<?php

namespace App\Http\Controllers;

use App\Invoice;
use App\InvoicePayment;
use App\Project;
use App\Services\PayPalCheckout;
use App\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PaypalController extends Controller
{
    public function __construct(private PayPalCheckout $paypal)
    {
    }

    public function clientPayWithPaypal(Request $request, $invoice_id)
    {
        $user    = Auth::user();
        $invoice = Invoice::find($invoice_id);
        if(!$invoice || (int) $invoice->client_id !== (int) $user->id)
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $details = $user->decodeDetails($invoice->created_by);

        if($details['enable_paypal'] == 'on')
        {
            $get_amount = $request->amount;

            $validator = Validator::make(
                $request->all(), ['amount' => 'required|numeric|min:1']
            );

            if($validator->fails())
            {
                return redirect()->back()->with('error', $validator->errors()->first());
            }

            $project = Project::find($invoice->project_id);

            if($invoice)
            {
                if($get_amount > $invoice->getDue())
                {
                    return redirect()->back()->with('error', __('Invalid amount.'));
                }

                $name = $user->name . " - " . Utility::invoiceNumberFormat($invoice->invoice_id);

                try
                {
                    $payment = $this->paypal->createSale(
                        $this->credentials($details),
                        [
                            'total' => number_format((float) $get_amount, 2, '.', ''),
                            'currency' => $project->currency_code,
                            'description' => (string) $request->notes,
                            'item_name' => $name,
                            'return_url' => route('client.get.payment.status', $invoice->id),
                            'cancel_url' => route('client.get.payment.status', $invoice->id),
                        ]
                    );
                }
                catch(\RuntimeException $exception)
                {
                    return redirect()->route('invoices.show', $invoice_id)->with('error', __('Some error occur, sorry for inconvenient'));
                }

                Session::put('paypal_payment_id', $payment['id']);

                return Redirect::away($payment['approval_url']);
            }

            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function clientGetPaymentStatus(Request $request, $invoice_id)
    {
        $user    = Auth::user();
        $invoice = Invoice::find($invoice_id);

        if($invoice && (int) $invoice->client_id === (int) $user->id)
        {
            $details    = $user->decodeDetails($invoice->created_by);
            $payment_id = Session::get('paypal_payment_id');

            Session::forget('paypal_payment_id');

            if(empty($request->PayerID) || empty($request->token) || empty($payment_id))
            {
                return redirect()->route('invoices.show', $invoice_id)->with('error', __('Payment failed'));
            }

            try
            {
                $result = $this->paypal->executeSale($this->credentials($details), $payment_id, $request->PayerID);
                $state  = $result['state'] ?? '';
                $status = ucwords(str_replace('_', ' ', $state));

                if($state == 'approved')
                {
                    $invoice_payment                 = new InvoicePayment();
                    $invoice_payment->transaction_id = app('App\Http\Controllers\InvoiceController')->transactionNumber();
                    $invoice_payment->invoice_id     = $invoice->id;
                    $invoice_payment->amount         = $result['transactions'][0]['amount']['total'];
                    $invoice_payment->date           = date('Y-m-d');
                    $invoice_payment->payment_id     = 0;
                    $invoice_payment->payment_type   = __('PAYPAL');
                    $invoice_payment->client_id      = $user->id;
                    $invoice_payment->notes          = $result['transactions'][0]['description'] ?? '';
                    $invoice_payment->save();

                    if(($invoice->getDue() - $invoice_payment->amount) == 0)
                    {
                        Invoice::change_status($invoice->id, 3);
                    }
                    else
                    {
                        Invoice::change_status($invoice->id, 2);
                    }

                    return redirect()->route('invoices.show', $invoice_id)->with('success', __('Payment added Successfully'));
                }

                return redirect()->route('invoices.show', $invoice_id)->with('error', __('Transaction has been ' . $status));
            }
            catch(\Exception $e)
            {
                return redirect()->route('invoices.show', $invoice_id)->with('error', __('Transaction has been failed!'));
            }
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    protected function credentials(array $details)
    {
        return [
            'mode' => $details['paypal_mode'] ?: env('PAYPAL_MODE', 'sandbox'),
            'client_id' => $details['paypal_client_id'] ?: env('PAYPAL_CLIENT_ID'),
            'secret' => $details['paypal_secret_key'] ?: env('PAYPAL_SECRET_KEY'),
        ];
    }
}
