<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Specyfikacja | Pensec</title>
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
    
    <main class="flex-grow w-full mx-auto max-w-6xl px-6 py-10" style="margin-top: 50px;">

        <!-- GRID -->
        <div class="grid gap-8 md:grid-cols-2">
            
            <!-- RECON / MAP -->
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">Recon / Map</h3>
                </div>
                <ul class="space-y-4 text-sm text-muted">
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Pasywna i aktywna detekcja hostów warstwy L2</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">arp-scan</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Skanowanie portów TCP i profilowanie wersji usług</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Nmap -sV</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Dynamiczne maskowanie fizycznego adresu MAC sondy</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">macchanger</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Analiza najaktywniejszych urządzeń (Top Talkers) i wydajności łącza</span>
                        <div class="flex gap-2 font-mono text-xs">
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">tshark</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">speedtest</span>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- VULN / CVE -->
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">Vuln / CVE</h3>
                </div>
                <ul class="space-y-4 text-sm text-muted">
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Wykrywanie podatności webowych (szablony i wywołania OAST)</span>
                        <div class="flex flex-wrap gap-2 font-mono text-xs">
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Nuclei</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Custom Templates</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Interactsh</span>
                        </div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Wykrywanie znanych luk CVE na usługach sieciowych</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Nmap NSE</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Fuzzing struktur HTTP(S) w poszukiwaniu archiwów (.env, .bak, .sql)</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Dirb</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Audyt certyfikatów (daty wygaśnięcia) i przestarzałych protokołów (TLS 1.0, SSLv3)</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">ssl-enum-ciphers</span></div>
                    </li>
                </ul>
            </div>

            <!-- ICS / OT -->
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">ICS / OT (Industrial)</h3>
                </div>
                <ul class="space-y-4 text-sm text-muted">
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Skanowanie dedykowanych portów automatyki (TCP/UDP/SCTP)</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Pobieranie strukturalnych banerów produkcyjnych (Module, Hardware Version)</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Siemens S7</span></div>
                    </li>
                    <li class="flex flex-col gap-2 mt-2">
                        <span class="text-chrome font-medium">Identyfikacja protokołów sterowania:</span>
                        <div class="flex flex-wrap gap-2 font-mono text-xs">
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Modbus</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">BACnet</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">EtherNet/IP</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">OPC UA</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">DNP3</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">PROFINET</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">EtherCAT</span>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- DB / NOSQL -->
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">Databases / NoSQL</h3>
                </div>
                <ul class="space-y-4 text-sm text-muted">
                    <li class="flex flex-col gap-2">
                        <span class="text-chrome font-medium">Identyfikacja baz NoSQL/Cache bez uwierzytelnienia</span>
                        <div class="flex flex-wrap gap-2 font-mono text-xs">
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Redis</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Elasticsearch</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">MongoDB</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">InfluxDB</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Cassandra</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Neo4j</span>
                        </div>
                    </li>
                    <li class="flex flex-col gap-2 mt-2">
                        <span class="text-chrome font-medium">Detekcja komercyjnych systemów Enterprise</span>
                        <div class="flex flex-wrap gap-2 font-mono text-xs">
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Oracle</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">IBM DB2</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">SAP HANA</span>
                        </div>
                    </li>
                    <li class="flex flex-col gap-1 mt-2">
                        <span class="text-chrome font-medium">Ataki słownikowe i weryfikacja autoryzacji RDBMS</span>
                        <div class="flex flex-wrap gap-2 font-mono text-xs">
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Hydra</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">MySQL</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">PostgreSQL</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">MSSQL</span>
                        </div>
                    </li>
                    <li class="flex flex-col gap-2 mt-2">
                        <span class="text-chrome font-medium">Wykrywanie podatności RCE i Lateral Movement</span>
                        <div class="flex flex-wrap gap-2 font-mono text-xs">
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">UDF Injection</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">COPY FROM PROGRAM</span>
                            <span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">xp_dirtree</span>
                        </div>
                    </li>
                    <li class="flex flex-col gap-1 mt-2">
                        <span class="text-chrome font-medium">Ekstrakcja danych wrażliwych (Wyszukiwanie numerów kart i PESEL w tabelach)</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">PII Radar</span></div>
                    </li>
                </ul>
            </div>

            <!-- AD / SMB / SNMP -->
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">Active Directory / SMB / SNMP</h3>
                </div>
                <ul class="space-y-4 text-sm text-muted">
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Weryfikacja praw dostępu (SMB Null Sessions)</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Eksploracja udziałów sieciowych (Spidering)</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">.kdbx</span><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">.ovpn</span><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">unattend.xml</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Pobieranie konfiguracji Password Policy z AD</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">rpcclient</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Enumeracja kont użytkowników w domenie</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">enumdomusers</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Testowanie wycieków z serwera LDAP (Anonymous Bind)</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Wykrywanie niezabezpieczonych agentów i urządzeń sieciowych (SNMP public community)</span>
                    </li>
                </ul>
            </div>

            <!-- MITM / SPOOFING -->
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">MITM / Spoofing</h3>
                </div>
                <ul class="space-y-4 text-sm text-muted">
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Zatruwanie zapytań LLMNR / NBT-NS / mDNS (Tryb Pasywny i Aktywny)</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Responder</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Przechwytywanie skrótów kryptograficznych NetNTLMv2</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Podatność na przejęcie bramy w standardzie IPv6</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">mitm6</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Weryfikacja podatności na fałszowanie ARP i przechwytywanie ruchu (ARP Spoofing)</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Wykrywanie Rogue DHCP oraz podatności autorytatywnego wpisu WPAD</span>
                    </li>
                </ul>
            </div>

            <!-- L2 / L3 DIAGNOSTICS -->
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">L2 / L3 Diagnostics</h3>
                </div>
                <ul class="space-y-4 text-sm text-muted">
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Badanie kondycji resolverów DNS i pomiary opóźnień (Latency)</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Iniekcja złośliwych ramek weryfikujących BPDU Guard</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Scapy</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Symulacja ominięcia segmentacji sieci (VLAN Hopping / QinQ)</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Badanie podatności portów na Network Access Control (MAB Bypass)</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Weryfikacja reguł Egress Filtering i portów wychodzących</span>
                    </li>
                </ul>
            </div>

            <!-- WLAN / 802.11 -->
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">WLAN / 802.11</h3>
                </div>
                <ul class="space-y-4 text-sm text-muted">
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Wymuszanie rygorystycznego trybu Monitor Mode</span>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Pasywne gromadzenie informacji o ukrytych punktach dostępowych</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">Channel Hopping</span></div>
                    </li>
                    <li class="flex flex-col gap-1">
                        <span class="text-chrome font-medium">Przechwytywanie kryptograficznych uścisków dłoni WPA2/WPA3</span>
                        <div class="flex gap-2 font-mono text-xs"><span class="bg-ink-raised border border-ink-line px-2 py-0.5 rounded text-brand">EAPOL 4-way handshakes</span></div>
                    </li>
                </ul>
            </div>

        </div>
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