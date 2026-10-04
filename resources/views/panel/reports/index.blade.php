@extends('panel.layout')

@section('title', 'Badania')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold chrome-text">Badania</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                Raporty przesłane przez sondy, od najnowszego odebranego. „Badanie" to czas z zegara sondy,
                „Odebrano" to moment, w którym raport do nas dotarł; różnica pod datą odbioru mówi, jak długo
                dokument czekał na urządzeniu. Obie daty w UTC.
            </p>
        </div>

        @if ($devices->isNotEmpty())
            <form method="GET" action="{{ route('panel.reports.index') }}" class="flex items-center gap-3">
                <label for="device" class="text-sm text-muted">Sonda</label>
                <select id="device" name="device" onchange="this.form.submit()"
                        class="rounded-lg border border-ink-line bg-ink px-4 py-2 text-sm text-chrome outline-none focus:border-brand">
                    <option value="">wszystkie</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected($selectedDevice === $device->id)>{{ $device->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if ($reports->isEmpty())
        <div class="card mt-8 p-10 text-center">
            <p class="text-muted">Nie ma jeszcze żadnego badania.</p>
        </div>
    @else
        <div class="card mt-8 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-line text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">Badanie</th>
                        <th class="px-5 py-3 font-medium">Odebrano</th>
                        <th class="px-5 py-3 font-medium">Sonda</th>
                        <th class="px-5 py-3 font-medium">Identyfikator badania</th>
                        <th class="px-5 py-3 font-medium">Stan</th>
                        <th class="px-5 py-3 font-medium">Rozmiar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        @php($lag = $report->deliverySeconds())
                        <tr class="border-b border-ink-line/60 last:border-0">
                            <td class="whitespace-nowrap px-5 py-4 text-muted">
                                {{ $report->scanned_at?->format('Y-m-d H:i') ?? 'nie podano' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">
                                {{ $report->received_at->format('Y-m-d H:i') }}

                                @if ($lag !== null)
                                    {{-- Ujemna różnica to nie opóźnienie, tylko zegar sondy idący
                                         przed naszym, więc jest wyróżniona tak samo jak zaległość. --}}
                                    <span class="mt-1 block text-xs {{ $lag < 0 || $lag >= $lagWarningSeconds ? 'text-warn' : 'text-muted' }}">
                                        {{ $lag < 0 ? '-' : '+' }}{{ App\Support\Duration::compact(abs($lag)) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-chrome">{{ $report->device->name }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('panel.reports.show', $report) }}"
                                   class="font-mono text-xs text-brand hover:underline">{{ $report->report_uid }}</a>
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $report->status->value }}</td>
                            <td class="px-5 py-4 text-muted">{{ number_format($report->payload_bytes / 1024, 1, ',', ' ') }} kB</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $reports->links() }}</div>
    @endif
@endsection
