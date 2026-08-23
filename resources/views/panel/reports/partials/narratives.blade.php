@php
    use App\Enums\NarrativeVariant;
@endphp

{{--
    Both PDFs, side by side. Generation runs on the queue, so a row that is
    working polls its own status endpoint and reloads the page once the job
    lands - the markup here stays the single description of every state.
--}}
<section class="card mt-8 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-chrome">Raporty PDF</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                Oba dokumenty powstają z tego samego badania. Liczby, urządzenia i porty pochodzą
                wprost z zapisanego raportu; różni je język opisu.
            </p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach (NarrativeVariant::cases() as $variant)
            @php
                $narrative = $report->narrative($variant);
                $status = $narrative->status;
            @endphp

            <div class="rounded-xl border border-ink-line p-5" @if ($status->inProgress()) data-narrative="{{ $variant->value }}" @endif>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-chrome">{{ $variant->heading() }}</h3>
                        <p class="mt-1 text-xs leading-relaxed text-muted">
                            {{ $variant === NarrativeVariant::Expert
                                ? 'Język techniczny, dla administratora sieci.'
                                : 'Prosty język, dla osoby nietechnicznej.' }}
                        </p>
                    </div>

                    <span class="shrink-0 rounded-full border px-3 py-1 text-xs
                        {{ match (true) {
                            $narrative->isReady() => 'border-brand/40 bg-brand/10 text-brand',
                            $status->inProgress() => 'border-ink-line text-muted',
                            default => 'border-warn-line text-warn',
                        } }}">
                        {{ $narrative->exists ? $status->label() : 'Niewygenerowany' }}
                    </span>
                </div>

                @if ($narrative->failure_reason)
                    <p class="mt-3 text-xs leading-relaxed text-warn">{{ $narrative->failure_reason }}</p>
                @endif

                @if ($narrative->isReady() && $narrative->generated_at)
                    <p class="mt-3 text-xs text-muted">
                        Wygenerowany {{ $narrative->generated_at->format('Y-m-d H:i') }}
                        @if ($narrative->model)
                            &middot; {{ $narrative->model }}
                        @endif
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    @if ($narrative->isReady())
                        <a href="{{ route('panel.reports.narrative.pdf', [$report, $variant->value]) }}"
                           class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-ink transition hover:bg-brand/90">
                            {{ $variant->label() }}
                        </a>

                        <form method="POST" action="{{ route('panel.reports.narrative.regenerate', [$report, $variant->value]) }}"
                              onsubmit="return confirm('Wygenerować ten raport od nowa? Obecna treść zostanie zastąpiona.')">
                            @csrf
                            <button type="submit" class="text-sm text-muted transition hover:text-chrome">
                                Wygeneruj od nowa
                            </button>
                        </form>
                    @elseif ($status->inProgress() && $narrative->exists)
                        <span class="text-sm text-muted">Generowanie w toku, strona odświeży się sama&hellip;</span>
                    @else
                        <form method="POST" action="{{ route('panel.reports.narrative.store', [$report, $variant->value]) }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-lg border border-ink-line px-4 py-2 text-sm text-chrome transition hover:border-brand">
                                {{ $narrative->exists ? 'Spróbuj ponownie' : 'Wygeneruj' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

@if ($report->narratives->contains(fn ($narrative) => $narrative->status->inProgress()))
    <script>
        (function () {
            var endpoints = @json($report->narratives
                ->filter(fn ($narrative) => $narrative->status->inProgress())
                ->map(fn ($narrative) => route('panel.reports.narrative.status', [$report, $narrative->variant->value]))
                ->values());

            // Poll rather than push: a report is generated once in a while, and
            // one request every few seconds costs less than keeping a socket open.
            var timer = setInterval(async function () {
                for (const endpoint of endpoints) {
                    try {
                        const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}});
                        const state = await response.json();

                        if (! state.in_progress) {
                            clearInterval(timer);
                            window.location.reload();

                            return;
                        }
                    } catch (error) {
                        // A dropped poll is not worth acting on; the next one will tell us.
                    }
                }
            }, 4000);
        })();
    </script>
@endif
