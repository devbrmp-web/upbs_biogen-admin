<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MidtransService
{
    protected string $serverKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->serverKey = (string) (config('services.midtrans.serverKey')
            ?: config('payment.midtrans.server_key')
            ?: config('midtrans.server_key'));

        $configuredBaseUrl = (string) (config('payment.midtrans.base_url')
            ?: config('midtrans.base_url'));

        if ($configuredBaseUrl !== '') {
            $this->baseUrl = rtrim($configuredBaseUrl, '/');
            return;
        }

        $isProduction = (bool) (config('services.midtrans.isProduction')
            ?? config('payment.midtrans.is_production')
            ?? config('midtrans.is_production')
            ?? false);

        $this->baseUrl = $isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    public function createTransaction(array $payload): array
    {
        $url = $this->baseUrl . '/snap/v1/transactions';
        $auth = base64_encode($this->serverKey . ':');
        $res = Http::withHeaders([
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        if ($res->failed()) {
            throw new \RuntimeException('Midtrans create transaction failed');
        }

        return $res->json();
    }

    public function getStatus(string $orderId): array
    {
        $url = $this->baseUrl . '/v2/' . $orderId . '/status';
        $auth = base64_encode($this->serverKey . ':');
        $res = Http::withHeaders([
            'Authorization' => 'Basic ' . $auth,
            'Accept' => 'application/json',
        ])->get($url);

        if ($res->failed()) {
            throw new \RuntimeException('Midtrans get status failed');
        }

        return $res->json();
    }

    public function verifySignature(string $signatureKey, array $params): bool
    {
        $orderId = (string) ($params['order_id'] ?? '');
        $statusCode = (string) ($params['status_code'] ?? '');
        $grossAmount = (string) ($params['gross_amount'] ?? '');
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return hash_equals($expected, (string) $signatureKey);
    }

    public function createSnapToken(\App\Models\Order $order): array
    {
        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_code,
                'gross_amount' => (int) round($order->total_amount),
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            'item_details' => $order->items->map(function($item) {
                return [
                    'id' => $item->variety_id,
                    'price' => (int) round($item->unit_price),
                    'quantity' => (int) $item->quantity,
                    'name' => $item->variety_name,
                    'category' => optional($item->variety->commodity)->name,
                ];
            })->toArray(),
        ];
        return $this->createTransaction($payload);
    }
}
