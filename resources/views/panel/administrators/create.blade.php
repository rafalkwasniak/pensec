@extends('panel.layout')

@section('title', 'Nowy administrator')

@section('content')
    <div class="mx-auto max-w-xl">
        <a href="{{ route('panel.administrators.index') }}" class="text-sm text-muted hover:text-chrome">&larr; Administratorzy</a>
        <h1 class="mt-4 text-2xl font-semibold chrome-text">Nowy administrator</h1>
        <p class="mt-2 text-sm leading-relaxed text-muted">
            Konto zadziała natychmiast po utworzeniu. Przekaż tej osobie adres i hasło - zmieni je sobie
            w zakładce Konto.
        </p>

        <form method="POST" action="{{ route('panel.administrators.store') }}" class="card mt-8 space-y-5 p-6">
            @csrf

            <div>
                <label for="name" class="block text-sm text-muted">Imię i nazwisko</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('name')
                    <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm text-muted">Adres e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       autocomplete="off"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('email')
                    <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-muted">Tym adresem ta osoba będzie się logować.</p>
            </div>

            <div>
                <label for="password" class="block text-sm text-muted">Hasło</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('password')
                    <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-muted">Co najmniej 12 znaków.</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm text-muted">Powtórz hasło</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                    Utwórz konto
                </button>
                <a href="{{ route('panel.administrators.index') }}" class="text-sm text-muted hover:text-chrome">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
