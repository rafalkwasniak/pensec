@extends('panel.layout')

@section('title', $device->name)

@section('content')
    <div class="mx-auto max-w-xl">
        <a href="{{ route('panel.devices.index') }}" class="text-sm text-muted hover:text-chrome">&larr; Sondy</a>
        <h1 class="mt-4 text-2xl font-semibold chrome-text">{{ $device->name }}</h1>
        <p class="mt-2 font-mono text-sm text-muted">{{ $device->token_prefix }}&hellip;</p>

        <form method="POST" action="{{ route('panel.devices.update', $device) }}" class="card mt-8 space-y-5 p-6">
            @csrf

            <div>
                <label for="name" class="block text-sm text-muted">Nazwa</label>
                <input id="name" name="name" type="text" value="{{ old('name', $device->name) }}" required
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('name')
                    <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm text-muted">Stan</label>
                <select id="status" name="status"
                        class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                    @foreach (['active' => 'Aktywna - może przesyłać badania', 'disabled' => 'Wyłączona - przesyłki są odrzucane'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $device->status->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                Zapisz
            </button>
        </form>

        <div class="card mt-6 p-6">
            <h2 class="text-base font-semibold text-chrome">Nowe poświadczenie</h2>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                Wystawienie nowego poświadczenia natychmiast unieważnia poprzednie. Sonda przestanie przesyłać
                badania, dopóki nie wgrasz jej nowego.
            </p>
            <form method="POST" action="{{ route('panel.devices.token', $device) }}" class="mt-4"
                  onsubmit="return confirm('Poprzednie poświadczenie przestanie działać. Kontynuować?')">
                @csrf
                <button type="submit"
                        class="rounded-lg border border-ink-line px-4 py-2.5 text-sm text-chrome transition hover:border-brand">
                    Wystaw nowe poświadczenie
                </button>
            </form>
        </div>

        <div class="card mt-6 p-6">
            <h2 class="text-base font-semibold text-chrome">Usunięcie sondy</h2>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                Sondę z powiązanymi badaniami można wyłącznie wyłączyć - wyniki badań zostają w systemie.
            </p>
            <form method="POST" action="{{ route('panel.devices.destroy', $device) }}" class="mt-4"
                  onsubmit="return confirm('Usunąć sondę {{ $device->name }}?')">
                @csrf
                <button type="submit"
                        class="rounded-lg border border-ink-line px-4 py-2.5 text-sm text-muted transition hover:border-amber-500/60 hover:text-amber-200">
                    Usuń sondę
                </button>
            </form>
        </div>
    </div>
@endsection
