{{--
    The technician's repair guide. Rendered by dompdf: tables and inline styles.

    What to fix comes from $facts - the same ranked findings the reports show,
    in the same order and with the same weights. The model only wrote how to
    fix each one; a finding whose steps are missing still appears, so nothing
    the scan found can drop out of the guide.
--}}
@php
    use App\Services\RemediationPrompt;
    use App\Support\Severity;
    use Illuminate\Support\Str;

    $steps = RemediationPrompt::split($narrative->content ?? '');
    $findings = RemediationPrompt::actionable($facts);
    $skipped = count(array_filter($facts['findings'], fn ($f) => Severity::actionable($f['level']))) - count($findings);

    $hosts = [];
    foreach ($facts['hosts'] as $host) {
        $hosts[$host['ip']] = $host;
    }

    /** Model output is untrusted input; markdown is rendered with HTML escaped. */
    $markdown = fn (?string $text): string => $text
        ? Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false])
        : '';

    $levelInk = [
        Severity::CRITICAL => ['#7f1d1d', '#fdeaea'],
        Severity::HIGH => ['#8a4b06', '#fdf0d5'],
        Severity::MEDIUM => ['#5b4a1a', '#fbf6e4'],
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
        ol, ul { margin: 0 0 8px 18px; padding: 0; }
        li { margin-bottom: 5px; }
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

        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; }

        /* Commands the model writes in backticks are what a technician types. */
        code {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9px;
            background: #f1f4f9;
            padding: 0 3px;
        }

        .card {
            border: 1px solid #e2e8f2;
            border-radius: 8px;
            margin-bottom: 14px;
        }

        .card-head {
            background: #f5f8fd;
            border-bottom: 1px solid #e2e8f2;
            padding: 8px 12px;
        }

        .card-body { padding: 10px 12px 4px 12px; }

        .tag {
            font-size: 8px;
            font-weight: bold;
            padding: 1px 6px;
            border-radius: 6px;
            white-space: nowrap;
        }

        .notice {
            background: #fdf0d5;
            border-left: 3px solid #d99a2b;
            padding: 8px 10px;
            color: #5b3d06;
            font-size: 10px;
            margin: 0 0 16px 0;
        }

        .empty {
            background: #f5f8fd;
            border-left: 3px solid #ccd6e8;
            padding: 8px 10px;
            color: #55647f;
        }

        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

<div class="footer">
    <table style="width:100%; border-collapse:collapse; font-size: 10px; color: #6b7280;">
        <tr>
            <td style="text-align:left; width: 33%;">{{ $variant->heading() }}</td>
            <td style="text-align:center; width: 34%;" class="mono">{{ $report->report_uid }}</td>
            <td style="text-align:right; width: 33%;">Strona <span class="pagenum"></span></td>
        </tr>
    </table>
</div>

<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="vertical-align:middle;">
            <img src="{{ public_path('images/pensec-logo-print.png') }}" alt="Pensec" style="width:150px;">
        </td>
        <td style="vertical-align:middle; text-align:right;">
            <div style="font-size:18px; font-weight:bold; color:#0b1426;">Przewodnik naprawy</div>
            <div style="font-size:10px; color:#55647f;">Instrukcje dla technika, krok po kroku</div>
        </td>
    </tr>
</table>

<table style="width:100%; margin:14px 0 14px 0; border-collapse:collapse; font-size:10px;">
    @foreach ([
        'Sonda' => $report->device->name,
        'Identyfikator badania' => $report->report_uid,
        'Data wykonania badania' => $facts['scan']['performed_at'] ?? '-',
        'Usterek do naprawy' => count($findings),
    ] as $label => $value)
        <tr>
            <td style="padding:3px 0; color:#667a96; width:34%;">{{ $label }}</td>
            <td style="padding:3px 0; color:#0b1426;" class="{{ $label === 'Identyfikator badania' ? 'mono' : '' }}">{{ $value }}</td>
        </tr>
    @endforeach
</table>

<div class="notice">
    <strong>Przed wprowadzeniem zmian.</strong> Usterki, adresy i wersje oprogramowania pochodzą
    wprost z badania. Kroki naprawy zostały przygotowane z pomocą AI na ich podstawie - nazwy menu
    i opcji mogą różnić się w zależności od wersji oprogramowania urządzenia. Każdą zmianę
    zweryfikuj w panelu lub dokumentacji producenta, wykonaj kopię konfiguracji przed jej zmianą
    i wprowadzaj zmiany w oknie serwisowym. Usterki są ułożone od najpilniejszej.
</div>

@if ($findings === [])
    <div class="empty">
        Badanie nie wykazało usterek wymagających naprawy. Konfigurację należy utrzymać w obecnym
        stanie.
    </div>
@endif

@foreach ($findings as $index => $finding)
    @php
        $number = $index + 1;
        [$ink, $bg] = $levelInk[$finding['level']] ?? ['#55647f', '#eef2f9'];
        $host = $finding['ip'] !== null ? ($hosts[$finding['ip']] ?? null) : null;
        $device = $host !== null
            ? implode(', ', array_filter([$host['vendor'], $host['model'], $host['os']]))
            : '';
    @endphp

    <div class="card">
        <div class="card-head">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="vertical-align:top;">
                        <span style="font-size:12px; font-weight:bold; color:#0b1426;">{{ $number }}. {{ $finding['title'] }}</span>
                    </td>
                    <td style="vertical-align:top; text-align:right; width:90px;">
                        <span class="tag" style="color:{{ $ink }}; background:{{ $bg }};">{{ Severity::label($finding['level']) }}</span>
                    </td>
                </tr>
            </table>
            <div style="font-size:9px; color:#55647f; margin-top:3px;">
                <span class="mono" style="color:#0b1426;">{{ $finding['ip'] ?? 'cała sieć' }}</span>
                @if ($finding['where'])
                    · {{ $finding['where'] }}
                @endif
                @if ($device !== '')
                    · {{ $device }}
                @endif
                @if (! $finding['confirmed'])
                    · <span style="color:#8a4b06;">ustalenie niepotwierdzone - najpierw zweryfikuj</span>
                @endif
            </div>
            @if ($finding['note'])
                <div style="font-size:9px; color:#55647f; margin-top:3px;">{{ Str::limit($finding['note'], 300) }}</div>
            @endif
            @if ($finding['cves'] !== [])
                <div class="mono" style="color:#55647f; margin-top:3px;">{{ implode(', ', array_slice($finding['cves'], 0, 8)) }}</div>
            @endif
        </div>

        <div class="card-body">
            @if (isset($steps[$number]))
                {!! $markdown($steps[$number]) !!}
            @else
                <div class="empty" style="margin-bottom:8px;">
                    Kroki dla tej usterki nie zostały wygenerowane. Ustalenie pozostaje do naprawy -
                    wygeneruj przewodnik ponownie albo zaplanuj działanie na podstawie opisu powyżej.
                </div>
            @endif
        </div>
    </div>
@endforeach

@if ($skipped > 0)
    <p style="font-size:9px; color:#667a96;">
        Przewodnik obejmuje {{ count($findings) }} najpilniejszych usterek; pozostałe {{ $skipped }}
        są wymienione w raporcie eksperckim.
    </p>
@endif

@if ($findings !== [])
    <p style="font-size:10px; color:#55647f; margin-top:10px;">
        Po wprowadzeniu zmian zalecamy ponowne badanie sondą - potwierdzi ono, że usterki zostały
        usunięte, a zmiany nie otworzyły nowych.
    </p>
@endif

</body>
</html>
