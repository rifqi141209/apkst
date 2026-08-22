<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PakasirService;
use Throwable;

class OrderController extends Controller
{
    public function store(Request $request, Product $product)
    {
        if (! ProductAccount::where('product_id', $product->id)->where('status', 'available')->exists()) {
            return redirect()->route('home')->withErrors(['product' => 'Akun untuk produk ini sedang habis. Silakan pilih produk lain atau hubungi admin.']);
        }
        $order = Order::create(['invoice_number' => 'TL-'.now()->format('ymd').'-'.strtoupper(bin2hex(random_bytes(3))), 'user_id' => $request->user()->id, 'product_id' => $product->id, 'quantity' => 1, 'total' => $product->price, 'payment_method' => 'qris', 'status' => 'awaiting_payment']);
        try {
            $payment = app(PakasirService::class)->createQris($order->invoice_number, $order->total);
            $order->update(['payment_number' => $payment['payment_number'] ?? null, 'payment_expires_at' => $payment['expired_at'] ?? null]);
        } catch (Throwable $exception) {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Pembelian berhasil dibuat.');
    }

    public function confirmPayment(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        if ($order->status !== 'awaiting_payment') {
            return redirect()->route('orders.show', $order);
        }
        try {
            $payment = app(PakasirService::class)->detail($order->invoice_number, $order->total);
        } catch (Throwable $exception) {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => $exception->getMessage()]);
        }
        if (($payment['transaction']['status'] ?? $payment['status'] ?? null) !== 'completed') {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Pembayaran QRIS belum terkonfirmasi oleh Pakasir.']);
        }
        $account = $this->deliverOrder($order);
        if (! $account) {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Pembayaran diterima, tetapi akun produk sedang habis. Admin akan segera menindaklanjutinya.']);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Pembayaran QRIS dikonfirmasi dan akun telah dikirim.');
    }

    public function simulatePayment(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless(config('services.pakasir.sandbox'), 404);

        try {
            app(PakasirService::class)->simulate($order->invoice_number, $order->total);
        } catch (Throwable $exception) {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Pembayaran sandbox berhasil disimulasikan.');
    }

    public function webhook(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer'],
            'order_id' => ['required', 'string'],
            'project' => ['required', 'string'],
            'status' => ['required', 'string'],
            'payment_method' => ['nullable', 'string'],
        ]);
        if ($data['project'] !== config('services.pakasir.project') || $data['status'] !== 'completed' || ($data['payment_method'] ?? 'qris') !== 'qris') {
            return response()->json(['message' => 'Webhook tidak valid.'], 422);
        }
        $order = Order::where('invoice_number', $data['order_id'])->where('total', $data['amount'])->first();
        if (! $order) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }
        try {
            $payment = app(PakasirService::class)->detail($order->invoice_number, $order->total);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Pembayaran belum dapat diverifikasi oleh Pakasir.'], 502);
        }
        if (($payment['transaction']['status'] ?? $payment['status'] ?? null) !== 'completed') {
            return response()->json(['message' => 'Status pembayaran belum completed di Pakasir.'], 422);
        }
        if ($order->status === 'delivered') {
            return response()->json(['message' => 'Order sudah diproses.']);
        }
        if ($order->status !== 'awaiting_payment') {
            return response()->json(['message' => 'Status order tidak valid.'], 422);
        }
        if (! $this->deliverOrder($order)) {
            return response()->json(['message' => 'Akun produk sedang habis.'], 409);
        }

        return response()->json(['message' => 'Pembayaran diterima dan akun dikirim.']);
    }

    private function deliverOrder(Order $order): ?ProductAccount
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->status === 'delivered') {
                return $lockedOrder->productAccount;
            }
            $account = ProductAccount::where('product_id', $lockedOrder->product_id)->where('status', 'available')->lockForUpdate()->first();
            if (! $account) {
                return null;
            }
            $account->update(['status' => 'sold']);
            Product::whereKey($lockedOrder->product_id)->decrement('stock');
            $lockedOrder->update(['product_account_id' => $account->id, 'status' => 'delivered']);
            return $account;
        });
    }

    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $paymentError = null;
        $hasValidQrisPayload = is_string($order->payment_number)
            && str_starts_with(trim($order->payment_number), '000201');
        if ($order->status === 'awaiting_payment' && ! $hasValidQrisPayload) {
            $order->update(['payment_number' => null, 'payment_expires_at' => null]);
            try {
                $payment = app(PakasirService::class)->createQris($order->invoice_number, $order->total);
                $order->update(['payment_number' => $payment['payment_number'] ?? null, 'payment_expires_at' => $payment['expired_at'] ?? null]);
            } catch (Throwable $exception) {
                $paymentError = $exception->getMessage();
            }
        }

        return view('orders.show', compact('order', 'paymentError'));
    }
}