<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Portal klienta') | Pensec</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased">

    <div class="relative z-50 w-full flex justify-center px-4 sm:px-6 mt-6 mb-10">
        <header class="flex min-w-0 max-w-full items-center gap-3 sm:gap-5 rounded-full border border-ink-line bg-ink-raised/70 px-4 sm:px-5 py-2 sm:py-2.5 shadow-lg backdrop-blur-md">
            <a href="{{ route('client.dashboard') }}" class="group shrink-0 flex items-center hover:opacity-80 transition-opacity">
                <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
            </a>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <a href="{{ route('client.dashboard') }}"
               class="shrink-0 whitespace-nowrap text-xs sm:text-sm font-medium {{ request()->routeIs('client.dashboard') ? 'text-chrome font-semibold' : 'text-muted hover:text-chrome' }}">
                Moje raporty
            </a>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <div class="shrink-0 flex items-center">@include('partials.theme-toggle')</div>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <form method="POST" action="{{ route('client.logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome transition-colors">Wyloguj</button>
            </form>
        </header>
    </div>

    <main class="flex-grow w-full mx-auto max-w-6xl px-6 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-brand/40 bg-brand/10 px-5 py-3 text-sm text-chrome">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    @include('partials.site-footer')
    @include('partials.cookie-consent')
</body>
</html>
