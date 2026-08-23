{{--
    The document. Rendered by dompdf, which means tables and inline styles - no
    flexbox, no grid, no external stylesheet.

    Every number on these pages comes from $facts, which was derived from the
    stored document. The model's prose is interleaved per section and is never
    the source of a figure. A section with no findings still gets its heading
    and says so, so a reader can tell "checked, nothing there" from "not checked".
--}}
@php
    use App\Services\NarrativePrompt;
    use Illuminate\Support\Str;

    $prose = NarrativePrompt::split($narrative->content ?? '');

    /** Model output is untrusted input; markdown is rendered with HTML escaped. */
    $markdown = fn (?string $text): string => $text
        ? Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false])
        : '';

    $totals = $facts['totals'];

    // Which ending the document gets is decided in ReportFacts from the
    // evidence, never by the model: a badanie with holes in its coverage does
    // not get the congratulatory version, because nobody can vouch for what was
    // not examined.
    $repairing = $facts['plan'] === 'repair';

    $sections = [
        [
            'key' => 'podsumowanie',
            'title' => 'Podsumowanie badania',
            'description' => 'Ocena ryzyka wynikająca z całości badania.',
        ],
        [
            'key' => 'wektory',
            'title' => 'Ustalenia według wagi',
            'description' => 'Wszystko, co badanie wykazało, zebrane w jednym miejscu i uszeregowane od najpoważniejszego. Wagi wynikają z tego, co stwierdziły testy - ustalenie, którego test nie zdołał potwierdzić, jest oceniane niżej niż potwierdzone.',
        ],
        [
            'key' => 'urzadzenia',
            'title' => 'Wykryte urządzenia',
            'description' => 'Przegląd segmentu sieci i lista wszystkich urządzeń, które się w nim odezwały. Urządzenie, które odpowiedziało przy wykrywaniu, ale zamilkło przy skanowaniu portów, jest odnotowane osobno.',
        ],
        [
            'key' => 'uslugi',
            'title' => 'Usługi dostępne w sieci',
            'description' => 'Sprawdzenie, co nasłuchuje na każdym wykrytym urządzeniu i w jakiej wersji. Wynik zostaje zachowany w pełnej postaci, nie w formie skrótu.',
        ],
        [
            'key' => 'podatnosci',
            'title' => 'Pogłębione testy podatności',
            'description' => 'Testy uruchamiane przeciwko znalezionym usługom. Ustalenie oznaczone jako wymagające uwagi to takie, które nie zakończyło się zwykłym „nic nie znaleziono”.',
        ],
        [
            'key' => 'ekspozycja',
            'title' => 'Ekspozycja i poświadczenia',
            'description' => 'Udziały plików dostępne bez uwierzytelnienia, ruch przestarzałych mechanizmów rozwiązywania nazw, pozostawione domyślne dane logowania oraz informacje wyciekające z katalogu firmowego do klientów anonimowych.',
        ],
        [
            'key' => 'ics',
            'title' => 'Protokoły przemysłowe ICS i OT',
            'description' => 'Punkty końcowe sterowania przemysłowego, takie jak CODESYS Runtime, EtherNet/IP czy IEC 60870-5-104, które zwykle nie mają czego szukać w sieci biurowej.',
        ],
        [
            'key' => 'diagnostyka',
            'title' => 'Diagnostyka sieci',
            // Deliberately does not list the individual tests. The probe decides
            // which diagnostics it sends, and naming a test that is not below
            // would read as "we checked that" when nobody did.
            'description' => 'Kondycja i konfiguracja sieci: opóźnienia, DNS, podatności na podsłuch i podszywanie się, zabezpieczenie portu dostępowego, ruch wychodzący oraz przepustowość. Poniżej znajdują się wyłącznie te testy, których wynik sonda przekazała.',
        ],
        [
            'key' => 'rekomendacje',
            'title' => $repairing ? 'Plan naprawy' : 'Plan utrzymania',
            'description' => $repairing
                ? 'Działania wynikające wyłącznie z ustaleń tego badania, uszeregowane od najpilniejszego.'
                : 'Badanie nie wykazało ustaleń wymagających działania ani luk w pokryciu. Poniżej to, co warto utrzymać.',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 42px 56px 42px; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2733;
            font-size: 11px;
            line-height: 1.6;
        }

        h1, h2, h3 { margin: 0; font-weight: bold; }

        p { margin: 0 0 8px 0; }

        ul { margin: 0 0 8px 16px; padding: 0; }
        li { margin-bottom: 4px; }

        strong { color: #0b1426; }

        .footer {
            position: fixed;
            bottom: -34px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #8a97ab;
        }

        .footer td { border-top: 1px solid #e2e8f2; padding-top: 5px; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.data th {
            text-align: left;
            font-size: 8px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #667a96;
            border-bottom: 1px solid #ccd6e8;
            padding: 5px 6px;
        }

        table.data td {
            padding: 5px 6px;
            border-bottom: 1px solid #eef2f9;
            vertical-align: top;
        }

        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; }

        .empty {
            background: #f5f8fd;
            border-left: 3px solid #ccd6e8;
            padding: 8px 10px;
            color: #55647f;
            margin-bottom: 10px;
        }

        .tag {
            font-size: 8px;
            padding: 1px 5px;
            border-radius: 6px;
            white-space: nowrap;
        }

        .tag-warn { background: #fdf0d5; color: #8a4b06; }
        .tag-calm { background: #eef2f9; color: #55647f; }
    </style>
</head>
<body>

<div class="footer">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="text-align:left;">Pensec &middot; {{ $variant->heading() }}</td>
            <td style="text-align:center;" class="mono">{{ $report->report_uid }}</td>
            <td style="text-align:right;">pensec.top</td>
        </tr>
    </table>
</div>

{{-- ============================ OKŁADKA ============================ --}}

<div style="text-align:center; padding-top:40px;">
    <img src="{{ public_path('images/pensec-logo-print.png') }}" alt="Pensec" style="width:240px;">
</div>

<div style="text-align:center; margin-top:26px;">
    <div style="font-size:22px; font-weight:bold; color:#0b1426;">{{ $variant->heading() }}</div>
    <div style="font-size:11px; color:#55647f; margin-top:5px;">Badanie bezpieczeństwa sieci od środka</div>
</div>

@php
    $critical = $facts['severity_counts'][App\Support\Severity::CRITICAL];

    $tiles = [
        ['Wykryte urządzenia', $totals['hosts_discovered'], '#0b62c4'],
        ['Otwarte porty', $totals['open_ports'], '#0b62c4'],
        ['Ustalenia wymagające działania', $facts['actionable'], match (true) {
            $critical > 0 => '#7f1d1d',
            $facts['actionable'] > 0 => '#b45309',
            default => '#0b62c4',
        }],
        ['Luki w pokryciu badania', count($facts['gaps']), count($facts['gaps']) > 0 ? '#b45309' : '#0b62c4'],
    ];
@endphp

<table style="width:86%; margin:30px auto 0 auto; border-collapse:separate; border-spacing:10px;">
    <tr>
        @foreach ($tiles as $index => [$label, $value, $color])
            <td style="width:50%; background:#f5f8fd; border:1px solid #e2e8f2; border-radius:8px; padding:14px; text-align:center;">
                <div style="font-size:26px; font-weight:bold; color:{{ $color }};">{{ $value }}</div>
                <div style="font-size:9px; color:#55647f; margin-top:3px;">{{ $label }}</div>
            </td>
            @if ($index % 2 === 1 && $index < 3)
                </tr><tr>
            @endif
        @endforeach
    </tr>
</table>

<table style="width:86%; margin:26px auto 0 auto; border-collapse:collapse; font-size:10px;">
    @foreach ([
        'Sonda' => $report->device->name,
        'Identyfikator badania' => $report->report_uid,
        'Data wykonania badania' => $facts['scan']['performed_at'] ?? '-',
        'Adres sondy w badanej sieci' => $facts['scan']['orchestrator_ip'] ?? '-',
        'Raport odebrany' => $report->received_at->format('Y-m-d H:i:s').' UTC',
    ] as $label => $value)
        <tr>
            <td style="padding:4px 0; color:#667a96; width:44%;">{{ $label }}</td>
            <td style="padding:4px 0; color:#0b1426;" class="{{ $label === 'Identyfikator badania' ? 'mono' : '' }}">{{ $value }}</td>
        </tr>
    @endforeach
</table>

{{-- The summary used to sit here, but two paragraphs of prose overflowed the
     cover and left a near-empty page behind it. It has its own section now. --}}

{{-- ============================ SEKCJE ============================ --}}

@foreach ($sections as $section)
    <div style="page-break-before: always;"></div>

    <div style="border-left:5px solid #0b62c4; padding:2px 0 2px 12px; margin-bottom:12px;">
        <h2 style="font-size:16px; color:#0b1426;">{{ $loop->iteration }}. {{ $section['title'] }}</h2>
    </div>

    <div style="background:#f5f8fd; border-left:3px solid #ccd6e8; padding:9px 12px; font-size:10px; color:#55647f; margin-bottom:14px;">
        {{ $section['description'] }}
    </div>

    @include('pdf.partials.'.$section['key'], ['facts' => $facts, 'totals' => $totals, 'variant' => $variant])

    @if (isset($prose[$section['key']]))
        {!! $markdown($prose[$section['key']]) !!}
    @else
        <div class="empty">Do tej sekcji nie powstał opis. Ustalenia powyżej pochodzą wprost z zapisanego raportu.</div>
    @endif

    @if ($section['key'] === 'rekomendacje')
        {{-- Written by the template, not asked of the model, so it appears on
             every report word for word and always matches the ending the
             evidence chose. --}}
        <div style="border-top:1px solid #e2e8f2; margin-top:12px; padding-top:10px;">
            &bull;
            {{ $repairing
                ? 'Ponowne przeprowadzenie audytu weryfikującego przy pomocy sprzętowego analizatora w celu technicznego potwierdzenia skuteczności wdrożonych zabezpieczeń.'
                : 'Okresowe wykonywanie zautomatyzowanych audytów bezpieczeństwa w celu utrzymania wysokiego standardu higieny cyfrowej.' }}
        </div>
    @endif
@endforeach

</body>
</html>
