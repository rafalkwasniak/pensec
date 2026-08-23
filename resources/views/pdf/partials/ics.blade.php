@if ($facts['ics_endpoints'] === [])
    <div class="empty">Nie wykryto punktów końcowych protokołów przemysłowych.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th style="width:20%;">Urządzenie</th>
                <th style="width:13%;">Port</th>
                <th>Protokół</th>
                <th style="width:18%;">Stan</th>
                <th style="width:18%;">Ocena sondy</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facts['ics_endpoints'] as $endpoint)
                <tr>
                    <td class="mono">{{ $endpoint['ip'] }}</td>
                    <td class="mono">{{ $endpoint['port'] ?? '—' }}/{{ $endpoint['transport'] ?? '—' }}</td>
                    <td>{{ $endpoint['protocol'] ?? 'nieznany' }}</td>
                    <td class="mono">{{ $endpoint['state'] ?? '—' }}</td>
                    <td>
                        <span class="tag {{ ($endpoint['severity'] ?? '') === 'INCONCLUSIVE' ? 'tag-calm' : 'tag-warn' }}">
                            {{ $endpoint['severity'] ?? 'brak oceny' }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-size:9px; color:#667a96;">
        Punktów końcowych ICS/OT: <strong>{{ $totals['ics_endpoints'] }}</strong>.
        Stan <span class="mono">open|filtered</span> oznacza, że sonda nie mogła rozstrzygnąć,
        czy port jest otwarty, czy odfiltrowany.
    </p>
@endif

@if ($facts['fingerprints'] !== [])
    <p style="font-size:9px; color:#667a96; margin-top:10px;">
        Sonda zebrała dodatkowo <strong>{{ count($facts['fingerprints']) }}</strong>
        {{ count($facts['fingerprints']) === 1 ? 'odcisk usługi' : 'odcisków usług' }}.
        Pełna postać odcisków znajduje się w zapisanym raporcie źródłowym.
    </p>
@endif
