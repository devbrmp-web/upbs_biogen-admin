<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    private string $baseUrl;
    private string $serverKey;

    public function __construct()
    {
        $cfg = config('payment.midtrans');
        $this->baseUrl = rtrim($cfg['base_url'] ?? 'https://api.sandbox.midtrans.com', '/');
        $this->serverKey = (string) ($cfg['server_key'] ?? '');
    }

    /**
     * Get transaction status from Midtrans by order ID (merchant order_id).
     * Returns array as provided by Midtrans.
     */
    public function getStatus(string $orderId): array
    {
        if (empty($this->serverKey)) {
            throw new \RuntimeException('MIDTRANS_SERVER_KEY is not configured');
        }

        $url = $this->baseUrl . '/v2/' . urlencode($orderId) . '/status';

        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->get($url);

        if ($response->failed()) {
            Log::error('Midtrans status request failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to fetch status from Midtrans');
        }

        return $response->json();
    }
}

