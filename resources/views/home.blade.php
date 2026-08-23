<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pensec - badanie bezpieczeństwa sieci od środka</title>
    <meta name="description" content="Pensec bada bezpieczeństwo sieci od środka. Sondy Raspberry Pi umieszczone w sieci przeprowadzają pełne badanie lokalnie i przekazują jeden kompletny raport.">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta property="og:title" content="Pensec - badanie bezpieczeństwa sieci od środka">
    <meta property="og:description" content="Sondy Raspberry Pi umieszczone wewnątrz sieci badają jej bezpieczeństwo i przekazują kompletny raport.">
    <meta property="og:image" content="{{ url('/images/pensec-logo.webp') }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pl_PL">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="antialiased">

    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 backdrop-glow"></div>
        <div class="pointer-events-none absolute inset-0 backdrop-grid"></div>

        <div class="relative mx-auto flex max-w-6xl justify-end px-6 pt-6">
            @include('partials.theme-toggle')
        </div>

        <header class="relative mx-auto max-w-6xl px-6 pt-14 pb-20 text-center sm:pt-20">
            <img src="/images/pensec-logo.webp" alt="Pensec" width="900" height="889"
                 class="theme-when-dark logo-glow mx-auto w-64 sm:w-80">
            <img src="/images/pensec-logo-light.webp" alt="Pensec" width="900" height="889"
                 class="theme-when-light logo-glow mx-auto w-64 sm:w-80">

            <p class="mt-10 text-xs uppercase tracking-[0.35em] text-brand">Security beyond the surface</p>

            <h1 class="mt-6 text-balance text-4xl font-semibold leading-tight chrome-text sm:text-6xl">
                Twoja sieć widziana od środka
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-balance text-lg leading-relaxed text-muted">
                Skan z zewnątrz pokazuje tylko to, co sieć wystawia na świat. Pensec bada ją stamtąd, skąd
                naprawdę widać ryzyko: sonda Raspberry Pi umieszczona wewnątrz sieci przeprowadza pełne badanie
                lokalnie i przekazuje jeden kompletny raport.
            </p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-3 text-sm">
                <span class="rounded-full border border-ink-line bg-ink-raised px-4 py-1.5 text-muted">Sondy Raspberry Pi</span>
                <span class="rounded-full border border-ink-line bg-ink-raised px-4 py-1.5 text-muted">Jeden raport na badanie</span>
                <span class="rounded-full border border-ink-line bg-ink-raised px-4 py-1.5 text-muted">Pełny zapis wyników</span>
            </div>
        </header>
    </div>

    <main class="relative mx-auto max-w-6xl px-6 pb-28">

        <section class="border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Przebieg badania</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                Nic nie opuszcza sondy, dopóki badanie nie dobiegnie końca.
            </p>

            <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['01', 'Sonda się uruchamia', 'Urządzenie umieszczone w badanej sieci rozpoczyna pracę po włączeniu. Nikt nie musi się na nie logować ani niczego uruchamiać ręcznie.'],
                    ['02', 'Badanie dostaje tożsamość', 'Zanim ruszy pierwszy test, sonda nadaje temu badaniu własny, niepowtarzalny identyfikator.'],
                    ['03', 'Wszystko dzieje się lokalnie', 'Wykrywanie urządzeń, skanowanie i diagnostyka wykonują się na samej sondzie. W trakcie badania nic nie jest wysyłane na zewnątrz.'],
                    ['04', 'Powstaje jeden dokument', 'Wyniki wszystkich modułów trafiają do pojedynczego raportu gromadzonego lokalnie na sondzie.'],
                    ['05', 'Sonda przekazuje wyniki', 'Po zakończeniu wszystkich testów, uwierzytelniając się własnym poświadczeniem, sonda przesyła kompletny raport.'],
                    ['06', 'Raport zostaje zachowany bez zmian', 'System rozpoznaje sondę i konkretne badanie, po czym zapisuje raport dokładnie w takiej postaci, w jakiej powstał.'],
                ] as [$step, $title, $body])
                    <li class="card p-6">
                        <span class="font-mono text-sm text-brand">{{ $step }}</span>
                        <h3 class="mt-3 text-lg font-semibold text-chrome">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $body }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="mt-28 border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Co bada sonda</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                Od otwartych portów po protokoły sterowania przemysłowego.
            </p>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Wykrywanie urządzeń', 'Przegląd segmentu sieci i lista wszystkich urządzeń, które się w nim odezwały.'],
                    ['Porty i usługi', 'Sprawdzenie, co nasłuchuje na każdym wykrytym urządzeniu i w jakiej wersji. Wynik zostaje zachowany w pełnej postaci, nie w formie skrótu.'],
                    ['Pogłębione testy podatności', 'Testy uruchamiane przeciwko znalezionym usługom, łącznie z podatnościami prowadzącymi do unieruchomienia usługi.'],
                    ['Skanowanie szablonami', 'Testy oparte na katalogu znanych podatności, uruchamiane przeciwko wszystkiemu, co odpowiedziało w sieci.'],
                    ['Ekspozycja SMB', 'Sesje anonimowe i udziały plików dostępne bez uwierzytelnienia, czyli klasyczna droga od dostępu gościa do dostępu do dokumentów.'],
                    ['Zatruwanie rozgłoszeń', 'Ruch przestarzałych mechanizmów rozwiązywania nazw i skróty haseł możliwe do przechwycenia w zadanym oknie pomiarowym.'],
                    ['Protokoły ICS i OT', 'Punkty końcowe sterowania przemysłowego, takie jak CODESYS Runtime, EtherNet/IP czy IEC 60870-5-104, które nie mają czego szukać w sieci biurowej.'],
                    ['Odciski usług', 'Sygnatury zbierane z poszczególnych portów, zapisywane w dwóch postaciach, żeby nic nie przepadło przy kodowaniu.'],
                    ['Diagnostyka sieci', 'Opóźnienia, kondycja DNS, podatność na podsłuch i na WPAD, podszywanie się w IPv6, obcy serwer DHCP, przeskakiwanie między VLAN-ami, filtrowanie ruchu wychodzącego, najbardziej obciążające urządzenia i pasmo.'],
                    ['Higiena poświadczeń', 'Pozostawione domyślne dane logowania oraz informacje z katalogu firmowego wyciekające do klientów anonimowych.'],
                ] as [$title, $body])
                    <div class="card p-6">
                        <h3 class="text-base font-semibold text-chrome">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-28 border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Zasady, na których to stoi</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                Wyniki badania są dowodem, więc traktujemy je jak dowód.
            </p>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                @foreach ([
                    ['Wyniki zachowujemy w całości', 'Zapisujemy dokładnie to, co zmierzyła sonda - bez skracania, filtrowania i podsumowywania. Późniejsze zmiany w sposobie prezentacji nigdy nie odbierają dostępu do tego, co faktycznie wykazało badanie.'],
                    ['Zerwane połączenie nie kosztuje wyników', 'Jeśli łączność urwie się w trakcie przesyłania, sonda ponawia wysyłkę. System rozpozna, że to wciąż to samo badanie, i nie zdubluje wyników.'],
                    ['Każda sonda odpowiada sama za siebie', 'Urządzenia uwierzytelniają się osobno, każde własnym poświadczeniem. Pojedynczą sondę można wyłączyć z systemu, nie ruszając pozostałych.'],
                    ['Historia badań nie nadpisuje się', 'Każde uruchomienie jest osobnym, zamkniętym badaniem, powiązanym z konkretną sondą i momentem wykonania. Nowy wynik nie zastępuje poprzedniego.'],
                ] as [$title, $body])
                    <div class="card p-6">
                        <h3 class="text-base font-semibold text-chrome">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-28 border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Stan prac</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                System powstaje etapami. To jest stan na dziś.
            </p>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-chrome">Działa</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-relaxed text-muted">
                        @foreach ([
                            'Odbieranie i bezpieczne przechowywanie raportów z sond',
                            'Rozpoznawanie sondy oraz pojedynczego badania',
                            'Odporność na zerwane połączenie i powtórną wysyłkę',
                            'Panel: lista sond, przeprowadzone badania i podgląd szczegółów',
                            'Raport PDF z badania w dwóch wersjach: technicznej i dla klienta',
                        ] as $item)
                            <li class="flex gap-3">
                                <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-chrome">Przed nami</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-relaxed text-muted">
                        @foreach ([
                            'Zestawianie kolejnych badań tej samej sieci',
                        ] as $item)
                            <li class="flex gap-3">
                                <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-ink-line"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

    </main>

    <footer class="border-t border-ink-line">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 px-6 py-10 text-sm text-muted sm:flex-row sm:justify-between">
            <div class="flex items-center gap-3">
                <img src="/images/pensec-mark.webp" alt="" width="512" height="590" class="theme-when-dark h-7 w-auto">
                <img src="/images/pensec-mark-light.webp" alt="" width="512" height="590" class="theme-when-light h-7 w-auto">
                <span class="font-semibold tracking-wide text-chrome">PENSEC</span>
            </div>
            <p>Security beyond the surface</p>
        </div>
    </footer>

</body>
</html>
