@php
    use App\Support\Severity;

    $colours = [
        Severity::CRITICAL => ['#7f1d1d', '#fdeaea'],
        Severity::HIGH => ['#8a4b06', '#fdf0d5'],
        Severity::MEDIUM => ['#5b4a1a', '#fbf6e4'],
        Severity::INFO => ['#55647f', '#eef2f9'],
    ];
@endphp

@if ($facts['findings'] === [])
    <div class="empty">
        Badanie nie wykazało ustaleń wymagających działania. Poszczególne testy i ich wyniki
        opisano w dalszych sekcjach.
    </div>
@else
    {{-- Weights come from App\Support\Severity, never from the model. --}}
    <table style="width:100%; border-collapse:separate; border-spacing:6px; margin-bottom:12px;">
        <tr>
            @foreach (Severity::ORDER as $level)
                @php ([$ink, $background] = $colours[$level]) @endphp
                <td style="width:25%; background:{{ $facts['severity_counts'][$level] > 0 ? $background : '#f5f8fd' }}; border:1px solid #e2e8f2; border-radius:6px; padding:9px; text-align:center;">
                    <div style="font-size:18px; font-weight:bold; color:{{ $facts['severity_counts'][$level] > 0 ? $ink : '#8a97ab' }};">
                        {{ $facts['severity_counts'][$level] }}
                    </div>
                    <div style="font-size:8px; color:#55647f;">{{ Severity::label($level) }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:13%;">Waga</th>
                <th style="width:20%;">Gdzie</th>
                <th>Ustalenie</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facts['findings'] as $finding)
                @php ([$ink, $background] = $colours[$finding['level']]) @endphp
                <tr>
                    <td>
                        <span class="tag" style="background:{{ $background }}; color:{{ $ink }};">
                            {{ Severity::label($finding['level']) }}
                        </span>
                    </td>
                    <td class="mono">
                        {{ $finding['ip'] ?? 'cała sieć' }}
                        @if ($finding['where'])
                            <div style="color:#8a97ab;">{{ $finding['where'] }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $finding['title'] }}
                        @if ($finding['cves'])
                            <div class="mono" style="color:#8a97ab;">{{ implode(', ', $finding['cves']) }}</div>
                        @endif
                        @if ($finding['note'])
                            <div style="color:#55647f;">{{ $finding['note'] }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if ($facts['gaps'] !== [])
    {{-- Deliberately not merged into the table above. A test that never ran is
         not a mild finding; sorted among them it would sit below real problems,
         when it is exactly what hides them. --}}
    <div style="background:#fdf6ea; border-left:3px solid #b45309; padding:9px 12px; margin-top:12px;">
        <div style="font-weight:bold; color:#8a4b06; margin-bottom:5px;">
            Luki w pokryciu badania ({{ count($facts['gaps']) }})
        </div>
        <div style="color:#55647f; margin-bottom:6px;">
            Poniższe testy nie dostarczyły wyniku. Tych obszarów badanie nie sprawdziło, więc nie
            można ich uznać ani za bezpieczne, ani za zagrożone.
        </div>
        @foreach ($facts['gaps'] as $gap)
            <div style="margin-bottom:2px;">
                &bull; {{ $gap['ip'] ? $gap['ip'].' — ' : '' }}{{ $gap['title'] }}
            </div>
        @endforeach
    </div>
@endif
