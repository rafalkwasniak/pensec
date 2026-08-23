@php
    // Four states, never collapsed into two. "Sprawdzono, czysto" and "test się
    // nie wykonał" look nothing alike to a reader and must not look alike here:
    // one is a result, the other is a gap in the badanie.
    $describe = function ($entry): string {
        $parts = [];

        foreach ((array) $entry as $key => $value) {
            if (is_scalar($value) && (string) $value !== '') {
                $parts[] = is_int($key) ? (string) $value : $key.': '.$value;
            }
        }

        return implode(', ', $parts);
    };
@endphp

<table class="data">
    <thead>
        <tr>
            <th style="width:34%;">Badany obszar</th>
            <th>Wynik</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($facts['exposure'] as $module)
            <tr>
                <td>{{ $module['label'] }}</td>
                <td>
                    @if (! $module['present'])
                        <span class="tag tag-warn">brak danych</span>
                        <div style="margin-top:4px; color:#55647f;">Sonda nie przekazała wyniku tego testu.</div>
                    @elseif ($module['failed'])
                        <span class="tag tag-warn">test nie wykonał się</span>
                        <div style="margin-top:4px; color:#55647f;">
                            Narzędzie zwróciło błąd na {{ count($module['errors']) }}
                            {{ count($module['errors']) === 1 ? 'urządzeniu' : 'urządzeniach' }}, więc ten obszar
                            pozostaje niesprawdzony.
                        </div>
                        @foreach (array_slice($module['errors'], 0, 3) as $error)
                            <div class="mono" style="margin-top:3px;">{{ Illuminate\Support\Str::limit($describe($error), 240) }}</div>
                        @endforeach
                    @elseif ($module['findings'] === [])
                        {{-- A check that ran and found nothing is a verdict, so it
                             reads as one. Never used for the states above. --}}
                        <span class="tag tag-calm">stan prawidłowy — nie stwierdzono podatności</span>
                    @else
                        <span class="tag tag-warn">{{ count($module['findings']) }} {{ count($module['findings']) === 1 ? 'ustalenie' : 'ustaleń' }}</span>
                        <div style="margin-top:5px;">
                            @foreach ($module['findings'] as $finding)
                                <div style="margin-bottom:3px;">{{ Illuminate\Support\Str::limit($describe($finding), 400) }}</div>
                            @endforeach
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if ($totals['modules_failed'] > 0)
    <div class="empty" style="border-left-color:#b45309;">
        <strong>{{ $totals['modules_failed'] }}</strong>
        {{ $totals['modules_failed'] === 1 ? 'moduł badania nie wykonał się' : 'moduły badania nie wykonały się' }}
        poprawnie. Wyniku tych testów nie należy czytać jako „nic nie znaleziono” - one się nie odbyły.
    </div>
@endif
