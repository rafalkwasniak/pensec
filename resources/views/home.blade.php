<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pensec</title>
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
<body class="min-h-screen flex flex-col antialiased">

    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 backdrop-glow"></div>
        <div class="pointer-events-none absolute inset-0 backdrop-grid"></div>

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
                <a href="/tests" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium {{ request()->is('tests') ? 'text-chrome font-semibold' : 'text-muted hover:text-chrome' }}">
                    Specyfikacja
                </a>
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

        <header class="relative mx-auto max-w-6xl px-6 pt-14 pb-20 text-center sm:pt-20">
            <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256"
                 class="theme-when-dark logo-glow mx-auto w-64 sm:w-80">
            <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256"
                 class="theme-when-light logo-glow mx-auto w-64 sm:w-80">

            <p class="mt-10 text-xs uppercase tracking-[0.35em] text-brand">Pentest Security</p>

            <h1 class="mt-6 text-balance text-4xl font-semibold leading-tight chrome-text sm:text-6xl">
                Automatyczny audyt bezpieczeństwa
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-balance text-lg leading-relaxed text-muted">
                Zaprojektowany od podstaw system diagnostyczny przeprowadza testy penetracyjne wewnątrz sieci firmowych i przemysłowych. Wykrywa błędne konfiguracje, ukryte podatności i otwarte wektory ataków, zanim zostaną wykorzystane. Wynikiem audytu jest raport zawierający informacje o brakach w zabezpieczeniach, wykaz diagnostyczny sieci oraz instrukcje dotyczące jej naprawy.
            </p>
            
            <div class="mt-12 text-center">
                <a href="/contact" class="inline-block rounded-full border border-ink-line bg-ink-raised px-6 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors">
                    Rozpocznij współpracę
                </a>
            </div>
        </header>
    </div>

    <main class="flex-grow w-full mx-auto max-w-6xl px-6 py-10">

        <!-- RAPORTY -->
        <section id="raporty" class="border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Wartość raportu</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                Jeden audyt, dwa dokumenty dostosowane do celu.
            </p>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                
                <!-- KAFELEK 1: Menedżerski -->
                <div class="card flex flex-col justify-between p-6">
                    <div>
                        <h3 class="text-xl font-semibold text-chrome">Raport Menedżerski</h3>
                        
                        <ul class="mt-6 space-y-3 text-sm leading-relaxed text-muted">
                            @foreach ([
                                'Zrozumiały opis stanu bezpieczeństwa sieci w kontekście ryzyka biznesowego.',
                                'Wspiera wykazanie należytej staranności i przygotowanie do wymogów dyrektywy NIS2.',
                                'Ułatwia podejmowanie strategicznych decyzji i alokację zasobów IT, dzięki jasnemu wskazaniu priorytetów naprawczych.',
                            ] as $item)
                                <li class="flex gap-3 items-start">
                                    <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach

                            <li class="pt-4 pb-2">
                                <span class="font-semibold text-chrome text-xs uppercase tracking-widest">Zawartość raportu:</span>
                            </li>

                            @foreach ([
                                'Ocena ryzyka i skategoryzowana lista wykrytych luk.',
                                'Wykaz aktywnych hostów, ich producentów oraz otwartych portów w warstwie sieciowej.',
                                'Wyniki skanowania podatności i struktury webowej (CVE i aplikacje webowe).',
                                'Ekspozycja poufnych poświadczeń, wycieki z LDAP, anonimowe udziały SMB i słabe hasła.',
                                'Wykrywanie infrastruktury przemysłowej (ICS/OT) w otwartych segmentach sieci LAN.',
                                'Diagnostyka sieci i bezpieczeństwo Wi-Fi, podatności na podsłuch, VLAN Hopping czy fałszywe DHCP.',
                                'Plan naprawy lub utrzymania — uszeregowane według priorytetu zalecenia usunięcia wykrytych podatności.',
                            ] as $item)
                                <li class="flex gap-3 items-start">
                                    <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-ink-line"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-ink-line">
                        <a href="/reports/pensec-raport-kliencki.pdf" target="_blank" class="block w-full text-center rounded-md border border-ink-line bg-ink-raised px-4 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors cursor-pointer">
                            Zobacz przykładowy Raport Menedżerski
                        </a>
                    </div>
                </div>

                <!-- KAFELEK 2: Ekspercki -->
                <div class="card flex flex-col justify-between p-6">
                    <div>
                        <h3 class="text-xl font-semibold text-chrome">Raport Ekspercki</h3>
                        
                        <ul class="mt-6 space-y-3 text-sm leading-relaxed text-muted">
                            @foreach ([
                                'Skierowany do działów IT, precyzyjnie operujący numerami CVE, adresami IP i nazwami usług.',
                                'Zawiera surowe zapisy, wyciągi z baz danych, przechwycone skróty haseł i zrzuty banerów.',
                                'Plan naprawy stanowi inżynieryjną listę zadań do natychmiastowej zmiany konfiguracji w sprzęcie.',
                            ] as $item)
                                <li class="flex gap-3 items-start">
                                    <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach

                            <li class="pt-4 pb-2">
                                <span class="font-semibold text-chrome text-xs uppercase tracking-widest">Zawartość raportu:</span>
                            </li>

                            @foreach ([
                                'Ocena ryzyka i skategoryzowana lista wykrytych luk.',
                                'Wykaz aktywnych hostów, ich producentów oraz otwartych portów w warstwie sieciowej.',
                                'Wyniki skanowania podatności i struktury webowej (CVE i aplikacje webowe).',
                                'Ekspozycja poufnych poświadczeń, wycieki z LDAP, anonimowe udziały SMB i słabe hasła.',
                                'Wykrywanie infrastruktury przemysłowej (ICS/OT) w otwartych segmentach sieci LAN.',
                                'Diagnostyka sieci i bezpieczeństwo Wi-Fi, podatności na podsłuch, VLAN Hopping czy fałszywe DHCP.',
                                'Plan naprawy lub utrzymania — uszeregowane według priorytetu zalecenia usunięcia wykrytych podatności.',
                            ] as $item)
                                <li class="flex gap-3 items-start">
                                    <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-ink-line"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-8 pt-6 border-t border-ink-line">
                        <a href="/reports/pensec-raport-ekspercki.pdf" target="_blank" class="block w-full text-center rounded-md border border-ink-line bg-ink-raised px-4 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors cursor-pointer">
                            Zobacz przykładowy Raport Ekspercki
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- SPECYFIKACJA -->
        <section id="testy" class="mt-28 border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Silnik Audytowy</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                Zakres wykonywanych testów penetracyjnych.
            </p>

            <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['RECON / MAP', 'Mapowanie Topologii', 'Pasywna i aktywna identyfikacja w warstwie L2/L3 (arp-scan, Nmap). Sonda wykrywa aktywne hosty oraz rozpoznaje wersje uruchomionych usług.'],
                    ['VULN / CVE', 'Skanowanie Podatności', 'Detekcja znanych luk przy użyciu silnika Nuclei oraz skryptów NSE (wycieki kodu, luki w aplikacjach webowych, przestarzałe protokoły SSL/TLS).'],
                    ['ICS / OT', 'Infrastruktura Przemysłowa', 'Wykrywanie punktów końcowych sterowania przemysłowego (Modbus, PROFINET, BACnet, EtherCAT, CODESYS) widocznych w sieciach biurowych.'],
                    ['DB / NOSQL', 'Bezpieczeństwo Baz Danych', 'Głęboki audyt (podatność na RCE, wycieki PII, braki haseł) dla m.in.: MySQL, PostgreSQL, MSSQL, Oracle, SAP HANA, MongoDB, Redis, Elasticsearch.'],
                    ['AUTH / BRUTE', 'Higiena Poświadczeń', 'Słownikowa weryfikacja odporności usług krytycznych (SSH, FTP, bazy danych) przy użyciu list domyślnych i fabrycznych haseł producentów.'],
                    ['AD / SMB', 'Usługi Katalogowe', 'Wyszukiwanie otwartych udziałów sieciowych (SMB Null Sessions) oraz detekcja wycieków struktury i polityk haseł z kontrolerów domeny (LDAP).'],
                    ['MITM / SPOOF', 'Przechwytywanie Ruchu', 'Symulacja ataków w warstwie lokalnej: zatruwanie LLMNR/NBT-NS (Responder), Rogue DHCP, IPv6 Spoofing (mitm6) oraz WPAD Spoofing.'],
                    ['L2 / L3', 'Diagnostyka Infrastruktury', 'Badanie konfiguracji przełączników i routerów: weryfikacja BPDU Guard, blokad Gratuitous ARP, izolacji VLAN (VLAN Hopping) oraz Egress Filtering.'],
                    ['WLAN / 802.11', 'Bezpieczeństwo Wi-Fi', 'Pasywne przechwytywanie materiału kryptograficznego EAPOL (4-way handshakes) z eteru w celu weryfikacji podatności sieci WPA2/WPA3 na łamanie offline.'],
                ] as [$step, $title, $body])
                    <li class="card p-6">
                        <span class="font-mono text-sm text-brand">{{ $step }}</span>
                        <h3 class="mt-3 text-lg font-semibold text-chrome">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $body }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="mt-12 text-center">
                <a href="/tests" class="inline-block rounded-full border border-ink-line bg-ink-raised px-6 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors">
                    Zobacz pełną specyfikację
                </a>
            </div>
        </section>

        <!-- ZASADY BADANIA -->
        <section class="mt-28 border-t border-ink-line pt-20">
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">Zasady badania</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                Kontrolowane, poufne i potwierdzone.
            </p>

            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                @foreach ([
                    ['Uzgodniony zakres', 'Badanie działa wyłącznie w granicach ustalonych z klientem przed startem (Rules of Engagement). Nic poza uzgodnioną siecią nie jest dotykane.'],
                    ['Poufność danych', 'Współpraca objęta umową NDA. Surowe dowody pozostają na urządzeniu, a raport opisuje je w bezpiecznej, ograniczonej formie.'],
                    ['Weryfikacja po naprawie', 'Po wdrożeniu poprawek przeprowadzamy audyt potwierdzający, że wykryte podatności zostały faktycznie usunięte.'],
                ] as [$title, $body])
                    <div class="card p-6">
                        <h3 class="text-base font-semibold text-chrome">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-muted">{{ $body }}</p>
                    </div>
                @endforeach
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

</body>
</html>