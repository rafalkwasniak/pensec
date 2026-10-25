@extends('panel.layout')

@section('title', 'Nowy klient')

@section('content')
    <div class="mx-auto max-w-xl">
        <a href="{{ route('panel.clients.index') }}" class="text-sm text-muted hover:text-chrome">&larr; Klienci</a>
        <h1 class="mt-4 text-2xl font-semibold chrome-text">Nowy klient</h1>
        <p class="mt-2 text-sm leading-relaxed text-muted">
            Po utworzeniu dostaniesz jednorazowy link do ustawienia hasła. Urządzenia przypiszesz na
            następnym ekranie.
        </p>

        <form method="POST" action="{{ route('panel.clients.store') }}" class="card mt-6 space-y-5 p-6">
            @csrf
            <div>
                <label for="name" class="block text-sm text-muted">Nazwa</label>
                <input id="name" name="name" value="{{ old('name') }}" required
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('name')<p class="mt-2 text-sm text-warn">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm text-muted">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('email')<p class="mt-2 text-sm text-warn">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                Utwórz klienta
            </button>
        </form>
    </div>
@endsection
