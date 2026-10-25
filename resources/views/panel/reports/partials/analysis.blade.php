@php
    use App\Enums\ReportStatus;

    $status = $report->status;
@endphp

<section class="card mt-8 p-6" @if ($status->inProgress()) data-parse="{{ route('panel.reports.parse.status', $report) }}" @endif>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-chrome">Analiza badania</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                Ustalenia i streszczenie wyprowadzone z zapisanego dokumentu. To ta sama analiza,
                która zasila raporty PDF, zapisana tutaj, żeby dało się ją przeszukać i filtrować.
                Dokument pozostaje nienaruszony.
            </p>
            @if ($status === ReportStatus::Parsed && $report->parsed_at)
                <p class="mt-2 text-xs text-muted">Przeanalizowano {{ $report->parsed_at->format('Y-m-d H:i') }} UTC</p>
            @endif
        </div>

        <span class="shrink-0 rounded-full border px-3 py-1 text-xs
            {{ match (true) {
                $status === ReportStatus::Parsed => 'border-brand/40 bg-brand/10 text-brand',
                $status->inProgress() => 'border-ink-line text-muted',
                default => 'border-warn-line text-warn',
            } }}">
            {{ $status->label() }}
        </span>
    </div>

    @if ($status === ReportStatus::Failed && $report->parse_failure_reason)
        <p class="mt-4 text-sm leading-relaxed text-warn">{{ $report->parse_failure_reason }}</p>
    @endif

    @if ($status->inProgress())
        <p class="mt-5 text-sm text-muted">
            {{ $status === ReportStatus::Parsing ? 'Analiza w toku, strona odświeży się sama…' : 'Raport czeka w kolejce analizy, strona odświeży się sama…' }}
        </p>
    @elseif ($status === ReportStatus::Failed)
        <div class="mt-5">
            <form method="POST" action="{{ route('panel.reports.parse.store', $report) }}">
                @csrf
                <button type="submit"
                        class="rounded-lg border border-ink-line px-4 py-2 text-sm text-chrome transition hover:border-brand">
                    Spróbuj ponownie
                </button>
            </form>
        </div>
    @endif

    @if ($status === ReportStatus::Parsed)
        @include('partials.report-analysis', ['filterAction' => 'panel.reports.show'])

        {{-- Re-parsing lives at the foot of the section, away from the content,
             the same place "Wygeneruj od nowa" sits in the PDF section. --}}
        <div class="mt-10 flex border-t border-ink-line pt-6">
            <form method="POST" action="{{ route('panel.reports.parse.reparse', $report) }}"
                  onsubmit="return confirm('Przeanalizować ten raport od nowa? Obecne ustalenia zostaną zastąpione.')">
                @csrf
                <button type="submit" class="text-sm text-muted transition hover:text-chrome">Przeanalizuj od nowa</button>
            </form>
        </div>
    @endif
</section>

@if ($status->inProgress())
    <script>
        (function () {
            var endpoint = @json(route('panel.reports.parse.status', $report));

            var timer = setInterval(async function () {
                try {
                    const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}});
                    const state = await response.json();

                    if (! state.in_progress) {
                        clearInterval(timer);
                        window.location.reload();
                    }
                } catch (error) {
                    // A dropped poll is not worth acting on; the next one will tell us.
                }
            }, 4000);
        })();
    </script>
@endif
