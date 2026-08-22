@extends('layouts.app')

@section('title', 'Invoice '.$order->invoice_number)

@section('content')
<section class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft sm:p-8">
        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">{{ $errors->first() }}</div>
        @endif
        @if ($paymentError)
            <div class="mb-6 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">QRIS belum dapat dimuat: {{ $paymentError }}</div>
        @endif
        <div class="flex items-start justify-between gap-4">
            <div>
                @if ($order->status === 'delivered')
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Akun otomatis terkirim</p>
                @else
                    <p class="text-xs font-bold uppercase tracking-widest text-electric">Menunggu pembayaran</p>
                @endif
                <h1 class="mt-2 font-display text-2xl font-bold">Invoice {{ $order->invoice_number }}</h1>
            </div>
            @if ($order->status === 'delivered')
                <i class="fa-solid fa-circle-check text-3xl text-emerald-500"></i>
            @else
                <i class="fa-solid fa-qrcode text-3xl text-electric"></i>
            @endif
        </div>

        <div class="mt-8 divide-y divide-slate-100 text-sm">
            <div class="flex justify-between gap-4 py-3"><span class="text-slate-500">Produk</span><strong>{{ $order->product->name }}</strong></div>
            <div class="flex justify-between gap-4 py-3"><span class="text-slate-500">Metode pembayaran</span><strong>QRIS</strong></div>
            <div class="flex justify-between gap-4 py-3"><span class="text-slate-500">Total</span><strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong></div>
            <div class="flex justify-between gap-4 py-3"><span class="text-slate-500">Status</span>
                @if ($order->status === 'delivered')
                    <span class="rounded-full bg-emerald-50 px-3 py-1 font-bold text-emerald-700">Sudah dibayar</span>
                @else
                    <span class="rounded-full bg-amber-50 px-3 py-1 font-bold text-amber-700">Menunggu pembayaran</span>
                @endif
            </div>
        </div>

        @if ($order->status === 'awaiting_payment')
            <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-5 text-center">
                <h2 class="font-display font-bold text-blue-950">Scan QRIS untuk membayar</h2>
                <p class="mt-1 text-xs text-blue-700">Gunakan aplikasi pembayaran Anda dan bayar sesuai total invoice.</p>
                @if ($order->payment_number)
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($order->payment_number) }}" alt="QRIS {{ $order->invoice_number }}" class="mx-auto my-4 h-44 w-44 rounded-lg border border-white bg-white p-2">
                @else
                    <p class="mx-auto my-4 max-w-sm rounded-lg bg-white px-4 py-6 text-sm font-semibold text-amber-800">QRIS belum tersedia. Muat ulang halaman beberapa saat lagi.</p>
                @endif
                <p class="text-xs text-blue-700">Ref: <strong>{{ $order->invoice_number }}</strong></p>
                <form method="POST" action="{{ route('orders.confirm-payment', $order) }}" class="mt-4">
                    @csrf
                    <button class="w-full rounded-xl bg-electric py-3 text-sm font-bold text-white hover:bg-blue-700" type="submit">Saya sudah bayar, cek status</button>
                </form>
            </div>
        @endif

        @if ($order->productAccount)
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <h2 class="font-display font-bold text-emerald-900">Detail akun Anda</h2>
                <p class="mt-1 text-xs text-emerald-700">Simpan detail ini di tempat aman.</p>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-emerald-700">Login</dt><dd class="font-bold text-emerald-950">{{ $order->productAccount->login }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-emerald-700">Password</dt><dd class="font-bold text-emerald-950">{{ $order->productAccount->password }}</dd></div>
                </dl>
            </div>
        @endif

        <a href="{{ route('home') }}#produk" class="mt-6 inline-flex rounded-xl bg-ink px-5 py-3 text-sm font-bold text-white">Kembali ke katalog</a>
    </div>
</section>
@endsection
