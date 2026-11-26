<?php

namespace Tests\Feature;

use App\Invoice;
use App\InvoiceProduct;
use App\Project;
use App\ProjectTask;
use App\ProjectUser;
use App\Support\RequestGuard;
use App\User;
use App\UserContact;
use App\UserToDo;
use App\Utility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if(!is_dir(storage_path()))
        {
            mkdir(storage_path(), 0777, true);
        }

        file_put_contents(storage_path('installed'), '1');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed'));

        parent::tearDown();
    }

    public function test_profile_update_cannot_change_role()
    {
        $user = $this->makeUser('owner');

        $this->actingAs($user)->post(route('update.profile'), [
            'from' => 'profile',
            'name' => 'Owner Name',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'phone' => '123456789',
            'type' => 'admin',
            'is_active' => 1,
            'created_by' => 99,
        ])->assertRedirect();

        $user->refresh();

        $this->assertSame('owner', $user->type);
        $this->assertSame(0, (int) $user->created_by);
        $this->assertSame('Owner Name', $user->name);
    }

    public function test_guest_cannot_call_state_changing_routes()
    {
        $this->post(route('change.complete', [1, 1]))->assertRedirect(route('login'));
        $this->get(route('invoice.payment.reminder', 1))->assertRedirect(route('login'));
        $this->get(route('search.json'))->assertRedirect(route('login'));
    }

    public function test_user_cannot_delete_another_owners_project()
    {
        $owner = $this->makeUser('owner');
        $other = $this->makeUser('owner');
        $project = $this->makeProject($owner);

        $this->actingAs($other)->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_user_cannot_complete_another_projects_task()
    {
        $owner = $this->makeUser('owner');
        $other = $this->makeUser('owner');
        $project = $this->makeProject($owner);
        $task = ProjectTask::create([
            'name' => 'Secret task',
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($other)
            ->post(route('change.complete', [$project->id, $task->id]))
            ->assertStatus(403);

        $this->assertSame(0, (int) $task->fresh()->is_complete);
    }

    public function test_user_cannot_view_another_users_invoice()
    {
        $owner = $this->makeUser('owner');
        $other = $this->makeUser('owner');
        $project = $this->makeProject($owner);
        $invoice = Invoice::create([
            'invoice_id' => 1,
            'project_id' => $project->id,
            'client_id' => $owner->id,
            'due_date' => '2026-01-01',
            'tax_id' => 1,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($other)->get(route('invoices.show', $invoice))->assertRedirect(route('invoices.index'));
    }

    public function test_user_cannot_delete_another_users_todo()
    {
        $owner = $this->makeUser('owner');
        $other = $this->makeUser('owner');
        $todo = UserToDo::create([
            'title' => 'Private',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($other)->delete(route('todo.destroy', $todo->id))->assertStatus(403);

        $this->assertDatabaseHas('user_to_dos', ['id' => $todo->id]);
    }

    public function test_member_search_does_not_escape_its_contact_scope()
    {
        $owner = $this->makeUser('owner');
        $contact = $this->makeUser('user', ['name' => 'Alice', 'skills' => 'php']);
        $stranger = $this->makeUser('user', ['name' => 'Bob', 'skills' => 'design']);

        UserContact::create([
            'parent_id' => $owner->id,
            'user_id' => $contact->id,
            'role' => 'user',
        ]);

        $response = $this->actingAs($owner)->get(route('filter.user.view', [
            'view' => 'grid',
            'role' => 'all',
            'keyword' => 'design") OR 1=1 -- ',
        ]), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertDontSee($stranger->email);
        $response->assertDontSee($contact->email);
    }

    public function test_login_response_sends_security_headers()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_environment_values_cannot_break_out_of_the_line()
    {
        $escaped = Utility::escapeEnvironmentValue("smtp.example.com\"\nEVIL_KEY=stolen");

        $this->assertStringNotContainsString("\n", $escaped);
        $this->assertStringContainsString('\\"', $escaped);
    }

    public function test_chat_attachment_download_stays_inside_its_directory()
    {
        $user = $this->makeUser('owner');
        $folder = storage_path('app/public/attachments');
        $outside = storage_path('app/public/secret-outside.txt');

        if(!is_dir($folder))
        {
            mkdir($folder, 0777, true);
        }

        file_put_contents($folder . '/note.txt', 'inside-attachment');
        file_put_contents($outside, 'TOP-SECRET-MARKER');

        try
        {
            DB::table('messages')->insert([
                'id' => 9001,
                'type' => 'user',
                'from_id' => $user->id,
                'to_id' => $user->id,
                'body' => 'file',
                'attachment' => 'note.txt,note.txt',
                'seen' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stranger = $this->makeUser('owner');

            $this->get('/messages/download/note.txt')->assertRedirect(route('login'));

            $this->actingAs($stranger)
                ->get('/messages/download/note.txt')
                ->assertStatus(404)
                ->assertDontSee('inside-attachment');

            $this->actingAs($user)
                ->get('/messages/download/' . rawurlencode('../secret-outside.txt'))
                ->assertStatus(404)
                ->assertDontSee('TOP-SECRET-MARKER');

            $this->actingAs($user)
                ->get('/messages/download/..')
                ->assertStatus(404);

            $download = $this->actingAs($user)->get('/messages/download/note.txt');
            $download->assertOk();
            $this->assertStringContainsString('note.txt', (string) $download->headers->get('content-disposition'));
        }
        finally
        {
            @unlink($folder . '/note.txt');
            @unlink($outside);
        }
    }

    public function test_invoice_client_is_sent_to_paypal_and_a_stranger_is_not()
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token-1'], 200),
            'api-m.sandbox.paypal.com/v1/payments/payment' => Http::response([
                'id' => 'PAY-1',
                'links' => [
                    ['rel' => 'approval_url', 'href' => 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-1'],
                ],
            ], 201),
            'api-m.sandbox.paypal.com/v1/payments/payment/PAY-1/execute' => Http::response([
                'state' => 'approved',
                'transactions' => [[
                    'amount' => ['total' => '25.00', 'currency' => 'USD'],
                    'description' => 'note',
                ]],
            ], 200),
        ]);

        $owner = $this->makeUser('owner');
        $owner->details = json_encode([
            'enable_paypal' => 'on',
            'paypal_mode' => 'sandbox',
            'paypal_client_id' => 'client-id',
            'paypal_secret_key' => 'client-secret',
        ]);
        $owner->save();

        $client = $this->makeUser('client');
        $stranger = $this->makeUser('client');
        $project = $this->makeProject($owner);
        $project->currency_code = 'USD';
        $project->save();

        $invoice = Invoice::create([
            'invoice_id' => 7,
            'project_id' => $project->id,
            'client_id' => $client->id,
            'due_date' => '2026-01-01',
            'tax_id' => 1,
            'created_by' => $owner->id,
        ]);
        InvoiceProduct::create([
            'invoice_id' => $invoice->id,
            'item' => 'Work',
            'price' => 25,
            'type' => 'item',
        ]);

        $this->actingAs($stranger)
            ->post(route('client.pay.with.paypal', $invoice->id), ['amount' => 25])
            ->assertRedirect();

        $this->assertDatabaseMissing('invoice_payments', ['invoice_id' => $invoice->id]);

        $this->actingAs($client)
            ->post(route('client.pay.with.paypal', $invoice->id), ['amount' => 25, 'notes' => 'note'])
            ->assertRedirect('https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-1');

        $this->actingAs($client)
            ->get(route('client.get.payment.status', $invoice->id) . '?PayerID=PAYER&token=EC-1')
            ->assertRedirect(route('invoices.show', $invoice->id));

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'amount' => 25,
        ]);
    }

    public function test_sort_and_view_inputs_are_whitelisted()
    {
        $this->assertSame(['created_at', 'desc'], RequestGuard::sort('name;drop table users-desc', ['created_at', 'name']));
        $this->assertSame('grid', RequestGuard::allowedView('../.env', ['grid', 'list'], 'grid'));
        $this->assertNull(RequestGuard::safeLangCode('../en'));
    }

    protected function makeUser($type, array $overrides = [])
    {
        $user = User::create(array_merge([
            'name' => ucfirst($type) . ' ' . uniqid(),
            'email' => uniqid($type) . '@example.com',
            'password' => Hash::make('password123'),
            'lang' => 'en',
        ], $overrides));
        $user->type = $type;
        $user->is_active = 1;
        $user->save();

        return $user;
    }

    protected function makeProject(User $owner)
    {
        $project = new Project();
        $project->name = 'Project ' . uniqid();
        $project->status = 'on_hold';
        $project->currency = '$';
        $project->created_by = $owner->id;
        $project->is_active = 1;
        $project->save();

        ProjectUser::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'permission' => 'owner',
            'user_permission' => json_encode($owner->getAllPermission()),
        ]);

        return $project;
    }
}
