<!DOCTYPE html>
<html lang="pl" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') - panel Pensec</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen antialiased">

    <header class="border-b border-ink-line">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-6 px-6 py-4">
            <a href="{{ route('panel.devices.index') }}" class="flex items-center gap-3">
                <img src="/images/pensec-mark.webp" alt="" width="512" height="590" class="theme-when-dark h-7 w-auto">
                <img src="/images/pensec-mark-light.webp" alt="" width="512" height="590" class="theme-when-light h-7 w-auto">
                <span class="font-semibold tracking-wide text-chrome">PENSEC</span>
            </a>

            @auth
                <nav class="flex items-center gap-5 text-sm">
                    @foreach ([
                        ['panel.devices.index', 'Sondy', 'panel/devices*'],
                        ['panel.reports.index', 'Badania', 'panel/reports*'],
                        ['panel.administrators.index', 'Administratorzy', 'panel/administrators*'],
                        ['panel.account.edit', 'Konto', 'panel/account*'],
                    ] as [$route, $label, $pattern])
                        <a href="{{ route($route) }}"
                           class="{{ request()->is($pattern) ? 'text-brand' : 'text-muted hover:text-chrome' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            @endauth

            <div class="ml-auto flex items-center gap-5">
                @include('partials.theme-toggle')

                @auth
                    <form method="POST" action="{{ route('panel.logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-muted hover:text-chrome">Wyloguj</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-brand/40 bg-brand/10 px-5 py-3 text-sm text-chrome">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg border border-warn-line bg-warn-soft px-5 py-3 text-sm text-warn">
                {{ session('error') }}
            </div>
        @endif

        @if (session('token'))
            <div class="mb-6 card p-6">
                <h2 class="text-base font-semibold text-chrome">Poświadczenie sondy</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted">
                    Zapisz je teraz i wgraj na urządzenie. Po opuszczeniu tej strony nie da się go już odczytać -
                    w bazie zapisany jest wyłącznie jego skrót. Jeśli je zgubisz, wystawisz nowe.
                </p>
                <p class="mt-4 overflow-x-auto rounded-lg border border-ink-line bg-ink px-4 py-3 font-mono text-sm text-brand">{{ session('token') }}</p>
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
