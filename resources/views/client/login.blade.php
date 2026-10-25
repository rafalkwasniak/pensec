<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Logowanie | Portal klienta Pensec</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased">
    <main class="flex-grow flex items-center justify-center px-6 py-16">
        <div class="w-full max-w-sm">
            <div class="flex justify-center">
                <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-6 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-6 w-auto">
            </div>
            <h1 class="mt-8 text-center text-xl font-semibold chrome-text">Portal klienta</h1>
            <p class="mt-2 text-center text-sm text-muted">Dostęp do raportów z badań urządzeń przypisanych do konta.</p>

            @if (session('status'))
                <div class="mt-6 rounded-lg border border-brand/40 bg-brand/10 px-4 py-3 text-sm text-chrome">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('client.login.store') }}" class="card mt-8 space-y-5 p-6">
                @csrf
                <div>
                    <label for="email" class="block text-sm text-muted">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                    @error('email')<p class="mt-2 text-sm text-warn">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm text-muted">Hasło</label>
                    <input id="password" name="password" type="password" required
                           class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                </div>
                <label class="flex items-center gap-2 text-sm text-muted">
                    <input type="checkbox" name="remember" value="1"> Zapamiętaj mnie
                </label>
                <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                    Zaloguj się
                </button>
            </form>
        </div>
    </main>

    @include('partials.cookie-consent')
</body>
</html>
