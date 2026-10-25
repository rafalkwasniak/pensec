<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jak przebiega audyt bezpieczeństwa sieci | Pensec</title>
    <meta name="description" content="Przebieg audytu Pensec krok po kroku: umieszczenie urządzenia w sieci, badanie, raport menedżerski i techniczny, naprawa oraz bezpłatny audyt potwierdzający.">
    @include('partials.og', ['title' => 'Jak przebiega audyt bezpieczeństwa sieci | Pensec', 'description' => 'Przebieg audytu Pensec krok po kroku: umieszczenie urządzenia w sieci, badanie, raport menedżerski i techniczny, naprawa oraz bezpłatny audyt potwierdzający.'])
    <link rel="canonical" href="{{ url('/jak-przebiega-audyt') }}">
    <link rel="icon" type="image/png" href="/favicon.png">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- KONTENER NAWIGACJI -->
    <div class="relative z-50 w-full flex justify-center px-4 sm:px-6 mt-6 mb-10">
        <header class="flex min-w-0 max-w-full items-center gap-3 sm:gap-5 rounded-full border border-ink-line bg-ink-raised/70 px-4 sm:px-5 py-2 sm:py-2.5 shadow-lg backdrop-blur-md">
            <a href="/" class="group shrink-0 flex items-center hover:opacity-80 transition-opacity">
                <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
            </a>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <nav class="flex shrink min-w-0 items-center gap-4 sm:gap-5 overflow-x-auto hide-scrollbar">
                <a href="/tests" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome">Specyfikacja</a>
                <a href="/course" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome">Kurs</a>
                <a href="/contact" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome">Kontakt</a>
            </nav>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <div class="shrink-0 flex items-center">@include('partials.theme-toggle')</div>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <a href="/panel/reports" class="shrink-0 text-xs sm:text-sm font-medium text-muted hover:text-chrome transition-colors whitespace-nowrap">Panel</a>
        </header>
    </div>

    <main class="flex-grow w-full mx-auto max-w-4xl px-6 py-10" style="margin-top: 30px;">
        <p class="text-sm uppercase tracking-[0.25em] text-brand">Proces</p>
        <h1 class="mt-4 text-3xl font-semibold chrome-text sm:text-4xl">Jak przebiega audyt</h1>
        <p class="mt-5 max-w-2xl text-base leading-relaxed text-muted">
            Badanie prowadzi urządzenie umieszczone wewnątrz sieci klienta. Testy penetracyjne
            wykonywane są lokalnie i automatycznie, a wynikiem są dokumenty z uszeregowanymi
            zaleceniami naprawy. Poniżej opisano kolejne etapy współpracy.
        </p>

        <!-- KROKI -->
        <div class="mt-12 grid gap-4 sm:grid-cols-2">
            @foreach ([
                ['01', 'Przygotowanie i zakres', 'Przed startem ustalany jest zakres badania, zasady prowadzenia prac (Rules of Engagement) oraz poufność (NDA). Dopiero po tych uzgodnieniach urządzenie trafia do sieci.'],
                ['02', 'Badanie w sieci', 'Po inicjalizacji w sieci urządzenie wykonuje zautomatyzowane testy: rozpoznanie hostów, enumerację usług, testy podatności, wykrywanie infrastruktury przemysłowej i diagnostykę. Ruch pozostaje wewnątrz sieci.'],
                ['03', 'Raporty', 'Wynik badania obejmuje raport menedżerski (ryzyko opisane językiem biznesu), raport techniczny (CVE, adresy, usługi) oraz osobny przewodnik naprawy krok po kroku dla technika.'],
                ['04', 'Naprawa i re-audyt', 'Naprawa błędów konfiguracyjnych i wdrożenie zabezpieczeń leżą po stronie klienta. Po ich zakończeniu przeprowadzany jest bezpłatny audyt potwierdzający neutralizację podatności oraz to, że zmiany nie otworzyły nowych.'],
            ] as [$num, $title, $desc])
                <div class="card p-6">
                    <span class="font-mono text-brand text-lg font-bold">{{ $num }}</span>
                    <h2 class="mt-3 text-lg font-semibold text-chrome">{{ $title }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $desc }}</p>
                </div>
            @endforeach
        </div>

        <!-- ZASADY -->
        <div class="card mt-10 p-6 sm:p-8">
            <h2 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">Zasady badania</h2>
            <ul class="mt-6 space-y-3 text-sm leading-relaxed text-muted">
                <li class="flex gap-3 items-start"><span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span><span>Prace prowadzone są w oparciu o uzgodniony zakres i standard Rules of Engagement (RoE).</span></li>
                <li class="flex gap-3 items-start"><span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span><span>Poufność zabezpiecza umowa o zachowaniu poufności (NDA) zawierana przed startem.</span></li>
                <li class="flex gap-3 items-start"><span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span><span>Audyt potwierdzający po naprawie jest bezpłatny.</span></li>
            </ul>
        </div>

        <div class="mt-12 flex justify-center border-t border-ink-line pt-6">
            <a href="/contact" class="inline-block rounded-md border border-ink-line bg-ink-raised px-6 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors">
                Zamów audyt
            </a>
        </div>
    </main>

    @include('partials.site-footer')

    @include('partials.cookie-consent')
</body>
</html>
