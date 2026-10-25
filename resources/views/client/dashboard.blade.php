@extends('client.layout')

@section('title', 'Moje raporty')

@section('content')
    <h1 class="text-2xl font-semibold chrome-text">Moje raporty</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
        Badania urządzeń przypisanych do konta, od najnowszego. Widok raportu zawiera ustalenia i dokumenty do pobrania.
    </p>

    @if ($reports->isEmpty())
        <div class="card mt-8 p-10 text-center">
            <p class="text-muted">Nie ma jeszcze żadnego raportu. Gdy sonda prześle badanie, pojawi się tutaj.</p>
        </div>
    @else
        <div class="card mt-8 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-line text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">Badanie</th>
                        <th class="px-5 py-3 font-medium">Odebrano</th>
                        <th class="px-5 py-3 font-medium">Urządzenie</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr class="border-b border-ink-line/60 last:border-0">
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $report->scanned_at?->format('Y-m-d H:i') ?? 'nie podano' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $report->received_at->format('Y-m-d H:i') }}</td>
                            <td class="px-5 py-4 text-chrome">{{ $report->device->name }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('client.reports.show', $report) }}" class="text-brand hover:underline">Otwórz</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $reports->links() }}</div>
    @endif
@endsection
