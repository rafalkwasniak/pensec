@extends('panel.layout')

@section('title', 'Nowa sonda')

@section('content')
    <div class="mx-auto max-w-xl">
        <h1 class="text-2xl font-semibold chrome-text">Nowa sonda</h1>
        <p class="mt-2 text-sm leading-relaxed text-muted">
            Po dodaniu zobaczysz poświadczenie urządzenia. Będzie pokazane jeden raz - zapisz je od razu.
        </p>

        <form method="POST" action="{{ route('panel.devices.store') }}" class="card mt-8 space-y-5 p-6">
            @csrf

            <div>
                <label for="name" class="block text-sm text-muted">Nazwa</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       placeholder="np. Sonda - magazyn Kraków"
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('name')
                    <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                    Dodaj sondę
                </button>
                <a href="{{ route('panel.devices.index') }}" class="text-sm text-muted hover:text-chrome">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
