<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ReservationGateway
{
    /** Null only means a verified provider 404, never a timeout or authorization failure. */
    public function status(string $orderId): ?array
    {
        return $this->request('GET', '/v2/'.rawurlencode($orderId).'/status');
    }

    public function expire(string $orderId): ?array
    {
        return $this->request('POST', '/v2/'.rawurlencode($orderId).'/expire');
    }

    private function request(string $method, string $path): ?array
    {
        $key = config('services.midtrans.server_key');
        if (!is_string($key) || trim($key) === '') {
            throw new \RuntimeException('Midtrans server key belum dikonfigurasi.');
        }
        $base = config('services.midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $response = Http::withBasicAuth($key, '')->acceptJson()->connectTimeout(5)->timeout(15)->send($method, $base.$path);
        $payload = $response->json();
        if ($response->status() === 404 && is_array($payload) && ($payload['status_code'] ?? null) === '404') {
            return null;
        }
        $response->throw();
        if (!is_array($payload) || !isset($payload['transaction_status'])) {
            throw new \RuntimeException('Respons status Midtrans tidak valid.');
        }
        return $payload;
    }
}
