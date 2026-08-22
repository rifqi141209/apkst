<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PakasirService
{
    public function createQris(string $orderId, int $amount): array
    {
        $payment = $this->post('/api/transactioncreate/qris', [
            'project' => config('services.pakasir.project'),
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => config('services.pakasir.api_key'),
        ]);

        return $payment;
    }

    public function detail(string $orderId, int $amount): array
    {
        $response = Http::timeout(15)->get($this->url('/api/transactiondetail'), [
            'project' => config('services.pakasir.project'),
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => config('services.pakasir.api_key'),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Pakasir tidak dapat memeriksa status pembayaran.');
        }

        return $response->json();
    }

    public function simulate(string $orderId, int $amount): void
    {
        if (! config('services.pakasir.sandbox')) {
            throw new RuntimeException('Simulasi pembayaran hanya tersedia dalam mode sandbox.');
        }

        $response = Http::acceptJson()->asJson()->timeout(15)->post($this->url('/api/paymentsimulation'), [
            'project' => config('services.pakasir.project'),
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => config('services.pakasir.api_key'),
        ]);
        if ($response->failed()) {
            throw new RuntimeException($response->json('message') ?? 'Pakasir gagal mensimulasikan pembayaran.');
        }
    }

    private function post(string $path, array $payload): array
    {
        if (! config('services.pakasir.project') || ! config('services.pakasir.api_key')) {
            throw new RuntimeException('Konfigurasi Pakasir belum lengkap.');
        }

        $response = Http::acceptJson()->asJson()->timeout(15)->post($this->url($path), $payload);
        $payment = $response->json('payment');
        if ($response->failed() || ! is_array($payment)) {
            throw new RuntimeException($response->json('message') ?? 'Pakasir gagal membuat transaksi QRIS.');
        }
        if (! is_string($payment['payment_number'] ?? null)
            || ! str_starts_with(trim($payment['payment_number']), '000201')) {
            throw new RuntimeException('Pakasir tidak mengembalikan payload QRIS yang valid. Aktifkan QRIS live pada project Pakasir.');
        }

        return $payment;
    }

    private function url(string $path): string
    {
        return rtrim(config('services.pakasir.base_url'), '/').'/'.ltrim($path, '/');
    }
}