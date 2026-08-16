@extends('panel.layout')

@section('title', 'Logowanie')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-semibold chrome-text">Panel administracyjny</h1>
        <p class="mt-2 text-sm text-muted">Zaloguj się, żeby zarządzać sondami i przeglądać badania.</p>

        <form method="POST" action="{{ route('panel.login.store') }}" class="card mt-8 space-y-5 p-6">
            @csrf

            <div>
                <label for="email" class="block text-sm text-muted">Adres e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('email')
                    <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm text-muted">Hasło</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('password')
                    <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" value="1" class="rounded border-ink-line bg-ink">
                Nie wylogowuj mnie
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-brand px-4 py-2.5 font-semibold text-ink transition hover:bg-brand/90">
                Zaloguj
            </button>
        </form>
    </div>
@endsection
