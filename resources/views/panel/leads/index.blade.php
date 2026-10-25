@extends('panel.layout')

@section('title', 'Zapytania')

@section('content')
    <div>
        <h1 class="text-2xl font-semibold chrome-text">Zapytania</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
            Zapytania z formularza kontaktowego, od najnowszego. Każde trafia też jako powiadomienie
            na adres zbierający leady. Źródło to kampania z parametrów utm_* linku, a bez nich strona,
            z której nastąpiło przejście.
        </p>
    </div>

    @if ($leads->isEmpty())
        <div class="card mt-8 p-10 text-center">
            <p class="text-muted">Nie ma jeszcze żadnego zapytania.</p>
        </div>
    @else
        <div class="card mt-8 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-ink-line text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">Data</th>
                        <th class="px-5 py-3 font-medium">Od</th>
                        <th class="px-5 py-3 font-medium">Firma</th>
                        <th class="px-5 py-3 font-medium">Źródło</th>
                        <th class="px-5 py-3 font-medium">Wiadomość</th>
                        <th class="px-5 py-3 font-medium">Stan</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($leads as $lead)
                        <tr class="border-b border-ink-line/60 last:border-0 align-top">
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $lead->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-5 py-4">
                                <div class="text-chrome">{{ $lead->name }}</div>
                                <a href="mailto:{{ $lead->email }}" class="font-mono text-xs text-brand hover:underline">{{ $lead->email }}</a>
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $lead->company ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="text-chrome">{{ $lead->sourceLabel() }}</div>
                                @if ($lead->landing_page)
                                    <div class="font-mono text-xs text-muted">wejście: {{ $lead->landing_page }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-muted">{{ \Illuminate\Support\Str::limit($lead->message, 160) }}</td>
                            <td class="px-5 py-4">
                                @if ($lead->handled_at)
                                    <span class="rounded-full border border-ink-line px-2.5 py-0.5 text-xs text-muted">obsłużone</span>
                                @else
                                    <span class="rounded-full border border-warn-line px-2.5 py-0.5 text-xs text-warn">nowe</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <form method="POST" action="{{ route('panel.leads.handled', $lead) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-muted transition hover:text-chrome">
                                        {{ $lead->handled_at ? 'oznacz jako nowe' : 'oznacz jako obsłużone' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $leads->links() }}</div>
    @endif
@endsection
