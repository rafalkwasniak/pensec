@php
    use App\Support\Severity;

    // Read-only analysis of one parsed report: severity tiles, headline totals,
    // flags, and a filterable findings table. Shared by the admin panel (inside
    // its parse controls) and the client portal (standalone). $filterAction is
    // the route name the filter form posts to (admin vs client).
    $filterAction ??= 'panel.reports.show';

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

{{-- Native selects render with the browser's own control and a light dropdown
     that clashes with the dark theme. Reset them (build-free) and draw a chevron;
     colours come from theme tokens, so the light theme re-points them too. --}}
<style>
    .analysis-field {
        appearance: none;
        -webkit-appearance: none;
        background-color: var(--color-ink);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='none' stroke='%238a97ab' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.65rem center;
        background-size: 0.85rem;
        padding-right: 2rem;
        cursor: pointer;
    }
    .analysis-field option {
        background-color: var(--color-ink-raised);
        color: var(--color-chrome);
    }
</style>

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

@if (($summary['personal_data'] ?? false) || $outcomeLabel !== null)
    <div class="mt-4 flex flex-wrap gap-2 text-xs">
        @if ($summary['personal_data'] ?? false)
            <span class="rounded-full border border-warn-line px-3 py-1 text-warn">Dane osobowe w sieci</span>
        @endif
        @if ($outcomeLabel !== null)
            <span class="rounded-full border border-ink-line px-3 py-1 text-muted">Przebieg: {{ $outcomeLabel }}</span>
        @endif
    </div>
@endif

{{-- Filter and search the findings. Shown only when the report has any. --}}
@if ($findingLevels->isNotEmpty() || $findingSources->isNotEmpty())
    <form method="GET" action="{{ route($filterAction, $report) }}"
          class="mt-8 flex flex-wrap items-end gap-3 border-t border-ink-line pt-6">
        <div>
            <label for="level" class="block text-xs text-muted">Waga</label>
            <select id="level" name="level" onchange="this.form.submit()"
                    class="analysis-field mt-1 rounded-lg border border-ink-line px-3 py-2 text-sm text-chrome outline-none focus:border-brand">
                <option value="">wszystkie</option>
                @foreach ($findingLevels as $level)
                    <option value="{{ $level }}" @selected($filterLevel === $level)>{{ Severity::label($level) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="source" class="block text-xs text-muted">Źródło</label>
            <select id="source" name="source" onchange="this.form.submit()"
                    class="analysis-field mt-1 rounded-lg border border-ink-line px-3 py-2 text-sm text-chrome outline-none focus:border-brand">
                <option value="">wszystkie</option>
                @foreach ($findingSources as $source)
                    <option value="{{ $source }}" @selected($filterSource === $source)>{{ $source }}</option>
                @endforeach
            </select>
        </div>
        <div class="grow">
            <label for="q" class="block text-xs text-muted">Szukaj (nazwa lub adres)</label>
            <input id="q" name="q" value="{{ $filterQuery }}" type="search" placeholder="np. telnet lub 192.168.0.1"
                   class="mt-1 w-full rounded-lg border border-ink-line bg-ink px-3 py-2 text-sm text-chrome outline-none focus:border-brand">
        </div>
        <button type="submit" class="rounded-lg border border-ink-line px-4 py-2 text-sm text-chrome transition hover:border-brand">Filtruj</button>
    </form>
@endif

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
