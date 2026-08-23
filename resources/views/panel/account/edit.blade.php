@extends('panel.layout')

@section('title', 'Konto')

@section('content')
    <div class="mx-auto max-w-xl">
        <h1 class="text-2xl font-semibold chrome-text">Twoje konto</h1>
        <p class="mt-2 text-sm text-muted">Dane logowania do panelu.</p>

        <form method="POST" action="{{ route('panel.account.update') }}" class="card mt-8 space-y-5 p-6">
            @csrf

            <h2 class="text-base font-semibold text-chrome">Dane konta</h2>

            <div>
                <label for="name" class="block text-sm text-muted">Imię i nazwisko</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('name')
                    <p class="mt-2 text-sm text-warn">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm text-muted">Adres e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                       autocomplete="username"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('email')
                    <p class="mt-2 text-sm text-warn">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-muted">Tym adresem logujesz się do panelu.</p>
            </div>

            <button type="submit"
                    class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                Zapisz dane
            </button>
        </form>

        <form method="POST" action="{{ route('panel.account.password') }}" class="card mt-6 space-y-5 p-6">
            @csrf

            <h2 class="text-base font-semibold text-chrome">Zmiana hasła</h2>
            <p class="text-sm leading-relaxed text-muted">
                Wystarczy podać nowe hasło - obecnego nie musisz pamiętać. Po zmianie pozostałe sesje zostaną
                wylogowane, a ta, w której teraz jesteś, zostaje aktywna.
            </p>

            <div>
                <label for="password" class="block text-sm text-muted">Nowe hasło</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('password')
                    <p class="mt-2 text-sm text-warn">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-muted">Co najmniej 12 znaków.</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm text-muted">Powtórz nowe hasło</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
            </div>

            <button type="submit"
                    class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                Zmień hasło
            </button>
        </form>
    </div>
@endsection
