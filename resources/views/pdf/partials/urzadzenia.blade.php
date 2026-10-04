@include('pdf.partials._pokrycie', ['group' => $facts['coverage']['port_scanning'] ?? null])

@if ($facts['hosts'] === [])
    <div class="empty">Skanowanie nie wykryło w tym segmencie żadnego urządzenia.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th style="width:22%;">Adres</th>
                <th style="width:22%;">Adres sprzętowy</th>
                <th>Producent i model</th>
                <th style="width:24%;">Stan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facts['hosts'] as $host)
                <tr>
                    <td class="mono">{{ $host['ip'] }}</td>
                    <td class="mono">{{ $host['mac'] ?? '—' }}</td>
                    <td>
                        {{ trim(($host['vendor'] ?? '').' '.($host['model'] ?? '')) ?: 'nieznany' }}
                        @if ($host['os'] ?? null)
                            <div style="color:#8a97ab;">{{ $host['os'] }}</div>
                        @endif
                    </td>
                    <td>
                        @if ($host['scan_failed'] ?? null)
                            {{-- Not the same as "did not answer": the scan itself broke. --}}
                            <span class="tag tag-warn">skanowanie nieudane</span>
                            <div style="color:#8a97ab;">{{ $host['scan_failed'] }}</div>
                        @elseif (! $host['scanned'])
                            <span class="tag tag-calm">nie skanowany</span>
                        @elseif (! $host['reachable'])
                            <span class="tag tag-warn">brak odpowiedzi</span>
                        @elseif ($host['open_ports'] === [])
                            <span class="tag tag-calm">bez otwartych portów</span>
                        @else
                            <span class="tag tag-warn">{{ App\Support\Polish::count(count($host['open_ports']), 'otwarty port', 'otwarte porty', 'otwartych portów') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-size:9px; color:#667a96;">
        Wykrytych urządzeń: <strong>{{ $totals['hosts_discovered'] }}</strong>,
        odpowiedziało na skanowanie portów: <strong>{{ $totals['hosts_reachable'] }}</strong>.
    </p>
@endif
