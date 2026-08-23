@php
    use App\Support\TsharkEndpoints;
@endphp

@if ($facts['diagnostics'] === [])
    <div class="empty">Badanie nie zawiera wyników diagnostycznych.</div>
@else
    @foreach ($facts['diagnostics'] as $diagnostic)
        <div style="margin-bottom:14px;">
            <div style="font-weight:bold; color:#0b1426; margin-bottom:5px;">{{ $diagnostic['label'] }}</div>

            @if ($diagnostic['error'])
                <div style="background:#fdf6ea; border-left:3px solid #b45309; padding:7px 10px; color:#8a4b06; margin-bottom:6px;">
                    Test nie wykonał się: {{ $diagnostic['error'] }}
                </div>
            @endif

            @if ($diagnostic['kind'] === 'talkers')
                {{-- Parsed out of tshark's console table; see App\Support\TsharkEndpoints. --}}
                <table class="data">
                    <thead>
                        <tr>
                            <th style="width:26%;">Adres</th>
                            <th style="width:14%;">Pakiety</th>
                            <th style="width:16%;">Ruch łącznie</th>
                            <th style="width:22%;">Wysłane</th>
                            <th style="width:22%;">Odebrane</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($diagnostic['rows'] as $row)
                            <tr>
                                <td class="mono">{{ $row['address'] }}</td>
                                <td>{{ $row['packets'] }}</td>
                                <td>{{ TsharkEndpoints::bytes($row['bytes']) }}</td>
                                <td>{{ $row['tx_packets'] }} pk &middot; {{ TsharkEndpoints::bytes($row['tx_bytes']) }}</td>
                                <td>{{ $row['rx_packets'] }} pk &middot; {{ TsharkEndpoints::bytes($row['rx_bytes']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif ($diagnostic['kind'] === 'fields')
                <table class="data">
                    <tbody>
                        @foreach ($diagnostic['fields'] as $field)
                            <tr>
                                @if ($field['label'])
                                    <td style="width:30%; color:#667a96;">{{ $field['label'] }}</td>
                                    <td style="{{ $field['concern'] ? 'color:#8a4b06;' : '' }}">
                                        {{ $field['concern'] ? '▲ ' : '' }}{{ $field['value'] }}
                                    </td>
                                @else
                                    <td colspan="2" style="{{ $field['concern'] ? 'color:#8a4b06;' : '' }}">
                                        {{ $field['concern'] ? '▲ ' : '' }}{{ $field['value'] }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="white-space: pre-wrap; color:#55647f;">{{ Illuminate\Support\Str::limit($diagnostic['text'], 600) }}</div>
            @endif
        </div>
    @endforeach
@endif
