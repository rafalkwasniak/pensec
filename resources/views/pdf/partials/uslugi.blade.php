@if ($facts['services'] === [])
    <div class="empty">
        Żadne z wykrytych urządzeń nie wystawiło otwartego portu spośród badanych. To dobry wynik,
        ale dotyczy wyłącznie portów objętych tym skanowaniem.
    </div>
@else
    <table class="data">
        <thead>
            <tr>
                <th style="width:22%;">Urządzenie</th>
                <th style="width:14%;">Port</th>
                <th style="width:20%;">Usługa</th>
                <th>Wersja rozpoznana przez sondę</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facts['services'] as $service)
                <tr>
                    <td class="mono">{{ $service['ip'] }}</td>
                    <td class="mono">{{ $service['port'] }}/{{ $service['transport'] }}</td>
                    <td>{{ $service['service'] }}</td>
                    <td>{{ $service['version'] ?? 'nie ustalono' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-size:9px; color:#667a96;">
        Otwartych portów łącznie: <strong>{{ $totals['open_ports'] }}</strong>,
        na <strong>{{ $totals['hosts_with_open_ports'] }}</strong> urządzeniach.
    </p>
@endif
