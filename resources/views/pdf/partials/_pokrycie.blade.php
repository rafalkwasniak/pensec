{{--
    Says, above a section's results, when the test behind them did not run in
    full. Without it an empty table reads as "nothing there", and on a run where
    the test never started that is exactly the wrong conclusion.

    $group: one entry of $facts['coverage'], or null when the probe did not say.
--}}
@if (($group['problem'] ?? false) === true)
    @php
        $hosts = $group['hosts'] ?? [];
        $shown = array_slice($hosts, 0, 10);
    @endphp
    <div class="empty" style="background:#fdf6ea; border-left-color:#b45309; color:#8a4b06;">
        @if (! $group['ran'])
            Ten test się nie odbył{{ in_array($group['status'], ['timeout', 'blocked'], true) ? ' ('.$group['phrase'].')' : '' }}. Brak wyników w tej części nie oznacza,
            że nie ma tu problemów - ten obszar pozostaje niesprawdzony.
        @elseif ($hosts !== [])
            Test nie powiódł się na {{ count($hosts) }} {{ count($hosts) === 1 ? 'urządzeniu' : 'urządzeniach' }}:
            <span class="mono">{{ implode(', ', $shown) }}</span>{{ count($hosts) > count($shown) ? ' i '.(count($hosts) - count($shown)).' innych' : '' }}.
            Wyniki poniżej dotyczą wyłącznie urządzeń, które udało się sprawdzić.
        @else
            {{ Illuminate\Support\Str::ucfirst($group['phrase'] ?? 'test wykonał się częściowo') }}, więc wyniki poniżej mogą być niepełne.
        @endif
    </div>
@endif
