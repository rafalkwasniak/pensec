<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kurs: audyt ekspozycji infrastruktury wewnętrznej | Pensec</title>
    <meta name="description" content="Kurs oceny ekspozycji infrastruktury wewnętrznej: mechanika protokołów, identyfikacja luk konfiguracyjnych i mechanizmy obronne, w ujęciu audytora.">
    @include('partials.og', ['title' => 'Kurs: audyt ekspozycji infrastruktury wewnętrznej | Pensec', 'description' => 'Kurs oceny ekspozycji infrastruktury wewnętrznej: mechanika protokołów, identyfikacja luk konfiguracyjnych i mechanizmy obronne, w ujęciu audytora.'])
    <link rel="canonical" href="{{ url('/course') }}">
    @php
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => 'Kurs manualnego audytu ekspozycji infrastruktury wewnętrznej',
            'description' => 'Kurs prowadzi przez ręczną ocenę ekspozycji infrastruktury wewnętrznej, od fundamentów sieciowych, przez rozpoznanie i enumerację usług, aż po bazy danych i raport.',
            'inLanguage' => 'pl',
            'provider' => [
                '@type' => 'Organization',
                'name' => 'Pensec',
                'url' => url('/'),
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <link rel="icon" type="image/png" href="/favicon.png">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- KONTENER NAWIGACJI -->
    <div class="relative z-50 w-full flex justify-center px-4 sm:px-6 mt-6 mb-10">
        
        <!-- NAWIGACJA -->
        <header class="flex min-w-0 max-w-full items-center gap-3 sm:gap-5 rounded-full border border-ink-line bg-ink-raised/70 px-4 sm:px-5 py-2 sm:py-2.5 shadow-lg backdrop-blur-md">
            
            <a href="/" class="group shrink-0 flex items-center hover:opacity-80 transition-opacity">
                <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
            </a>

            <div class="h-4 w-px shrink-0 bg-ink-line"></div>

            <nav class="flex shrink min-w-0 items-center gap-4 sm:gap-5 overflow-x-auto hide-scrollbar">
                <a href="/tests" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome">
                    Specyfikacja
                </a>
                <a href="/course" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium {{ request()->is('course*') ? 'text-chrome font-semibold' : 'text-muted hover:text-chrome' }}">
                    Kurs
                </a>
                {{-- Zakładka książki - odsłonić, gdy książka będzie gotowa (strona /course/ksiazka już istnieje).
                <a href="/course/ksiazka" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium {{ request()->is('course/ksiazka') ? 'text-chrome font-semibold' : 'text-muted hover:text-chrome' }}">
                    Książka
                </a>
                --}}
                <a href="/contact" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium {{ request()->is('contact') ? 'text-chrome font-semibold' : 'text-muted hover:text-chrome' }}">
                    Kontakt
                </a>
            </nav>
            
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            
            <div class="shrink-0 flex items-center">
                @include('partials.theme-toggle')
            </div>
            
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>

            <a href="/panel/reports" class="shrink-0 text-xs sm:text-sm font-medium text-muted hover:text-chrome transition-colors whitespace-nowrap">
                Panel
            </a>
            
        </header>
    </div>

    <main class="flex-grow w-full mx-auto max-w-6xl px-6 py-10" style="margin-top: 50px;">

        <!-- WPROWADZENIE I CERTYFIKACJA -->
        <section>
            <div class="card flex flex-col justify-between p-6">
                <div class="text-center">
                    <p class="text-sm uppercase tracking-[0.25em] text-brand">Wprowadzenie</p>
                    <h1 class="mt-4 text-2xl font-semibold chrome-text">Kurs Pentesterski</h1>
                    <p class="mt-4 text-sm leading-relaxed text-muted mx-auto max-w-3xl">
                        Kurs prowadzi przez ręczną ocenę ekspozycji infrastruktury wewnętrznej, od fundamentów sieciowych, przez rozpoznanie i enumerację usług, aż po bazy danych i raport. Nacisk pada na składnię poleceń, pracę w terminalu środowiska Linux oraz ostrożną interpretację wyników, odróżniającą obserwację od potwierdzonego ustalenia. Program odwzorowuje metodykę realnego audytu, ze szczególnym uwzględnieniem usług i baz danych. Zaliczenie testu końcowego pozwala zdobyć imienny certyfikat ukończenia szkolenia.
                    </p>
                </div>
                
                <div class="mt-8 pt-6 border-t border-ink-line">
                    <a href="/course/00-wprowadzenie/01" class="block w-full text-center rounded-md border border-ink-line bg-ink-raised px-4 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors cursor-pointer">
                        Rozpocznij kurs
                    </a>
                </div>
            </div>
        </section>

        <!-- ROZDZIAŁY -->
        <section class="mt-28 border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Zawartość kursu</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                Konkretne umiejętności nabywane w trakcie szkolenia.
            </p>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                @foreach ([
                    ['Rozdział I', 'Fundamenty sieciowe', [
                        'Model warstwowy, Ethernet, ARP, IPv4 oraz IPv6.',
                        'Przełączanie, domeny i sieci wirtualne (VLAN).',
                        'DHCP, DNS oraz mapowanie portów i usług.'
                    ]],
                    ['Rozdział II', 'Warstwa dostępowa i L2', [
                        'Pasywne mapowanie hostów i ruchu w segmencie.',
                        'Rozpoznanie protokołów przełącznika (DTP, STP).',
                        'Adres MAC jako tożsamość oraz test VLAN hopping.'
                    ]],
                    ['Rozdział III', 'Protokoły lokalne i poświadczenia', [
                        'Podatność na pośrednika i rozgłoszeniowe rozwiązywanie nazw.',
                        'Przechwytywanie materiału NetNTLMv2.',
                        'Zatruwanie DHCP i WPAD oraz przejęcie przez IPv6.'
                    ]],
                    ['Rozdział IV', 'Rozpoznanie i enumeracja usług', [
                        'Wykrywanie hostów oraz skan wersji i systemu.',
                        'Interpretacja stanów i kompletności skanu.',
                        'Skrypty NSE, SNMP, UPnP oraz postura TLS.'
                    ]],
                    ['Rozdział V', 'Tożsamość i usługi katalogowe', [
                        'Enumeracja i przeszukiwanie udziałów SMB.',
                        'Sesja anonimowa RPC i polityka haseł.',
                        'Anonimowy odczyt usługi katalogowej LDAP.'
                    ]],
                    ['Rozdział VI', 'Usługi, bazy i podatności', [
                        'Otwarte bazy danych i weryfikacja danych wrażliwych.',
                        'Domyślne poświadczenia i walidacja z kontrolą negatywną.',
                        'Znane podatności, nuclei oraz korelacja CVE.'
                    ]],
                    ['Rozdział VII', 'Moduły specjalistyczne', [
                        'Pasywny zrzut uwierzytelnień sieci bezprzewodowej.',
                        'Bezpieczne wykrywanie urządzeń automatyki (OT/ICS).'
                    ]],
                    ['Rozdział VIII', 'Diagnostyka, dowód i raport', [
                        'Od obserwacji do udokumentowanego ustalenia.',
                        'Ocena wagi bez przeszacowania oraz struktura raportu.'
                    ]]
                ] as [$step, $title, $bullets])
                    <div class="card p-6 flex flex-col">
                        <span class="font-mono text-sm text-brand">{{ $step }}</span>
                        <h3 class="mt-3 text-xl font-semibold text-chrome">{{ $title }}</h3>
                        
                        <ul class="mt-6 space-y-3 text-sm leading-relaxed text-muted">
                            @foreach ($bullets as $item)
                                <li class="flex gap-3 items-start">
                                    <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- CERTYFIKAT: PRZYKŁAD -->
        <section class="mt-28 border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Certyfikat</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                Po zdaniu testu końcowego otrzymasz imienny certyfikat.
            </p>

            <div class="mt-12 flex justify-center">
                <div class="w-full max-w-2xl rounded-lg bg-gradient-to-b from-brand to-slate-950 p-[6px] shadow-lg">
                    <div class="rounded-md bg-white px-8 py-10 text-center sm:px-12">
                        <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="mx-auto h-7 w-auto sm:h-8">
                        <p class="mt-6 text-[0.65rem] uppercase tracking-[0.3em] text-gray-500 sm:text-xs">Certyfikat ukończenia kursu</p>
                        <div class="mx-auto my-4 h-1 w-12 bg-brand"></div>
                        <p class="text-xl font-bold uppercase tracking-tight text-gray-900 sm:text-2xl">Imię Nazwisko</p>
                        <div class="mx-auto my-4 h-1 w-12 bg-brand"></div>
                        <p class="mx-auto max-w-md text-xs leading-relaxed text-gray-600 sm:text-sm">
                            Ukończył z wynikiem pozytywnym szkolenie z oceny ekspozycji infrastruktury wewnętrznej.
                        </p>
                        <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-4 font-mono text-[0.65rem] text-gray-400">
                            <span>1A2B-3C4D-5E6F</span>
                            <span class="tracking-widest text-gray-700">pensec.top</span>
                            <span>{{ date('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <p class="mt-4 text-center text-xs text-muted">Przykładowy wygląd. Certyfikat jest imienny i weryfikowalny pod własnym adresem.</p>
        </section>

    </main>

    @include('partials.site-footer')


    @include('partials.cookie-consent')
</body>
</html>