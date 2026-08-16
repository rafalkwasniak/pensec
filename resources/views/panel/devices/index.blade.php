@extends('panel.layout')

@section('title', 'Sondy')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold chrome-text">Sondy</h1>
            <p class="mt-2 text-sm text-muted">Urządzenia dopuszczone do przesyłania raportów.</p>
        </div>

        <a href="{{ route('panel.devices.create') }}"
           class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
            Dodaj sondę
        </a>
    </div>

    @if ($devices->isEmpty())
        <div class="card mt-8 p-10 text-center">
            <p class="text-muted">Nie ma jeszcze żadnej sondy. Dodaj pierwszą, żeby mogła przesyłać badania.</p>
        </div>
    @else
        <div class="card mt-8 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-line text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nazwa</th>
                        <th class="px-5 py-3 font-medium">Stan</th>
                        <th class="px-5 py-3 font-medium">Poświadczenie</th>
                        <th class="px-5 py-3 font-medium">Badania</th>
                        <th class="px-5 py-3 font-medium">Ostatni kontakt</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($devices as $device)
                        <tr class="border-b border-ink-line/60 last:border-0">
                            <td class="px-5 py-4 font-medium text-chrome">{{ $device->name }}</td>
                            <td class="px-5 py-4">
                                @if ($device->isActive())
                                    <span class="rounded-full border border-brand/40 bg-brand/10 px-3 py-1 text-xs text-brand">aktywna</span>
                                @else
                                    <span class="rounded-full border border-ink-line px-3 py-1 text-xs text-muted">wyłączona</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-muted">{{ $device->token_prefix }}&hellip;</td>
                            <td class="px-5 py-4 text-muted">
                                @if ($device->reports_count > 0)
                                    <a href="{{ route('panel.reports.index', ['device' => $device->id]) }}"
                                       class="text-brand hover:underline">{{ $device->reports_count }}</a>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="px-5 py-4 text-muted">
                                {{ $device->last_seen_at?->format('Y-m-d H:i') ?? 'nigdy' }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('panel.devices.edit', $device) }}"
                                   class="text-brand hover:underline">Edytuj</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $devices->links() }}</div>
    @endif
@endsection
