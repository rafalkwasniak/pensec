@extends('panel.layout')

@section('title', 'Klienci')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold chrome-text">Klienci</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                Konta klientów portalu i liczba przypisanych sond. Klient po zalogowaniu widzi wyłącznie
                raporty swoich urządzeń.
            </p>
        </div>
        <a href="{{ route('panel.clients.create') }}"
           class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
            Dodaj klienta
        </a>
    </div>

    @if ($clients->isEmpty())
        <div class="card mt-8 p-10 text-center">
            <p class="text-muted">Nie ma jeszcze żadnego klienta.</p>
        </div>
    @else
        <div class="card mt-8 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-line text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nazwa</th>
                        <th class="px-5 py-3 font-medium">E-mail</th>
                        <th class="px-5 py-3 font-medium">Urządzenia</th>
                        <th class="px-5 py-3 font-medium">Hasło</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $client)
                        <tr class="border-b border-ink-line/60 last:border-0">
                            <td class="px-5 py-4">
                                <a href="{{ route('panel.clients.edit', $client) }}" class="text-brand hover:underline">{{ $client->name }}</a>
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $client->email }}</td>
                            <td class="px-5 py-4 text-muted">{{ $client->devices_count }}</td>
                            <td class="px-5 py-4">
                                @if ($client->password)
                                    <span class="rounded-full border border-ink-line px-2.5 py-0.5 text-xs text-muted">ustawione</span>
                                @else
                                    <span class="rounded-full border border-warn-line px-2.5 py-0.5 text-xs text-warn">nie ustawione</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $clients->links() }}</div>
    @endif
@endsection
