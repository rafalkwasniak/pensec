@php
    // Findings that came back clean are counted but not listed one by one - a
    // page of "nothing found" buries the handful that matter. The count stays
    // visible so nobody mistakes the short list for the whole test run.
    $notable = array_values(array_filter($facts['deep_findings'], fn ($f) => $f['notable']));
    $quiet = $totals['deep_findings'] - $totals['deep_findings_notable'];

    // The expert report keeps the evidence whole - that is what makes it usable
    // as proof. In the client report the same finding is present but trimmed:
    // a page of dumped HTML tells a non-technical reader nothing and buries the
    // sentence that matters.
    $evidence = $variant === App\Enums\NarrativeVariant::Expert ? 700 : 200;

    // A table past this length stops being read. The remainder is counted, not
    // dropped silently, and stays in the stored report.
    $listed = array_slice($notable, 0, 40);

    $group = $facts['coverage']['vulnerability_scanning'] ?? null;
@endphp

@include('pdf.partials._pokrycie', ['group' => $group])

@if ($facts['deep_findings'] === [] && (($group['problem'] ?? false) && ! $group['ran']))
    {{-- Not run: the note above says so, and "no findings" would contradict it. --}}
@elseif ($facts['deep_findings'] === [])
    <div class="empty">Pogłębione testy nie zwróciły żadnych ustaleń.</div>
@else
    @if ($notable === [])
        <div class="empty">
            Żaden z uruchomionych testów nie wykazał problemu - wszystkie zakończyły się wynikiem
            czystym (łącznie: {{ $totals['deep_findings'] }}).
        </div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:20%;">Urządzenie</th>
                    <th style="width:24%;">Test</th>
                    <th>Ustalenie</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($listed as $finding)
                    <tr>
                        <td class="mono">{{ $finding['ip'] }}</td>
                        <td class="mono">{{ $finding['name'] }}</td>
                        <td style="white-space: pre-wrap;">{{ Illuminate\Support\Str::limit($finding['output'], $evidence) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (count($notable) > count($listed))
            <div class="empty">
                Pokazano {{ count($listed) }} z {{ count($notable) }} ustaleń wymagających uwagi. Pozostałe
                znajdują się w pełnym raporcie źródłowym.
            </div>
        @endif
    @endif

    <p style="font-size:9px; color:#667a96;">
        Ustaleń łącznie: <strong>{{ $totals['deep_findings'] }}</strong>,
        w tym wymagających uwagi: <strong>{{ $totals['deep_findings_notable'] }}</strong>{{ $quiet > 0 ? ", pozostałe {$quiet} zakończyło się wynikiem czystym" : '' }}.
    </p>
@endif

@php
    // Database and web-fuzzing results do not come through NSE; they are their
    // own findings (ReportFacts), so this section lists them here too rather
    // than leaving them only in the ranked table.
    $deepExtra = array_values(array_filter(
        $facts['findings'],
        fn ($f) => in_array($f['source'], ['Bazy danych', 'Aplikacje webowe'], true),
    ));
@endphp

@if ($deepExtra !== [])
    <h3 style="font-size:12px; color:#0b1426; margin:16px 0 8px;">Bazy danych i aplikacje webowe</h3>
    <table class="data">
        <thead>
            <tr>
                <th style="width:13%;">Waga</th>
                <th style="width:20%;">Gdzie</th>
                <th>Ustalenie</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deepExtra as $finding)
                <tr>
                    <td><span class="tag {{ $finding['level'] === App\Support\Severity::INFO ? 'tag-calm' : 'tag-warn' }}">{{ App\Support\Severity::label($finding['level']) }}</span></td>
                    <td class="mono">{{ $finding['ip'] ?? 'cała sieć' }}</td>
                    <td>
                        {{ $finding['title'] }}
                        @if ($finding['note'])
                            <div style="color:#55647f;">{{ Illuminate\Support\Str::limit($finding['note'], $evidence) }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
