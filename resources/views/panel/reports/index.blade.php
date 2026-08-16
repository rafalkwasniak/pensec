@extends('panel.layout')

@section('title', 'Badania')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold chrome-text">Badania</h1>
            <p class="mt-2 text-sm text-muted">Raporty przesłane przez sondy, od najnowszego.</p>
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
                        <th class="px-5 py-3 font-medium">Odebrano</th>
                        <th class="px-5 py-3 font-medium">Sonda</th>
                        <th class="px-5 py-3 font-medium">Identyfikator badania</th>
                        <th class="px-5 py-3 font-medium">Stan</th>
                        <th class="px-5 py-3 font-medium">Rozmiar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr class="border-b border-ink-line/60 last:border-0">
                            <td class="px-5 py-4 text-muted">{{ $report->received_at->format('Y-m-d H:i') }}</td>
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
