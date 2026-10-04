@php
    // Four states, never collapsed into two. "Sprawdzono, czysto" and "test się
    // nie wykonał" look nothing alike to a reader and must not look alike here:
    // one is a result, the other is a gap in the badanie. A clean result from a
    // test that ran only on some devices is a fifth, and says so.
    use App\Support\Polish;
    use App\Support\ResultText;

    // Per module; a table of hundreds of informational rows buries the one that
    // matters. The count above the list is always the full one.
    $limit = 15;
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
                            @if ($module['errors'] !== [])
                                Test nie dał wyniku w {{ count($module['errors']) }}
                                {{ count($module['errors']) === 1 ? 'próbie' : 'próbach' }}, więc ten obszar
                                pozostaje niesprawdzony. Na przykład:
                            @else
                                Test nie został uruchomiony, więc ten obszar pozostaje niesprawdzony.
                            @endif
                        </div>
                        @foreach (array_slice($module['errors'], 0, 3) as $error)
                            <div class="mono" style="margin-top:3px;">{{ Illuminate\Support\Str::limit(ResultText::describe($error), 240) }}</div>
                        @endforeach
                    @elseif ($module['not_applicable'] ?? false)
                        <span class="tag tag-calm">nie dotyczy</span>
                        <div style="margin-top:4px; color:#55647f;">
                            Sonda pominęła ten test, bo w sieci nie było usług, których dotyczy.
                        </div>
                    @elseif ($module['findings'] === [])
                        {{-- A check that ran and found nothing is a verdict, so it
                             reads as one. Never used for the states above. --}}
                        @if ($module['partial'] ?? false)
                            <span class="tag tag-calm">nie stwierdzono podatności w sprawdzonym zakresie</span>
                        @else
                            <span class="tag tag-calm">stan prawidłowy — nie stwierdzono podatności</span>
                        @endif
                    @else
                        <span class="tag tag-warn">{{ Polish::count(count($module['findings']), 'ustalenie', 'ustalenia', 'ustaleń') }}</span>
                        <div style="margin-top:5px;">
                            @foreach (array_slice($module['findings'], 0, $limit) as $finding)
                                <div style="margin-bottom:3px;">{{ Illuminate\Support\Str::limit(ResultText::describe($finding), 400) }}</div>
                            @endforeach
                            @if (count($module['findings']) > $limit)
                                <div style="color:#8a97ab;">… oraz {{ count($module['findings']) - $limit }} kolejnych, w pełnym raporcie źródłowym.</div>
                            @endif
                        </div>
                    @endif

                    @if (($module['partial'] ?? false) && ! $module['failed'])
                        <div style="margin-top:4px; color:#8a4b06;">
                            Test nie objął całej sieci{{ ($module['missed_hosts'] ?? []) !== [] ? ' - nie powiódł się na '.count($module['missed_hosts']).' '.(count($module['missed_hosts']) === 1 ? 'urządzeniu' : 'urządzeniach') : '' }},
                            więc wynik dotyczy tylko sprawdzonej części.
                        </div>
                    @endif

                    @if (($module['observations'] ?? 0) > 0)
                        {{-- Grouped non-security extractor hits (clock times, hex
                             colours). Counted context, never vulnerabilities. --}}
                        <div style="margin-top:4px; color:#8a97ab;">
                            Dodatkowo {{ App\Support\Polish::count($module['observations'], 'techniczna obserwacja', 'techniczne obserwacje', 'technicznych obserwacji') }}
                            narzędzia (np. znaczniki czasu) - nie są to podatności.
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if ($totals['modules_failed'] > 0)
    <div class="empty" style="border-left-color:#b45309;">
        {{ Polish::plural($totals['modules_failed'], 'Jeden moduł badania nie wykonał się', $totals['modules_failed'].' moduły badania nie wykonały się', $totals['modules_failed'].' modułów badania nie wykonało się') }}
        poprawnie. Wyniku tych testów nie należy czytać jako „nic nie znaleziono” - one się nie odbyły.
    </div>
@endif
