<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Kastore menyediakan produk digital premium dengan proses cepat dan dukungan 24 jam.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kastore - Produk Digital Premium')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['DM Sans', 'sans-serif'], display: ['Space Grotesk', 'sans-serif'] }, colors: { ink: '#111827', electric: '#2563eb', lime: '#d9f99d' }, boxShadow: { soft: '0 18px 50px rgba(15, 23, 42, .08)' } } } };
    </script>
    <style>
        :root {
            color-scheme: light;
        }

        body {
            background: #f8fafc;
            color: #111827;
        }

        .mesh-bg {
            background-color: #f8fafc;
            background-image: linear-gradient(rgba(37, 99, 235, .05) 1px, transparent 1px), linear-gradient(90deg, rgba(37, 99, 235, .05) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            scrollbar-width: none;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
    @stack('head')
</head>

<body class="font-sans antialiased min-h-screen flex flex-col">
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-lg">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
            <div class="h-[72px] flex items-center justify-between gap-6">
                <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0" aria-label="Kastore beranda"><span
                        class="w-10 h-10 rounded-xl bg-ink text-lime grid place-items-center font-display font-bold text-xl">K</span><span
                        class="font-display font-bold text-xl tracking-tight">Kastore<span
                            class="text-electric">.</span></span></a>
                <div class="hidden lg:flex items-center gap-1 text-sm font-semibold text-slate-500"><a href="#produk"
                        class="px-4 py-2 rounded-lg text-ink bg-slate-100">Produk</a><a href="#cara-pesan"
                        class="px-4 py-2 rounded-lg hover:bg-slate-100 hover:text-ink transition">Cara pesan</a><a
                        href="#faq"
                        class="px-4 py-2 rounded-lg hover:bg-slate-100 hover:text-ink transition">FAQ</a>@auth
                            @if(auth()->user()->isAdmin())<a href="{{ route('admin.payment-gateways') }}"
                                class="px-4 py-2 rounded-lg hover:bg-slate-100 hover:text-ink transition"><i
                        class="fa-solid fa-credit-card mr-1 text-electric"></i> Payment gateway</a>@endif @endauth
                </div>
                <div class="flex items-center gap-2"><a href="#cek-invoice"
                        class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-ink transition"><i
                            class="fa-solid fa-receipt text-electric"></i> Cek invoice</a>@auth<a
                                    href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : '#produk' }}"
                                    class="inline-flex items-center gap-2 rounded-xl bg-electric px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition"><i
                                        class="fa-solid fa-user"></i><span
                                        class="hidden sm:inline">{{ auth()->user()->isAdmin() ? 'Dashboard admin' : auth()->user()->name }}</span></a>
                                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">@csrf<button
                                        class="px-2 text-sm font-semibold text-slate-500 hover:text-ink"
                            type="submit">Keluar</button></form>@else<a href="{{ route('login') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-electric px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition"><i
                                    class="fa-solid fa-arrow-right-to-bracket"></i><span class="hidden sm:inline">Masuk
                            member</span><span class="sm:hidden">Masuk</span></a>@endauth<button id="mobile-menu-btn"
                        type="button" class="lg:hidden w-10 h-10 rounded-xl border border-slate-200 text-slate-600"
                        aria-label="Buka menu" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
                </div>
            </div>
            <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-100 py-3 space-y-1"><a href="#produk"
                    class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-ink bg-slate-100">Produk</a><a
                    href="#cara-pesan" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600">Cara
                    pesan</a><a href="#faq"
                    class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600">FAQ</a><a
                    href="#cek-invoice" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600">Cek
                    invoice</a>@auth @if(auth()->user()->isAdmin())<a href="{{ route('admin.payment-gateways') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600">Payment gateway</a>@endif
                    @endauth</div>
        </nav>
    </header>
    <main class="flex-1">@yield('content')</main>
    <footer id="bantuan" class="border-t border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex flex-col md:flex-row justify-between gap-8">
                <div class="max-w-sm">
                    <div class="flex items-center gap-3 mb-4"><span
                            class="w-9 h-9 rounded-lg bg-ink text-lime grid place-items-center font-display font-bold">K</span><strong
                            class="font-display text-lg">Kastore.</strong></div>
                    <p class="text-sm leading-6 text-slate-500">Produk digital premium, proses otomatis, dan bantuan
                        manusia saat Anda membutuhkannya.</p>
                </div>
                <div class="grid grid-cols-2 gap-x-12 gap-y-3 text-sm"><a href="#produk"
                        class="text-slate-500 hover:text-electric">Katalog produk</a><a href="#cara-pesan"
                        class="text-slate-500 hover:text-electric">Cara pesan</a><a href="#cek-invoice"
                        class="text-slate-500 hover:text-electric">Cek invoice</a><a href="#faq"
                        class="text-slate-500 hover:text-electric">FAQ</a></div>
            </div>
            <div
                class="mt-10 pt-6 border-t border-slate-100 flex flex-col sm:flex-row justify-between gap-3 text-xs text-slate-400">
                <span>&copy; {{ date('Y') }} Kastore. Dibuat untuk akses digital yang lebih mudah.</span><span
                    class="flex items-center gap-2"><i class="fa-solid fa-shield-halved text-emerald-500"></i>
                    Pembayaran aman</span></div>
        </div>
    </footer>
    <section class="border-t border-slate-100 bg-slate-50" aria-label="Kontak customer service dan media sosial">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-electric">Customer service</p>
                    <h2 class="mt-2 font-display text-lg font-bold text-ink">Butuh bantuan?</h2>
                    <p class="mt-1 text-sm text-slate-500">Tim kami siap membantu setiap hari.</p>
                </div><a href="https://wa.me/6285860142219" target="_blank" rel="noopener"
                    class="flex items-center gap-3 rounded-xl bg-white p-4 text-sm font-bold text-ink shadow-sm transition hover:-translate-y-0.5 hover:text-emerald-600"><i
                        class="fa-brands fa-whatsapp text-xl text-emerald-500"></i><span>WhatsApp CS<br><small
                            class="font-normal text-slate-400">+62 8586-0142-219</small></span></a><a
                    href="tokozoom78@gmail.com"
                    class="flex items-center gap-3 rounded-xl bg-white p-4 text-sm font-bold text-ink shadow-sm transition hover:-translate-y-0.5 hover:text-electric"><i
                        class="fa-solid fa-envelope text-lg text-electric"></i><span>Email CS<br><small
                            class="font-normal text-slate-400">cs@kastore.id</small></span></a>
                <div>
            </div>
        </div>
    </section>
    @stack('scripts')
</body>

</html>