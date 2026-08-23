@extends('panel.layout')

@section('title', 'Badanie')

@section('content')
    <a href="{{ route('panel.reports.index') }}" class="text-sm text-muted hover:text-chrome">&larr; Badania</a>

    <div class="mt-4">
        <h1 class="text-2xl font-semibold chrome-text">Badanie</h1>
        <p class="mt-2 font-mono text-sm text-muted">{{ $report->report_uid }}</p>
    </div>

    <dl class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            ['Sonda', $report->device->name],
            ['Odebrano', $report->received_at->format('Y-m-d H:i:s').' UTC'],
            ['Stan', $report->status->value],
            ['Rozmiar', number_format($report->payload_bytes, 0, ',', ' ').' B'],
            ['Adres źródłowy', $report->source_ip ?? 'nieznany'],
        ] as [$label, $value])
            <div class="card p-5">
                <dt class="text-xs uppercase tracking-widest text-muted">{{ $label }}</dt>
                <dd class="mt-2 text-chrome">{{ $value }}</dd>
            </div>
        @endforeach

        <div class="card p-5">
            <dt class="text-xs uppercase tracking-widest text-muted">Suma kontrolna</dt>
            <dd class="mt-2 break-all font-mono text-xs text-chrome">{{ $report->payload_sha256 }}</dd>
        </div>
    </dl>

    @include('panel.reports.partials.narratives', ['report' => $report])

    <section class="card mt-8 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-line px-5 py-3">
            <h2 class="text-xs uppercase tracking-widest text-muted">Treść raportu</h2>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="load-payload"
                        class="rounded-lg border border-ink-line px-3 py-1.5 text-sm text-chrome transition hover:border-brand">
                    Pokaż treść
                </button>

                {{-- Pobiera zapisany dokument, nie PDF. Stoi przy treści raportu,
                     bo to ta sama rzecz: jedno ją pokazuje, drugie zapisuje na dysk. --}}
                <a href="{{ route('panel.reports.download', $report) }}"
                   class="rounded-lg border border-ink-line px-3 py-1.5 text-sm text-chrome transition hover:border-brand">
                    Pobierz plik JSON
                </a>
            </div>
        </div>

        <p id="payload-hint" class="px-5 py-6 text-sm text-muted">
            Raport waży {{ number_format($report->payload_bytes / 1024, 1, ',', ' ') }} kB i jest wczytywany dopiero
            na żądanie, żeby nie obciążać tej strony.
        </p>

        <pre id="payload" hidden class="overflow-x-auto px-5 py-5 font-mono text-xs leading-relaxed text-muted"></pre>
    </section>

    <script>
        document.getElementById('load-payload').addEventListener('click', async function () {
            const target = document.getElementById('payload');
            const hint = document.getElementById('payload-hint');

            this.disabled = true;
            this.textContent = 'Wczytywanie...';

            try {
                const response = await fetch(@json(route('panel.reports.payload', $report)));
                const document_ = await response.json();

                target.textContent = JSON.stringify(document_, null, 2);
                target.hidden = false;
                hint.hidden = true;
                this.hidden = true;
            } catch (error) {
                this.disabled = false;
                this.textContent = 'Spróbuj ponownie';
                hint.textContent = 'Nie udało się wczytać treści raportu.';
            }
        });
    </script>
@endsection
