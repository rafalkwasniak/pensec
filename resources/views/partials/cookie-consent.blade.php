{{-- Panel akceptacji cookie. Samodzielny: własny skrypt, zgoda w localStorage.
     Spójny z nawigacją (bg-ink-raised, backdrop-blur, tokeny, oba motywy).

     Bez PENSEC_GA_ID: tylko cookie niezbędne, jeden przycisk. Z identyfikatorem:
     wybór "tylko niezbędne" / "akceptuję wszystkie", a Google Analytics ładuje się
     wyłącznie po zgodzie. Zgoda "1" ze starej wersji banera dotyczyła tylko cookie
     niezbędnych, więc przy włączonej analityce pytanie pada ponownie. --}}
@php
    $gaId = config('pensec.analytics.ga_id');
    $gaId = is_string($gaId) && preg_match('/^G-[A-Z0-9]{4,20}$/', $gaId) && ! request()->is('panel*', 'portal*') ? $gaId : null;
@endphp
<div id="cookie-consent" class="fixed inset-x-0 bottom-0 z-50 hidden px-4 pb-4 sm:px-6">
    <div class="mx-auto flex max-w-3xl flex-col gap-4 rounded-xl border border-ink-line bg-ink-raised/90 px-5 py-4 shadow-lg backdrop-blur-md sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs leading-relaxed text-muted sm:text-sm">
            @if ($gaId)
                Serwis używa niezbędnych plików cookie, koniecznych do działania strony, oraz, wyłącznie za zgodą,
                plików cookie Google Analytics służących do statystyk odwiedzin.
            @else
                Serwis używa wyłącznie niezbędnych plików cookie, koniecznych do działania strony i utrzymania sesji. Bez śledzenia i profilowania.
            @endif
            <a href="/polityka-prywatnosci" class="text-brand hover:underline">Polityka prywatności</a>.
        </p>
        <div class="flex shrink-0 gap-3">
            @if ($gaId)
                <button data-consent="necessary" type="button" class="whitespace-nowrap rounded-md border border-ink-line px-4 py-2.5 text-sm font-medium text-muted transition-colors hover:border-brand hover:text-chrome">
                    Tylko niezbędne
                </button>
                <button data-consent="all" type="button" class="whitespace-nowrap rounded-md border border-ink-line bg-ink-raised px-4 py-2.5 text-sm font-medium text-chrome transition-colors hover:border-brand">
                    Akceptuję wszystkie
                </button>
            @else
                <button data-consent="1" type="button" class="whitespace-nowrap rounded-md border border-ink-line bg-ink-raised px-5 py-2.5 text-sm font-medium text-chrome transition-colors hover:border-brand">
                    Akceptuję
                </button>
            @endif
        </div>
    </div>
</div>
<script>
    (function () {
        var KEY = 'pensec.cookie-consent';
        var GA_ID = @json($gaId);
        var box = document.getElementById('cookie-consent');
        if (!box) return;

        function stored() {
            try { return localStorage.getItem(KEY); } catch (e) { return null; }
        }

        function loadAnalytics() {
            if (!GA_ID || window.gtag) return;
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', GA_ID);
            // A sent contact form is the conversion promotion is measured by.
            if (window.pensecLeadSent) window.gtag('event', 'generate_lead');
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA_ID);
            document.head.appendChild(s);
        }

        // Withdrawing consent: drop the Google cookies already set and reload
        // so the tag that is running is gone too.
        function dropAnalyticsCookies() {
            document.cookie.split(';').forEach(function (c) {
                var name = c.split('=')[0].trim();
                if (name.indexOf('_ga') !== 0) return;
                var host = location.hostname;
                [host, '.' + host, '.' + host.split('.').slice(-2).join('.')].forEach(function (d) {
                    document.cookie = name + '=; Max-Age=0; path=/; domain=' + d;
                });
                document.cookie = name + '=; Max-Age=0; path=/';
            });
        }

        var answer = stored();
        var decided = GA_ID ? (answer === 'all' || answer === 'necessary') : answer !== null;

        if (GA_ID && answer === 'all') loadAnalytics();
        if (!decided) box.classList.remove('hidden');

        box.querySelectorAll('[data-consent]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-consent');
                var previous = stored();
                try { localStorage.setItem(KEY, value); } catch (e) {}
                box.classList.add('hidden');
                if (value === 'all') loadAnalytics();
                if (value === 'necessary' && previous === 'all') { dropAnalyticsCookies(); location.reload(); }
            });
        });

        // The cookie-settings button in the footer reopens the choice.
        document.addEventListener('click', function (event) {
            if (event.target instanceof Element && event.target.closest('[data-cookie-settings]')) {
                event.preventDefault();
                box.classList.remove('hidden');
            }
        });
    })();
</script>
