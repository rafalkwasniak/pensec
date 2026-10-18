@php($group = $facts['coverage']['ics_ot'] ?? null)
@php($client = ($variant ?? null) === App\Enums\NarrativeVariant::Client)
@include('pdf.partials._pokrycie', ['group' => $group])

@if ($facts['ics_endpoints'] === [] && (($group['problem'] ?? false) && ! $group['ran']))
    {{-- Not run: the note above says so, and "nothing detected" would contradict it. --}}
@elseif ($facts['ics_endpoints'] === [])
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
                    <td class="mono">{{ $client ? App\Support\ProbeLabels::term($endpoint['state'] ?? '—') : ($endpoint['state'] ?? '—') }}</td>
                    <td>
                        <span class="tag {{ ($endpoint['severity'] ?? '') === 'INCONCLUSIVE' ? 'tag-calm' : 'tag-warn' }}">
                            {{ $client ? App\Support\ProbeLabels::term($endpoint['severity'] ?? 'brak oceny') : ($endpoint['severity'] ?? 'brak oceny') }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php($hasOpenFiltered = collect($facts['ics_endpoints'])->contains(fn ($e) => ($e['state'] ?? '') === 'open|filtered'))
    <p style="font-size:9px; color:#667a96;">
        Punktów końcowych ICS/OT: <strong>{{ $totals['ics_endpoints'] }}</strong>.
        @if ($hasOpenFiltered)
            Stan <span class="mono">open|filtered</span> oznacza, że sonda nie mogła rozstrzygnąć,
            czy port jest otwarty, czy odfiltrowany.
        @endif
    </p>
@endif

@if ($facts['fingerprints'] !== [])
    <p style="font-size:9px; color:#667a96; margin-top:10px;">
        Sonda zebrała dodatkowo <strong>{{ count($facts['fingerprints']) }}</strong>
        {{ App\Support\Polish::plural(count($facts['fingerprints']), 'odcisk usługi', 'odciski usług', 'odcisków usług') }}.
        Pełna postać odcisków znajduje się w zapisanym raporcie źródłowym.
    </p>
@endif
