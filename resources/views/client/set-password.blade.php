<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ustaw hasło | Portal klienta Pensec</title>
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
            <h1 class="mt-8 text-center text-xl font-semibold chrome-text">Ustaw hasło</h1>
            <p class="mt-2 text-center text-sm text-muted">Pierwsze ustawienie hasła do konta w portalu. Hasło musi mieć co najmniej 12 znaków.</p>

            <form method="POST" action="{{ route('client.password.setup.store', $token) }}" class="card mt-8 space-y-5 p-6">
                @csrf
                <div>
                    <label for="password" class="block text-sm text-muted">Hasło</label>
                    <input id="password" name="password" type="password" required autofocus
                           class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                    @error('password')<p class="mt-2 text-sm text-warn">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm text-muted">Powtórz hasło</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                </div>
                <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                    Ustaw hasło i zaloguj
                </button>
            </form>
        </div>
    </main>

    @include('partials.cookie-consent')
</body>
</html>
