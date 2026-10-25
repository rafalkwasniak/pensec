@extends('panel.layout')

@section('title', 'Klient')

@section('content')
    <div class="mx-auto max-w-xl">
        <a href="{{ route('panel.clients.index') }}" class="text-sm text-muted hover:text-chrome">&larr; Klienci</a>
        <h1 class="mt-4 text-2xl font-semibold chrome-text">{{ $client->name }}</h1>

        <form method="POST" action="{{ route('panel.clients.update', $client) }}" class="card mt-6 space-y-5 p-6">
            @csrf
            <div>
                <label for="name" class="block text-sm text-muted">Nazwa</label>
                <input id="name" name="name" value="{{ old('name', $client->name) }}" required
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('name')<p class="mt-2 text-sm text-warn">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm text-muted">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $client->email) }}" required
                       class="mt-2 w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-chrome outline-none focus:border-brand">
                @error('email')<p class="mt-2 text-sm text-warn">{{ $message }}</p>@enderror
            </div>

            <div>
                <span class="block text-sm text-muted">Przypisane urządzenia</span>
                @if ($devices->isEmpty())
                    <p class="mt-2 text-sm text-muted">Brak urządzeń do przypisania.</p>
                @else
                    <div class="mt-3 space-y-2">
                        @foreach ($devices as $device)
                            <label class="flex items-center gap-3 text-sm text-chrome">
                                <input type="checkbox" name="devices[]" value="{{ $device->id }}" @checked(in_array($device->id, $assigned, true))>
                                <span>{{ $device->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-muted">Zaznaczone urządzenia będą widoczne dla tego klienta w portalu.</p>
                @endif
            </div>

            <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
                Zapisz
            </button>
        </form>

        <form method="POST" action="{{ route('panel.clients.password', $client) }}" class="card mt-6 p-6">
            @csrf
            <h2 class="text-base font-semibold text-chrome">Dostęp do portalu</h2>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                {{ $client->password ? 'Klient ma ustawione hasło.' : 'Klient nie ustawił jeszcze hasła.' }}
                Wystaw jednorazowy link, aby klient mógł ustawić (lub zresetować) hasło. Poprzedni link przestanie działać.
            </p>
            <button type="submit" class="mt-4 rounded-lg border border-ink-line px-4 py-2.5 text-sm text-chrome transition hover:border-brand">
                Wystaw link do ustawienia hasła
            </button>
        </form>

        <form method="POST" action="{{ route('panel.clients.destroy', $client) }}" class="card mt-6 p-6"
              onsubmit="return confirm('Usunąć tego klienta? Jego urządzenia zostaną odpięte, a raporty pozostaną.')">
            @csrf
            <h2 class="text-base font-semibold text-chrome">Usuń klienta</h2>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                Konto zostanie usunięte, a przypisane urządzenia odpięte. Raporty nie znikają.
            </p>
            <button type="submit" class="mt-4 rounded-lg border border-warn-line px-4 py-2.5 text-sm text-warn transition hover:bg-warn-soft">
                Usuń klienta
            </button>
        </form>
    </div>
@endsection
