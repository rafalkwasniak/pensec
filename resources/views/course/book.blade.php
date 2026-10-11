<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Książka | Pensec</title>
    <meta name="description" content="Książka o bazach danych autora kursu.">
    <link rel="icon" type="image/png" href="/favicon.png">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- NAWIGACJA (spójna z resztą strony) -->
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
                <a href="/contact" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-chrome font-semibold">Kontakt</a>
            </nav>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <div class="shrink-0 flex items-center">@include('partials.theme-toggle')</div>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <a href="/panel/reports" class="shrink-0 text-xs sm:text-sm font-medium text-muted hover:text-chrome transition-colors whitespace-nowrap">Panel</a>
        </header>
    </div>

    <main class="flex-grow w-full mx-auto max-w-6xl px-6 py-10" style="margin-top: 50px;">

        {{-- ============================================================
             STRONA-PODSTAWA. Uzupełnij pola oznaczone [UZUPEŁNIJ].
             Okładkę podmień na <img src="/images/ksiazka-okladka.webp" ...>.
             ============================================================ --}}

        <!-- HERO: okładka + opis -->
        <section class="grid gap-10 lg:grid-cols-[2fr_3fr] items-center">

            <!-- OKŁADKA (placeholder do podmiany na obraz) -->
            <div class="mx-auto w-full max-w-xs">
                <div class="aspect-[3/4] rounded-lg border border-ink-line bg-ink-raised p-[2px] bg-gradient-to-b from-brand to-ink-raised">
                    <div class="flex h-full w-full flex-col items-center justify-center rounded-md bg-ink text-center px-6">
                        <span class="font-mono text-xs uppercase tracking-widest text-muted">Okładka</span>
                        <span class="mt-3 text-lg font-semibold text-chrome">[UZUPEŁNIJ tytuł]</span>
                    </div>
                </div>
            </div>

            <!-- OPIS -->
            <div>
                <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Książka</h2>
                <h1 class="mt-4 text-3xl sm:text-4xl font-semibold chrome-text leading-tight">[UZUPEŁNIJ tytuł książki]</h1>
                <p class="mt-6 text-base leading-relaxed text-muted max-w-2xl">
                    [UZUPEŁNIJ opis: czego dotyczy książka o bazach danych, jaki problem rozwiązuje i dla kogo jest przeznaczona. Dwa, trzy zdania.]
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#" class="rounded-md border border-ink-line bg-ink-raised px-5 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors">
                        [UZUPEŁNIJ odnośnik] Kup książkę
                    </a>
                    <a href="/course" class="rounded-md border border-transparent px-5 py-2.5 text-sm font-medium text-muted hover:text-chrome transition-colors">
                        Wróć do kursu
                    </a>
                </div>
            </div>
        </section>

        <!-- W KSIĄŻCE -->
        <section class="mt-24 border-t border-ink-line pt-16">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">W książce</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">[UZUPEŁNIJ jedno zdanie o zawartości.]</p>

            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                @foreach ([
                    ['[UZUPEŁNIJ]', '[UZUPEŁNIJ krótki opis punktu pierwszego.]'],
                    ['[UZUPEŁNIJ]', '[UZUPEŁNIJ krótki opis punktu drugiego.]'],
                    ['[UZUPEŁNIJ]', '[UZUPEŁNIJ krótki opis punktu trzeciego.]'],
                ] as [$title, $body])
                    <div class="card p-6">
                        <h3 class="text-base font-semibold text-chrome">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-muted">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- DLA KOGO -->
        <section class="mt-24 border-t border-ink-line pt-16">
            <div class="card p-6 sm:p-10">
                <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Dla kogo</h2>
                <p class="mt-4 text-base leading-relaxed text-muted max-w-3xl">
                    [UZUPEŁNIJ: do jakiego czytelnika kierowana jest książka i co zyska po lekturze.]
                </p>
            </div>
        </section>

    </main>

    <footer class="border-t border-ink-line mt-auto">
        <div class="mx-auto flex max-w-6xl items-center justify-center px-6 py-10 text-sm text-muted">
            <div class="flex items-center gap-1.5">
                <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
                <span class="font-semibold tracking-wide text-chrome">&copy;</span>
                <span class="font-semibold tracking-wide text-chrome">{{ date('Y') }}</span>
            </div>
        </div>
    </footer>


    @include('partials.cookie-consent')
</body>
</html>
