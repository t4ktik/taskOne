<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalCheckout
{
    public function createSale(array $credentials, array $payment)
    {
        $token = $this->accessToken($credentials);

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->post($this->baseUrl($credentials['mode']) . '/v1/payments/payment', [
                'intent' => 'sale',
                'payer' => ['payment_method' => 'paypal'],
                'transactions' => [[
                    'amount' => [
                        'total' => $payment['total'],
                        'currency' => $payment['currency'],
                    ],
                    'description' => $payment['description'],
                    'item_list' => [
                        'items' => [[
                            'name' => $payment['item_name'],
                            'currency' => $payment['currency'],
                            'quantity' => '1',
                            'price' => $payment['total'],
                        ]],
                    ],
                ]],
                'redirect_urls' => [
                    'return_url' => $payment['return_url'],
                    'cancel_url' => $payment['cancel_url'],
                ],
            ]);

        if(!$response->successful())
        {
            throw new RuntimeException('PayPal did not create the payment.');
        }

        $body = $response->json();
        $approval = null;

        foreach($body['links'] ?? [] as $link)
        {
            if(($link['rel'] ?? '') === 'approval_url')
            {
                $approval = $link['href'] ?? null;
                break;
            }
        }

        if(empty($body['id']) || empty($approval))
        {
            throw new RuntimeException('PayPal did not return an approval link.');
        }

        return [
            'id' => $body['id'],
            'approval_url' => $approval,
        ];
    }

    public function executeSale(array $credentials, $paymentId, $payerId)
    {
        $token = $this->accessToken($credentials);

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->post($this->baseUrl($credentials['mode']) . '/v1/payments/payment/' . $paymentId . '/execute', [
                'payer_id' => $payerId,
            ]);

        if(!$response->successful())
        {
            throw new RuntimeException('PayPal did not execute the payment.');
        }

        return $response->json();
    }

    protected function accessToken(array $credentials)
    {
        try
        {
            $response = Http::withBasicAuth($credentials['client_id'], $credentials['secret'])
                ->asForm()
                ->acceptJson()
                ->timeout(30)
                ->post($this->baseUrl($credentials['mode']) . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ])
                ->throw();
        }
        catch(RequestException $exception)
        {
            throw new RuntimeException('PayPal rejected the API credentials.', 0, $exception);
        }

        $token = $response->json('access_token');

        if(!is_string($token) || $token === '')
        {
            throw new RuntimeException('PayPal did not return an access token.');
        }

        return $token;
    }

    protected function baseUrl($mode)
    {
        return $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
