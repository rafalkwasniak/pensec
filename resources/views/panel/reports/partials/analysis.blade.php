@php
    use App\Enums\ReportStatus;
    use App\Support\Severity;

    $status = $report->status;
    $summary = $report->summary ?? [];
    $counts = $summary['severity_counts'] ?? [];
    $totals = $summary['totals'] ?? [];
    $gaps = $summary['gaps'] ?? [];

    $outcomeLabel = match ($summary['scan']['outcome'] ?? null) {
        'operator_stop' => 'zatrzymane przez operatora',
        'completed_with_module_errors' => 'ukończone z błędami części testów',
        'completed' => 'ukończone',
        null => null,
        default => $summary['scan']['outcome'],
    };

    $levelClass = fn (string $level): string => match ($level) {
        Severity::CRITICAL => 'border-warn-line bg-warn-soft text-warn',
        Severity::HIGH => 'border-warn-line text-warn',
        default => 'border-ink-line text-muted',
    };
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

    <div class="mt-5 flex flex-wrap items-center gap-3">
        @if ($status === ReportStatus::Parsed)
            @if ($report->parsed_at)
                <span class="text-xs text-muted">Przeanalizowano {{ $report->parsed_at->format('Y-m-d H:i') }} UTC</span>
            @endif
            <form method="POST" action="{{ route('panel.reports.parse.reparse', $report) }}"
                  onsubmit="return confirm('Przeanalizować ten raport od nowa? Obecne ustalenia zostaną zastąpione.')">
                @csrf
                <button type="submit" class="text-sm text-muted transition hover:text-chrome">Przeanalizuj od nowa</button>
            </form>
        @elseif ($status->inProgress())
            <span class="text-sm text-muted">
                {{ $status === ReportStatus::Parsing ? 'Analiza w toku, strona odświeży się sama…' : 'Raport czeka w kolejce analizy, strona odświeży się sama…' }}
            </span>
        @else
            <form method="POST" action="{{ route('panel.reports.parse.store', $report) }}">
                @csrf
                <button type="submit"
                        class="rounded-lg border border-ink-line px-4 py-2 text-sm text-chrome transition hover:border-brand">
                    Spróbuj ponownie
                </button>
            </form>
        @endif
    </div>

    @if ($status === ReportStatus::Parsed)
        {{-- Severity tiles, then a few headline totals. Counts come from the stored summary. --}}
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach (Severity::ORDER as $level)
                <div class="rounded-xl border p-4 text-center {{ ($counts[$level] ?? 0) > 0 ? $levelClass($level) : 'border-ink-line text-muted' }}">
                    <div class="text-2xl font-semibold">{{ $counts[$level] ?? 0 }}</div>
                    <div class="mt-1 text-xs uppercase tracking-widest">{{ Severity::label($level) }}</div>
                </div>
            @endforeach
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            @foreach ([
                ['Urządzenia', $totals['hosts_discovered'] ?? 0],
                ['Otwarte porty', $totals['open_ports'] ?? 0],
                ['Testy pogłębione', $totals['deep_findings_notable'] ?? 0],
                ['Luki w pokryciu', count($gaps)],
            ] as [$label, $value])
                <div class="rounded-lg border border-ink-line p-3">
                    <dt class="text-xs uppercase tracking-widest text-muted">{{ $label }}</dt>
                    <dd class="mt-1 text-chrome">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            <span class="rounded-full border border-ink-line px-3 py-1 text-muted">
                Plan: {{ ($summary['plan'] ?? null) === 'repair' ? 'naprawa' : 'utrzymanie' }}
            </span>
            @if ($summary['personal_data'] ?? false)
                <span class="rounded-full border border-warn-line px-3 py-1 text-warn">Dane osobowe w sieci</span>
            @endif
            @if ($outcomeLabel !== null)
                <span class="rounded-full border border-ink-line px-3 py-1 text-muted">
                    Przebieg: {{ $outcomeLabel }}
                </span>
            @endif
        </div>

        {{-- Filter and search the findings. Reuses the GET-form pattern from the report list. --}}
        <form method="GET" action="{{ route('panel.reports.show', $report) }}"
              class="mt-6 flex flex-wrap items-end gap-3 border-t border-ink-line pt-5">
            <div>
                <label for="level" class="block text-xs text-muted">Waga</label>
                <select id="level" name="level" onchange="this.form.submit()"
                        class="mt-1 rounded-lg border border-ink-line bg-ink px-3 py-2 text-sm text-chrome outline-none focus:border-brand">
                    <option value="">wszystkie</option>
                    @foreach ($findingLevels as $level)
                        <option value="{{ $level }}" @selected($filterLevel === $level)>{{ Severity::label($level) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="source" class="block text-xs text-muted">Źródło</label>
                <select id="source" name="source" onchange="this.form.submit()"
                        class="mt-1 rounded-lg border border-ink-line bg-ink px-3 py-2 text-sm text-chrome outline-none focus:border-brand">
                    <option value="">wszystkie</option>
                    @foreach ($findingSources as $source)
                        <option value="{{ $source }}" @selected($filterSource === $source)>{{ $source }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grow">
                <label for="q" class="block text-xs text-muted">Szukaj (nazwa lub adres)</label>
                <input id="q" name="q" value="{{ $filterQuery }}" type="search"
                       class="mt-1 w-full rounded-lg border border-ink-line bg-ink px-3 py-2 text-sm text-chrome outline-none focus:border-brand">
            </div>
            <button type="submit" class="rounded-lg border border-ink-line px-4 py-2 text-sm text-chrome transition hover:border-brand">Filtruj</button>
        </form>

        @if ($findings->isEmpty())
            <p class="mt-6 text-sm text-muted">
                {{ $filterLevel || $filterSource || $filterQuery ? 'Żadne ustalenie nie pasuje do filtra.' : 'Badanie nie wykazało ustaleń wymagających działania.' }}
            </p>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-ink-line text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="px-3 py-3 font-medium">Waga</th>
                            <th class="px-3 py-3 font-medium">Gdzie</th>
                            <th class="px-3 py-3 font-medium">Ustalenie</th>
                            <th class="px-3 py-3 font-medium">Źródło</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($findings as $finding)
                            <tr class="border-b border-ink-line/60 last:border-0 align-top">
                                <td class="px-3 py-3">
                                    <span class="rounded-full border px-2.5 py-0.5 text-xs {{ $levelClass($finding->level) }}">{{ Severity::label($finding->level) }}</span>
                                </td>
                                <td class="px-3 py-3 font-mono text-xs text-muted">
                                    {{ $finding->ip ?? 'cała sieć' }}
                                    @if ($finding->where)
                                        <div class="text-muted/70">{{ $finding->where }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-chrome">
                                    {{ $finding->title }}
                                    @if ($finding->cves !== [])
                                        <div class="mt-1 font-mono text-xs text-muted">{{ implode(', ', array_slice($finding->cves, 0, 8)) }}{{ count($finding->cves) > 8 ? ' i '.(count($finding->cves) - 8).' innych' : '' }}</div>
                                    @endif
                                    @if (! $finding->confirmed)
                                        <div class="mt-1 text-xs text-warn">ustalenie niepotwierdzone</div>
                                    @endif
                                    @if ($finding->note)
                                        <div class="mt-1 text-xs text-muted">{{ $finding->note }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-muted">{{ $finding->source }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $findings->links() }}</div>
        @endif
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
