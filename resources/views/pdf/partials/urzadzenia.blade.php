@if ($facts['hosts'] === [])
    <div class="empty">Skanowanie nie wykryło w tym segmencie żadnego urządzenia.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th style="width:22%;">Adres</th>
                <th style="width:24%;">Adres sprzętowy</th>
                <th>Producent</th>
                <th style="width:26%;">Stan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facts['hosts'] as $host)
                <tr>
                    <td class="mono">{{ $host['ip'] }}</td>
                    <td class="mono">{{ $host['mac'] ?? '—' }}</td>
                    <td>{{ $host['vendor'] ?? 'nieznany' }}</td>
                    <td>
                        @if (! $host['scanned'])
                            <span class="tag tag-calm">nie skanowany</span>
                        @elseif (! $host['reachable'])
                            <span class="tag tag-warn">brak odpowiedzi</span>
                        @elseif ($host['open_ports'] === [])
                            <span class="tag tag-calm">bez otwartych portów</span>
                        @else
                            <span class="tag tag-warn">{{ count($host['open_ports']) }} otwart{{ count($host['open_ports']) === 1 ? 'y port' : 'e porty' }}</span>
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
