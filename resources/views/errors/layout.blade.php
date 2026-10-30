<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') - SportManado</title>
    @include('layouts._assets')
</head>
<body class="min-h-screen bg-dark-bg text-white flex items-center justify-center p-6">
    <main class="max-w-lg w-full rounded-xl border border-slate-800 bg-dark-card p-6 sm:p-8">
        <p class="text-sm font-bold text-accent">SportManado · @yield('code')</p>
        <h1 class="mt-3 text-2xl font-bold">@yield('heading')</h1>
        <p class="mt-4 text-sm leading-relaxed text-slate-300">@yield('message')</p>
        <a href="{{ url('/') }}" class="mt-6 inline-flex min-h-11 items-center rounded-lg bg-accent px-5 py-3 text-sm font-bold">Kembali ke Beranda</a>
    </main>
</body>
</html>
