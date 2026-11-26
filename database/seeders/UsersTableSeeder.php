<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminPassword = env('SEED_ADMIN_PASSWORD');
        $ownerPassword = env('SEED_OWNER_PASSWORD');
        $generated     = false;

        if(empty($adminPassword) || empty($ownerPassword))
        {
            $adminPassword = $adminPassword ?: Str::random(24);
            $ownerPassword = $ownerPassword ?: Str::random(24);
            $generated     = true;
        }

        $admin = User::create(
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make($adminPassword),
                'lang' => 'en',
            ]
        );
        $admin->type      = 'admin';
        $admin->is_active = 1;
        $admin->save();

        // create new email template notification
        $admin->defaultEmail();

        $owner = User::create(
            [
                'name' => 'Owner',
                'email' => 'owner@example.com',
                'password' => Hash::make($ownerPassword),
                'lang' => 'en',
            ]
        );
        $owner->type       = 'owner';
        $owner->created_by = $admin->id;
        $owner->is_active  = 1;
        $owner->save();

        if($generated)
        {
            $path = storage_path('app/seeded-credentials.txt');
            file_put_contents(
                $path,
                "admin@example.com {$adminPassword}\nowner@example.com {$ownerPassword}\n"
            );
            @chmod($path, 0600);

            if($this->command)
            {
                $this->command->warn('Initial passwords were written to storage/app/seeded-credentials.txt. Change them and delete that file.');
            }
        }
    }
}
