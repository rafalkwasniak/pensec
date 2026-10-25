{{-- Wspólna stopka stron publicznych: linki wewnętrzne (lepsze dla SEO i nawigacji)
     oraz logo i copyright. Jedno miejsce zamiast powtórzeń na każdej stronie. --}}
<footer class="border-t border-ink-line mt-auto">
    <div class="mx-auto max-w-6xl px-6 py-10">
        <nav class="flex flex-wrap items-center justify-center gap-6 text-sm text-muted">
            <a href="/jak-przebiega-audyt" class="transition-colors hover:text-chrome">Jak przebiega audyt</a>
            <a href="/wiedza" class="transition-colors hover:text-chrome">Baza wiedzy</a>
            <a href="/polityka-prywatnosci" class="transition-colors hover:text-chrome">Polityka prywatności</a>
            @if (config('pensec.analytics.ga_id'))
                <button type="button" data-cookie-settings class="transition-colors hover:text-chrome">Ustawienia cookie</button>
            @endif
        </nav>
        <div class="mt-6 flex items-center justify-center gap-1.5 text-sm text-muted">
            <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
            <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
            <span class="font-semibold tracking-wide text-chrome">&copy;</span>
            <span class="font-semibold tracking-wide text-chrome">{{ date('Y') }}</span>
        </div>
    </div>
</footer>
