1. Druga koncepcja kursu
Roboczy tytuł
Pentesting infrastruktury wewnętrznej. Od uzyskania dostępu do kompromitacji środowiska
Alternatywnie:
Praktyczny pentesting infrastruktury wewnętrznej
Pierwszy tytuł jest moim zdaniem lepszy marketingowo, ponieważ pokazuje rezultat i zakres.
Główna obietnica kursu
Po ukończeniu kursu uczestnik powinien rozumieć i potrafić przeprowadzić kontrolowany pentest środowiska wewnętrznego:
dostęp do sieci → rozpoznanie → enumeracja → identyfikacja słabości → uzyskanie pozycji → dostęp do poświadczeń → AD → eskalacja uprawnień → ruch boczny → pivoting → walidacja wpływu → raport
Nie uczysz:
„100 komend Kali Linux”.
Uczysz:
„Co robić, gdy otrzymam dostęp do środowiska i jak podjąć kolejną decyzję”.
To jest najważniejsze założenie całego kursu.
Pierwsza koncepcja już posiada bardzo dobry fundament takiej ścieżki: Assume Breach, pasywne mapowanie, MAB, VLAN, ARP, LLMNR/NBT-NS, IPv6, SMB, RPC i bazy danych.
Druga koncepcja dostarcza natomiast dobrych modułów uzupełniających, takich jak Wi-Fi, WPAD, CVE i OT/ICS oraz lepszy schemat pojedynczej lekcji.

2. Docelowa struktura całego kursu
Kurs podzieliłbym na:
Moduł 0 — Metodyka i bezpieczeństwo testu
Moduł 1 — Fundamenty sieciowe dla pentestera
Moduł 2 — Uzyskanie dostępu do infrastruktury L2
Moduł 3 — Rozpoznanie i enumeracja infrastruktury
Moduł 4 — Ataki na protokoły lokalne i pozyskiwanie poświadczeń
Moduł 5 — Active Directory: fundamenty i enumeracja
Moduł 6 — Active Directory: ataki i ścieżki eskalacji
Moduł 7 — Eskalacja uprawnień Windows i Linux
Moduł 8 — Lateral Movement i Pivoting
Moduł 9 — Usługi, bazy danych i aplikacje webowe
Moduł 10 — Moduły specjalistyczne
Moduł 11 — Raportowanie i profesjonalny proces pentestera
Moduł 12 — Projekt końcowy
To daje kurs, który ma logiczny początek, środek i zakończenie.

3. MODUŁ 0 — Metodyka i bezpieczeństwo testu
Ten moduł powinien być krótki, ale obowiązkowy.
Nie zaczynaj od Kali Linux.
0.1. Czym jest test penetracyjny
Wyjaśnij:
* różnicę między vulnerability assessment, security audit i penetration test
* czym jest red team, a czym pentest
* czym różni się discovery od exploitation
* dlaczego znalezienie podatności nie oznacza jeszcze jej wykorzystania
* dlaczego pentester musi potwierdzić wpływ.
0.2. Scope i Rules of Engagement
Zakres:
* adresy IP
* domeny
* systemy wyłączone z testu
* okna czasowe
* dozwolone techniki
* zakazane działania
* dane wrażliwe
* procedura przerwania testu.
0.3. Assume Breach
Tutaj wykorzystujesz obecny materiał. Assume Breach powinno być podstawowym modelem całego kursu.
Wyjaśnij:
zewnętrzny pentest
vs
internal pentest
vs
Assume Breach
0.4. Fazy pentestu
Ustal jeden model, według którego później prowadzisz cały kurs:
Pre-engagement → Reconnaissance → Enumeration → Vulnerability Analysis → Exploitation → Post-Exploitation → Lateral Movement → Validation → Reporting → Retest
W każdym późniejszym module przypominaj, w której fazie jesteśmy.
0.5. Evidence
Uczysz od początku:
* co należy zachować
* screenshot
* output terminala
* adres IP
* nazwa hosta
* czas
* konto
* payload
* wynik walidacji.
0.6. Bezpieczna walidacja
To bardzo ważna lekcja.
Pokaż różnicę:
„podatność istnieje”
a
„podatność pozwala wykonać destrukcyjne działanie”.
Uczestnik powinien nauczyć się kończyć exploitację na poziomie niezbędnym do udowodnienia ryzyka.
0.7. Środowisko laboratoryjne
Wprowadź docelową architekturę laboratorium.
Przykładowo:
Kali Linux
      |
 VLAN użytkowników
      |
 Windows Client
      |
 Windows Server
      |
 Active Directory / DC
      |
 File Server
      |
 Linux Server
      |
 DB / Web
      |
 drugi segment sieci
To laboratorium będzie później wykorzystywane przez cały kurs.

4. MODUŁ 1 — Fundamenty sieciowe dla pentestera
Tutaj nie robisz pełnego kursu sieci.
Uczysz tylko tego, co będzie potrzebne do rozumienia późniejszych ataków.
1.1. Ethernet
* frame
* source MAC
* destination MAC
* broadcast
* unicast
* multicast.
1.2. ARP
* IP → MAC
* ARP Request
* ARP Reply
* cache
* brak uwierzytelniania.
1.3. IPv4
* adres IP
* maska
* subnet
* gateway
* routing.
1.4. Switching
* switch
* MAC address table
* broadcast domain
* collision domain.
1.5. VLAN
* access
* trunk
* 802.1Q
* VLAN ID
* segmentacja.
1.6. DHCP
* DORA
* DHCP server
* lease
* gateway
* DNS.
1.7. DNS
* A
* AAAA
* PTR
* NS
* DNS resolution
* różnica między DNS a lokalnym name resolution.
1.8. IPv6
* podstawy adresacji
* link-local
* Router Advertisement
* DHCPv6
* preferowanie IPv6 przez niektóre systemy.
1.9. Porty i usługi
Uczestnik musi rozumieć:
IP → port → protokół → usługa → aplikacja
Nie chodzi o zapamiętanie wszystkich portów.

5. MODUŁ 2 — Uzyskanie dostępu do infrastruktury L2
Tutaj zaczyna się właściwy pentesting.
2.1. Pasywne rozpoznanie sieci
Na podstawie pierwszej koncepcji:
* broadcast
* ARP
* bierny nasłuch
* identyfikacja hostów
* MAC
* OUI
* producent urządzenia.
netdiscover zostaje.
Jednocześnie trzeba poprawić sposób opisywania wyników: działanie narzędzia nie może automatycznie oznaczać sukcesu ataku.
2.2. 802.1X
Wyjaśnij:
* czym jest 802.1X
* supplicant
* authenticator
* RADIUS
* EAP
* NAC.
Bez tego MAB jest niezrozumiałe.
2.3. MAB
* czym jest MAC Authentication Bypass
* dlaczego istnieje
* dlaczego jest używany dla urządzeń IoT/printer/VoIP
* gdzie występuje problem z zaufaniem do MAC.
2.4. MAC spoofing
* zmiana MAC
* warstwa systemowa
* warstwa przełącznika
* czym różni się zmiana lokalna od uzyskania dostępu.
macchanger zostaje.
2.5. VLAN hopping
* access vs trunk
* DTP
* warunki powodujące podatność
* czym jest VLAN hopping
* kiedy nie zadziała
* jakie zabezpieczenia go blokują.
yersinia zostaje jako narzędzie laboratoryjne.
2.6. Walidacja przełamania segmentacji
To koniecznie dodaj.
Nie:
„interfejs VLAN się utworzył, więc atak się udał”.
Tylko:
jak rzeczywiście udowodnić, że uzyskaliśmy dostęp do innej domeny.

6. MODUŁ 3 — Rozpoznanie i enumeracja infrastruktury
To powinien być jeden z największych modułów.
3.1. Reconnaissance vs Enumeration
3.2. Host Discovery
3.3. Port Scanning
3.4. Service Enumeration
3.5. Version Detection
3.6. Nmap
Nie uczysz:
„Nmap ma takie i takie flagi”.
Uczysz:
jak dobrać skan do pytania, na które chcę uzyskać odpowiedź.
3.7. NSE
* czym są NSE
* kiedy wykorzystać skrypt
* kiedy wynik jest tylko wskazówką.
3.8. Service fingerprinting
Przykładowa klasyfikacja:
445 → SMB
389 → LDAP
88 → Kerberos
53 → DNS
80/443 → HTTP
22 → SSH
3389 → RDP
3.9. Enumeracja SMB
Tutaj wchodzi materiał z pierwszej i drugiej koncepcji.
3.10. RPC
3.11. LDAP
3.12. DNS
3.13. SNMP
Dodałbym SNMP, ponieważ dobrze rozszerza rozpoznanie infrastruktury.
3.14. Tworzenie attack surface
Najważniejsza lekcja modułu:
Z wyniku:
10.0.0.10
80
443
445
3389
uczestnik ma dojść do:
web server
SMB
RDP
→ jakie pytanie zadam dalej?
To jest właściwa umiejętność pentesterska.

7. MODUŁ 4 — Ataki na protokoły lokalne i pozyskiwanie poświadczeń
To będzie jedna z najmocniejszych części kursu.
4.1. ARP spoofing
Zostaje.
4.2. Man-in-the-Middle
* model ataku
* forwarding
* ruch dwukierunkowy
* sniffing
* ograniczenia wynikające z TLS.
4.3. LLMNR
4.4. NBT-NS
4.5. Responder
Materiał z Twojej drugiej koncepcji bardzo dobrze tu pasuje.
4.6. NTLMv2
To powinno być osobną lekcją.
Wyjaśnij:
* czym jest NTLM
* czym jest challenge-response
* czym jest NetNTLMv2
* dlaczego przechwycony materiał nie jest „hasłem”.
To poprawi merytorycznie obecny materiał.
4.7. Offline password auditing
* czym jest cracking
* dictionary
* rules
* mask
* brute force
* sprzęt CPU/GPU.
4.8. WPAD
Druga koncepcja zostaje, ale jako rozwinięcie mechanizmów name resolution i proxy discovery.
4.9. IPv6 poisoning
mitm6.
Nie przedstawiaj IPv6 jako „magicznego obejścia IPv4”.
Ucz warunków:
dlaczego infrastruktura może być podatna i jakie konkretne mechanizmy są wykorzystywane.
4.10. Credential security
Na koniec:
znalazłem materiał uwierzytelniający — co dalej?
* identyfikacja rodzaju
* bezpieczne przechowywanie
* walidacja
* credential reuse
* ograniczenie zakresu testu.

8. MODUŁ 5 — Active Directory: fundamenty i enumeracja
To powinien być centralny moduł całego kursu.
Obecny fragment rpcclient → użytkownicy → policy zostaje, ale zostaje mocno rozbudowany.
5.1. Czym jest Active Directory
5.2. Domain
5.3. Forest
5.4. Domain Controller
5.5. OU
5.6. Users
5.7. Groups
5.8. Computers
5.9. GPO
5.10. SID
5.11. LDAP
5.12. Kerberos
5.13. NTLM
5.14. SMB w środowisku domenowym
5.15. RPC w AD
5.16. Null Session
Tutaj koniecznie dodaj zastrzeżenie, że dostęp anonimowy zależy od konfiguracji środowiska i nie jest uniwersalną cechą współczesnego AD.
5.17. rpcclient
* enumdomusers
* getdompwinfo
* interpretacja danych.
5.18. Enumeracja domeny
* użytkownicy
* grupy
* komputery
* polityki.
5.19. BloodHound
To powinien być obowiązkowy element kursu.
Nie ucz tylko uruchomienia BloodHound.
Ucz:
jak czytać graf zależności.

9. MODUŁ 6 — Active Directory: ataki i ścieżki eskalacji
Tutaj przechodzimy z:
„wiem co jest w domenie”
do:
„wiem, jak znaleźć drogę do większych uprawnień”.
6.1. Password policy
6.2. Password Spraying
6.3. Credential Reuse
6.4. Kerberoasting
6.5. AS-REP Roasting
6.6. Service Accounts
6.7. ACL
6.8. Delegation — wprowadzenie
6.9. Attack Paths
6.10. Lateral Movement w Windows
* SMB
* WinRM
* RDP
* WMI
* PsExec/SMB-based execution jako koncepcja.
6.11. Interpretacja BloodHound
Uczestnik powinien umieć odpowiedzieć:
„Mam konto X. Jaki jest najkrótszy realistyczny sposób dotarcia do Domain Admin?”
To jest dużo bardziej wartościowe niż znajomość konkretnego programu.

10. MODUŁ 7 — Eskalacja uprawnień Windows i Linux
To jeden z najważniejszych brakujących elementów.
Windows
7.1. Local Enumeration
7.2. Users and Groups
7.3. Services
7.4. Scheduled Tasks
7.5. File Permissions
7.6. Registry
7.7. Tokens i privileges
7.8. UAC
7.9. Credential material
Linux
7.10. Local Enumeration
7.11. Users and Groups
7.12. sudo
7.13. SUID
7.14. Capabilities
7.15. Cron
7.16. Writable files/directories
7.17. Services
7.18. Interpretacja wyników
Nie rób z tego listy exploitów.
Ucz:
enumerate → identify weakness → validate → escalate → document.

11. MODUŁ 8 — Lateral Movement i Pivoting
Dzięki temu kurs wreszcie staje się pełnym scenariuszem internal pentestu.
8.1. Lateral Movement
Co oznacza przechodzenie:
Host A
  ↓
Host B
  ↓
Host C
8.2. Segmentacja
8.3. Routing między segmentami
8.4. Pivot przez przejęty host
8.5. SOCKS Proxy
8.6. Proxychains
8.7. SSH Port Forwarding
8.8. Local vs Remote Forwarding
8.9. Dostęp do ukrytego segmentu
8.10. Kompletny scenariusz pivotingu
To powinno być ćwiczenie wieloetapowe, nie kilka niezależnych komend.

12. MODUŁ 9 — Usługi, bazy danych i aplikacje webowe
Tutaj zachowujesz dużą część drugiej koncepcji.
Bazy
9.1. Open Services
9.2. Authentication vs Authorization
9.3. Redis
9.4. MongoDB
9.5. Elasticsearch
9.6. Anonymous Access
9.7. Sensitive Data Exposure
9.8. Manual Validation
Najważniejsza zasada:
otwarty port ≠ podatność krytyczna.

Web
Nie tworzysz pełnego kursu web security, ale musisz dać podstawę.
9.9. HTTP
9.10. Request / Response
9.11. Cookies
9.12. Sessions
9.13. Authentication
9.14. Authorization
9.15. Burp Suite
9.16. Input Validation
9.17. IDOR
9.18. Path Traversal
9.19. SSRF — wprowadzenie
9.20. API
9.21. Nuclei
Tu poprawiamy obecną koncepcję:
Nuclei wykrywa potencjalny problem → pentester dokonuje triage → ręcznie waliduje → ocenia wpływ.
Nie:
Nuclei znalazł CVE → mamy RCE.

13. MODUŁ 10 — Moduły specjalistyczne
To jest miejsce na elementy, które są wartościowe, ale nie powinny zaburzać głównej ścieżki.
10.1. Wireless Pentesting
Z drugiej koncepcji:
* 802.11
* Beacon
* monitor mode
* WPA2
* WPA3
* capture
* handshake
* hcxdumptool
* hcxpcapngtool
* Hashcat.
Materiał o Wi-Fi zostaje jako pełnoprawny moduł dodatkowy.
10.2. Zaawansowane IPv6
* Router Advertisement
* DHCPv6
* DNS takeover
* IPv6-only paths
* różnice względem IPv4.
10.3. OT/ICS
* czym różni się IT od OT
* PLC
* SCADA
* Modbus
* S7
* bezpieczeństwo operacyjne
* safe scanning
* ryzyko zakłócenia procesu.
Tu nie robisz z kursanta „ICS exploit developera”.
Celem jest:
rozpoznanie ekspozycji OT i zrozumienie ryzyka.
nmap + skrypty pozostają, ale precyzyjnie opisujesz je jako aktywne zapytania, a nie pasywny monitoring. Obecna wersja drugiej koncepcji miesza te pojęcia.

14. MODUŁ 11 — Raportowanie
To nie powinien być dodatek na końcu.
W każdym wcześniejszym module uczestnik ma już zbierać evidence.
Tutaj składamy to w całość.
11.1. Finding
11.2. Title
11.3. Description
11.4. Technical Details
11.5. Evidence
11.6. Impact
11.7. Severity
11.8. CVSS
11.9. Attack Path
11.10. Recommendation
11.11. Retest
11.12. Executive Summary
11.13. Technical Report
11.14. Priorytetyzacja
Uczestnik powinien dostać gotowy przykład:
Finding
↓
Evidence
↓
Impact
↓
Risk
↓
Remediation
↓
Retest
I na końcu napisać własny finding.

15. MODUŁ 12 — Projekt końcowy
To jest najważniejszy element całego kursu.
Nie kończ kursu quizem.
Kończ go pełnym pentestem laboratoryjnym.
Scenariusz
Uczestnik dostaje:
* zakres IP
* ograniczenia
* konto początkowe albo fizyczny dostęp do segmentu
* kilka podsieci
* Windows
* Linux
* Active Directory
* file server
* web server
* bazę danych
* opcjonalnie Wi-Fi.
Nie dostaje instrukcji:
użyj nmap, potem Responder, potem BloodHound.
Dostaje jedynie:
„Przeprowadź test.”

Zadania końcowe
Uczestnik powinien:
1. Określić scope
2. Wykonać reconnaissance
3. Zidentyfikować hosty
4. Zidentyfikować usługi
5. Znaleźć słabe punkty
6. Uzyskać dostęp
7. Pozyskać materiał uwierzytelniający
8. Przeprowadzić enumerację AD
9. Znaleźć ścieżkę eskalacji
10. Wykonać lateral movement
11. Wykorzystać pivoting
12. Udowodnić wpływ
13. Zebrać evidence
14. Przygotować raport
To jest moment, w którym kursant powinien samodzielnie zastosować wszystko, czego nauczył się wcześniej.

16. Stały format każdej lekcji
To proponuję przyjąć jako sztywny standard redakcyjny całego kursu.
Każda lekcja ma dokładnie tę strukturę:
1. Cel lekcji
Co uczestnik ma umieć po jej zakończeniu.
2. Kontekst
Dlaczego pentester w ogóle interesuje się danym mechanizmem.
3. Fundament techniczny
Jak działa technologia.
4. Warunki podatności
Kiedy atak może się udać?
To bardzo ważne.
5. Metodyka
Jak pentester dochodzi do decyzji.
6. Narzędzie
Dopiero tutaj pojawia się:
nmap
Responder
rpcclient
BloodHound itd.
7. Laboratorium
Kontrolowane wykonanie.
8. Analiza wyniku
Co właściwie oznacza output.
9. Walidacja
Jak potwierdzić podatność.
10. Ocena wpływu
Co realnie można osiągnąć.
11. Remediacja
Co administrator powinien zrobić.
12. Evidence
Co należy zachować do raportu.
13. Ćwiczenie samodzielne
Jedna rzecz bez instrukcji krok po kroku.
14. Checkpoint
Kilka pytań sprawdzających rozumienie.

17. Bardzo ważna zasada językowa całego kursu
W Twoich obecnych materiałach często pojawia się konstrukcja:
„Polecenie robi X → otrzymujemy Y → atak zakończony sukcesem”.
Tego nie powinno być w tej formie.
Zastąp to schematem:
Założenie → działanie → obserwacja → interpretacja → walidacja → wpływ
Przykład koncepcyjny:
MAC spoofing
↓
zmiana lokalnego MAC
↓
interfejs działa
↓
to jeszcze nie oznacza autoryzacji
↓
sprawdzamy dostęp do sieci
↓
sprawdzamy otrzymaną konfigurację
↓
dopiero wtedy oceniamy MAB
To bardzo podniesie poziom merytoryczny.

18. Czego NIE dodawać do głównej ścieżki
Żeby kurs nie zamienił się w encyklopedię:
Nie rozbudowywałbym głównej ścieżki o:
malware development,
exploit development,
reverse engineering,
advanced AV/EDR evasion,
zaawansowane C2,
pełny mobile pentesting,
pełny cloud pentesting,
zaawansowany exploit development dla kernela.
To mogą być kiedyś osobne kursy.
Główny kurs ma mieć wyraźne DNA:
Internal Network Pentesting + Windows/AD + lateral movement + reporting

19. Co zostaje z obu pierwotnych dokumentów
Z pierwszego dokumentu zachowujemy:
Assume Breach
Passive Discovery
MAB
VLAN/DTP
ARP spoofing
LLMNR/NBT-NS
DHCPv6 / mitm6
SMB
RPC
Redis
MongoDB
To jest rdzeń.
Z drugiego dokumentu zachowujemy:
Rules of Engagement
MITRE ATT&CK
Wi-Fi
Responder
WPAD
IPv6
BloodHound jako kierunek rozwoju AD
CVE/Nuclei
Elasticsearch
OT/ICS
plus przede wszystkim format:
Anatomia → Wektor → Analiza wyniku.

20. Jak powinien wyglądać poziom kursu
Docelowo:
Początek
Beginner → lower intermediate
Uczestnik nie musi być pentesterem.
Musi natomiast nauczyć się podstaw Linuxa, sieci i Windowsa w trakcie.
Środek
Intermediate
Dużo praktyki z:
* Nmap
* SMB
* Responder
* AD
* BloodHound
* credential attacks
* privilege escalation
* lateral movement.
Koniec
Intermediate → advanced practical
Projekt końcowy wymaga samodzielnego łączenia technik.

21. Docelowy rozmiar kursu
Nie robiłbym 15 ogromnych modułów po 40 lekcji.
Celowałbym w około:
12 modułów
65–80 lekcji
50–70 godzin materiału wraz z laboratoriami
1 środowisko laboratoryjne rozwijane przez cały kurs
1 duży projekt końcowy
Wtedy kurs jest już produktem, a nie serią artykułów.

22. Kolejność pisania
Tutaj ważna rzecz: nie pisałbym kursu dokładnie w kolejności, w której będzie go czytał użytkownik.
Najpierw przygotuj fundament:
1. Architektura laboratorium
2. Projekt końcowego scenariusza
3. Model metodyki
4. Spis wszystkich findingów, które mają wyjść z laboratorium
5. Dopiero potem lekcje
Dzięki temu każda wcześniejsza lekcja będzie przygotowywała do późniejszego scenariusza.
Przykładowo:
Lekcja ARP
       ↓
Lekcja Responder
       ↓
Lekcja NTLM
       ↓
Lekcja AD
       ↓
Kerberoasting
       ↓
BloodHound
       ↓
Lateral Movement
       ↓
Pivoting
       ↓
Raport
Nie będzie wrażenia, że każdy rozdział jest niezależnym tutorialem.

23. Ostateczny „kręgosłup” kursu
Cały kurs powinien dać się streścić jednym diagramem:
METODYKA
   ↓
ASSUME BREACH
   ↓
ACCESS
   ↓
L2 RECON
   ↓
NETWORK ENUMERATION
   ↓
SERVICE ENUMERATION
   ↓
CREDENTIAL ACCESS
   ↓
ACTIVE DIRECTORY
   ↓
PRIVILEGE ESCALATION
   ↓
LATERAL MOVEMENT
   ↓
PIVOTING
   ↓
SERVICE / WEB / DATABASE VALIDATION
   ↓
IMPACT
   ↓
EVIDENCE
   ↓
REPORT
   ↓
RETEST
A Wi-Fi, OT/ICS i pozostałe bardziej specjalistyczne zagadnienia są odnogami tej głównej ścieżki, a nie osobnymi historiami.
 numer → tytuł → cel → wiedza wymagana wcześniej → narzędzia → laboratorium → wynik → finding → remediacja → ćwiczenie




Aktualnie posiadane teksty (stare):
Wersja 1:
Kurs pentesterski

## Metodologia i Założenia Audytu

### Ramy Operacyjne
Profesjonalny test penetracyjny wymaga precyzyjnego zdefiniowania zasad zaangażowania (Rules of Engagement). Działania ofensywne wewnątrz infrastruktury produkcyjnej niosą ryzyko zakłócenia ciągłości biznesowej. Zbiór reguł określa dozwolone okna czasowe oraz wykaz adresów IP wyłączonych z aktywnych skanów. Przestrzeganie standardów takich jak PTES (Penetration Testing Execution Standard) gwarantuje powtarzalność procesu analitycznego.

### Koncepcja Assume Breach
Skuteczna ewaluacja bezpieczeństwa odrzuca założenie o szczelności obwodu sieciowego. Metodologia Assume Breach wymusza rozpoczęcie testów bezpośrednio z wnętrza strefy zaufanej. Symulacja zakłada wcześniejsze skompromitowanie stacji roboczej pracownika lub uzyskanie fizycznego dostępu do portu dostępowego. Pominięcie zewnętrznych zapór sieciowych pozwala na weryfikację rzeczywistej odporności mechanizmów segmentacji oraz kontroli dostępu.

### Modele Referencyjne
Identyfikacja i klasyfikacja zagrożeń opiera się na globalnych matrycach taktyk ofensywnych. Standaryzacja wyników wykorzystuje ramy analityczne MITRE ATT&CK. Przypisanie wykrytych podatności do konkretnych technik ataku ułatwia mapowanie potencjalnych ścieżek eskalacji (Kill Chain). Wynik audytu dostarcza zespołom reagowania (Blue Team) gotowych sygnatur ułatwiających kalibrację systemów monitorowania ruchu.



## Eksploracja przestrzeni radiowej 802.11

### Anatomia Protokołu
Komunikacja bezprzewodowa w standardzie 802.11 wymusza jawną transmisję ramek zarządzających. Punkty dostępowe nieustannie rozgłaszają parametry sieci w pakietach typu Beacon. Uwierzytelnianie klientów w protokołach WPA2 oraz WPA3 opiera się na kryptograficznym procesie wymiany kluczy. Brak fizycznej izolacji medium transmisyjnego umożliwia pasywne monitorowanie całego ruchu w zasięgu anteny.

### Wektor Ataku
Przechwycenie materiału kryptograficznego wymaga użycia interfejsu sieciowego przełączonego w tryb monitora (Monitor Mode). Narzędzie `hcxdumptool` pozwala na agresywne gromadzenie ramek autoryzacyjnych. Wymuszenie rozłączenia klienta skutkuje ponowną negocjacją sesji oraz wygenerowaniem nowego uścisku dłoni.

```bash
hcxdumptool -i wlan0 -o zrzut_ruchu.pcapng --active_beacon --enable_status=1
```

Oprogramowanie przejmuje całkowitą kontrolę nad interfejsem bezprzewodowym. Parametr aktywujący pakiety weryfikacyjne wymusza bezpośrednią interakcję z pobliskimi punktami dostępowymi.

### Analiza Wyniku
Zebrany ruch sieciowy ulega przetworzeniu w celu wyekstrahowania użytecznych wartości skrótów kryptograficznych. Program `hcxpcapngtool` filtruje plik wejściowy i generuje strukturę danych zgodną z formatami łamaczy haseł.

```bash
hcxpcapngtool -o skroty.hc22000 zrzut_ruchu.pcapng
```

Otrzymany plik poddaje się następnie analizie słownikowej z wykorzystaniem mocy obliczeniowej procesorów graficznych.

```bash
hashcat -m 22000 skroty.hc22000 slownik.txt
```



## Pasywna detekcja i enumeracja L2

### Anatomia Protokołu
Protokoły warstwy łącza danych (L2) nieustannie emitują pakiety rozgłoszeniowe (Broadcast). Urządzenia sieciowe komunikują swoją obecność za pomocą zapytań protokołu ARP. Architektura przełączników wymusza propagację takich ramek do wszystkich aktywnych portów w danej domenie rozgłoszeniowej. Pasywny nasłuch pozwala na mapowanie topologii sieciowej bez generowania najmniejszych anomalii w systemach detekcji intruzów (IDS).

### Wektor Ataku
Narzędzia do analizy ruchu sieciowego umożliwiają cichą rejestrację pakietów. Program `netdiscover` automatyzuje proces wyciągania użytecznych danych adresowych z przechwyconych żądań.

```bash
netdiscover -i eth0 -p
```

Oprogramowanie nasłuchuje w tle i kolekcjonuje protokoły translacji adresów. Parametr wymuszający tryb pasywny całkowicie blokuje wysyłanie jakichkolwiek pakietów z interfejsu audytora. 

### Analiza Wyniku
System zwraca tabelę powiązań między adresami IP a sprzętowymi adresami MAC. Identyfikacja pierwszych trzech oktetów adresu sprzętowego (OUI) ujawnia producenta układu sieciowego.

```text
 192.168.1.10    b8:27:eb:xx:xx:xx   Raspberry Pi Foundation
 192.168.1.55    00:15:99:xx:xx:xx   Samsung Electronics
 192.168.1.120   00:50:56:xx:xx:xx   VMware, Inc.
```

Otrzymana struktura umożliwia precyzyjne odróżnienie urządzeń wbudowanych (drukarki, telefony IP) od serwerów wirtualnych. Wyodrębnienie klas sprzętu ukierunkowuje dalsze ataki na konkretne podatności (CVE) omijając konieczność głośnego, aktywnego skanowania wszystkich adresów IP w sieci.



## Przełamywanie segmentacji L2 (VLAN Hopping)

### Anatomia Protokołu
Tagowanie 802.1Q izoluje ruch sieciowy pomiędzy wirtualnymi strefami LAN. Ramki przesyłane przez łącza magistralowe (Trunk) posiadają dodatkowy nagłówek z identyfikatorem sieci. Wiele przełączników sieciowych domyślnie wykorzystuje protokół DTP do automatycznej negocjacji stanu portów. Ufność mechanizmu DTP dopuszcza zestawienie magistrali z nieautoryzowanym sprzętem podłączonym do zwykłego gniazdka dostępowego.

### Wektor Ataku
Aplikacja `yersinia` generuje fałszywe pakiety negocjacyjne protokołu DTP. Oprogramowanie wysyła żądanie aktywacji trybu Trunk bezpośrednio do przełącznika brzegowego.

```bash
yersinia dtp -attack 1 -interface eth0
```

Skuteczne wymuszenie zmiany stanu portu otwiera intruzowi dostęp do tagowanego ruchu. Interfejs atakującego wymaga następnie utworzenia wirtualnego podinterfejsu zdolnego do odbierania specyficznych ramek. Polecenie systemowe definiuje nowy interfejs i przypisuje mu docelowy identyfikator VLAN.

```bash
ip link add link eth0 name eth0.10 type vlan id 10
ip link set dev eth0.10 up
```

### Analiza Wyniku
Przełamanie segmentacji skutkuje natychmiastową widocznością ruchu rozgłoszeniowego obcych sieci wirtualnych. System operacyjny napastnika odbiera ramki przypisane pierwotnie do wyizolowanych stref. Uruchomienie klienta DHCP na nowym podinterfejsie owocuje pobraniem prawidłowej adresacji IP z zastrzeżonej puli. Napastnik uzyskuje pełny dostęp warstwy drugiej do chronionych zasobów wewnątrz atakowanego segmentu.



## Omijanie kontroli dostępu (MAB Bypass)

### Anatomia Protokołu
Zabezpieczenie portów dostępowych standardem 802.1X wymusza kryptograficzne uwierzytelnianie stacji roboczych. Urządzenia wbudowane omijają ten wymóg wykorzystując mechanizm MAB. Przełącznik sieciowy autoryzuje wybrane sprzęty wyłącznie na podstawie źródłowego adresu MAC. Taka weryfikacja pozbawiona jest jakichkolwiek metod szyfrowania transmisji. Zaufanie do urządzenia opiera się na łatwym do podrobienia identyfikatorze sprzętowym.

### Wektor Ataku
Intruz wykorzystuje pasywnie zebrane adresy MAC autoryzowanych drukarek sieciowych lub telefonów VoIP. Procedura ataku wymaga całkowitego wyłączenia interfejsu sieciowego sondy operacyjnej. Narzędzie `macchanger` modyfikuje następnie fizyczny adres karty.

```bash
ip link set dev eth0 down
macchanger -m 00:15:99:xx:xx:xx eth0
ip link set dev eth0 up
```

Spreparowany interfejs rozpoczyna emisję standardowych ramek sieciowych opatrzonych nowym adresem źródłowym. Oszukany przełącznik wysyła żądanie autoryzacji do centralnego serwera uwierzytelniającego.

### Analiza Wyniku
Serwer RADIUS otrzymuje zapytanie zawierające zaufany adres sprzętowy. System kontroli dostępu NAC natychmiast autoryzuje sesję. Port dostępowy zmienia stan na aktywny i przepuszcza ruch sieciowy do wnętrza infrastruktury produkcyjnej. Mechanizmy zabezpieczające systematycznie traktują komputer napastnika jako autoryzowane urządzenie wbudowane. Intruz zyskuje stabilne połączenie gotowe do dalszej eksploracji płaszczyzny sterowania.



## Zatruwanie protokołów rozwiązywania nazw

### Anatomia Protokołu
Środowiska operacyjne Windows wykorzystują protokoły LLMNR oraz NBT-NS do lokalnego rozwiązywania nazw. Mechanizm aktywuje się w momencie braku odpowiedzi od głównego serwera DNS. Zapytania posiadają charakter rozgłoszeniowy. Odbiorcy nie posiadają natywnej metody weryfikacji tożsamości hosta wysyłającego odpowiedź. Ufność tego procesu umożliwia dowolnemu urządzeniu w segmencie zadeklarowanie posiadania poszukiwanej nazwy.

### Wektor Ataku
Aplikacja `Responder` uruchamia nasłuch na portach UDP dedykowanych dla protokołów rozgłoszeniowych. Oprogramowanie natychmiast odpowiada na każde wykryte zapytanie. Narzędzie podszywa się pod poszukiwany zasób sieciowy. Fałszywa usługa wymusza na kliencie przeprowadzenie procesu uwierzytelnienia za pomocą protokołu NTLMv2.

```bash
Responder -I eth0 -rwd
```

Moduł ofensywny przejmuje kontrolę nad procesem rozwiązywania nazw NetBIOS. Aktywowane flagi wymuszają agresywne odpowiedzi na zapytania stacji roboczych poszukujących nieistniejących serwerów plików.

### Analiza Wyniku
Oszukana stacja robocza przesyła skrót kryptograficzny hasła zalogowanego użytkownika do systemu napastnika. Moduł logujący zapisuje przechwycone poświadczenia w lokalnym pliku tekstowym. Zgromadzony materiał dowodowy podlega późniejszej analizie słownikowej z wykorzystaniem wyspecjalizowanych akceleratorów obliczeniowych. Odzyskane hasło w postaci jawnej otwiera drogę do centralnych usług domenowych.



## Fałszowanie tablic trasowania ARP

### Anatomia Protokołu
Protokół ARP odpowiada za mapowanie logicznych adresów IP na fizyczne adresy MAC wewnątrz warstwy łącza danych. Architektura mechanizmu nie uwzględnia weryfikacji autentyczności nadawcy. Stacje robocze bezwarunkowo aktualizują lokalne tablice pamięci podręcznej po otrzymaniu ramki odpowiedzi ARP. Brak mechanizmów uwierzytelniających umożliwia trywialne nadpisywanie powiązań adresowych.

### Wektor Ataku
Intruz generuje ciągły strumień spreparowanych pakietów ARP Reply. Transmisja informuje wybrany cel o zmianie fizycznego adresu bramy domyślnej. Złośliwe pakiety przypisują adres IP rutera do adresu MAC karty sieciowej audytora. Proces realizuje narzędzie `arpspoof`.

```bash
arpspoof -i eth0 -t 192.168.1.50 192.168.1.1
```

System operacyjny napastnika wymaga wcześniejszej aktywacji przekazywania ruchu sieciowego w celu uniknięcia odcięcia ofiary od zasobów internetowych. Pakiety docierające do sondy ulegają płynnemu przekierowaniu do właściwej bramy.

### Analiza Wyniku
Kompletny ruch sieciowy modyfikowanej stacji roboczej przepływa przez interfejs maszyny atakującej. Topologia logiczna ulega zmianie na model Man-in-the-Middle. Napastnik uzyskuje pełny wgląd w nieszyfrowane sesje komunikacyjne. Inspektor pakietów pozwala na swobodne wyodrębnianie ciasteczek sesyjnych oraz przechwytywanie transferowanych plików konfiguracyjnych.



## Omijanie izolacji za pomocą protokołu IPv6

### Anatomia Protokołu
Nowoczesne systemy operacyjne domyślnie preferują komunikację z wykorzystaniem stosu IPv6. Infrastruktura oparta wyłącznie na adresacji IPv4 ignoruje pakiety rozgłoszeniowe nowszej generacji. Urządzenia klienckie nieustannie emitują żądania konfiguracji sieciowej typu Router Solicitation. Brak autoryzowanych serwerów DHCPv6 otwiera przestrzeń do wprowadzenia fałszywych parametrów trasowania bez generowania alertów w systemach monitorujących ruch IPv4.

### Wektor Ataku
Oprogramowanie `mitm6` automatyzuje proces przejmowania kontroli nad lokalnym stosem IPv6. Aplikacja nasłuchuje żądań konfiguracyjnych i natychmiast wysyła spreparowane odpowiedzi.

```bash
mitm6 -i eth0 -d domena.lokalna
```

Sonda przypisuje stacjom roboczym adresację IPv6 oraz wyznacza własny interfejs jako główny serwer DNS. Aktywacja mechanizmu wymusza modyfikację preferencji sieciowych atakowanych hostów.

### Analiza Wyniku
Przejęcie roli serwera DNS skutkuje przechwyceniem wszystkich zapytań o nazwy domenowe. Napastnik weryfikuje ruch i selektywnie zatruwa odpowiedzi dla wewnętrznych zasobów korporacyjnych. Połączenia sieciowe ulegają przekierowaniu na adresy kontrolowane przez audytora. Zgromadzony ruch trafia do modułów logujących w celu ekstrakcji materiału kryptograficznego uwierzytelniającego usługi katalogowe.



## Manipulacja konfiguracją proxy (WPAD)

### Anatomia Protokołu
Protokół WPAD automatyzuje proces dystrybucji ustawień serwerów pośredniczących. Stacje robocze poszukują pliku konfiguracyjnego `wpad.dat` wykorzystując lokalne mechanizmy rozwiązywania nazw. Systemy operacyjne akceptują ścieżki dostarczone przez serwery DHCP za pośrednictwem opcji 252. Mechanizm dystrybucji nie wykorzystuje szyfrowania ani cyfrowych podpisów potwierdzających integralność konfiguracji.

### Wektor Ataku
Atakujący wdraża wewnątrz sieci złośliwy serwer HTTP hostujący wygenerowany plik konfiguracyjny. Następnie moduł ofensywny przechwytuje żądania LLMNR lub uruchamia fałszywą usługę DHCP.

```bash
Responder -I eth0 -rwdF
```

Oprogramowanie dystrybuuje złośliwe ustawienia proxy. Konfiguracja wymusza trasowanie ruchu HTTP przez węzeł kontrolowany przez intruza. Żądania dostępu do zasobów zewnętrznych spotykają się z błędem autoryzacji.

### Analiza Wyniku
Wymuszenie autoryzacji na fałszywym serwerze proxy generuje okno logowania na docelowej stacji roboczej. Próba nawiązania połączenia kończy się przesłaniem poświadczeń NTLM do maszyny atakującej. Zarejestrowane skróty kryptograficzne zasilają bazę danych modułu audytowego. Ekstrakcja ruchu HTTP pozwala na dodatkowe przechwycenie nagłówków autoryzacyjnych w postaci jawnej lub zakodowanej algorytmem Base64.



## Eksploracja Active Directory i mechanizmów RPC

### Anatomia Protokołu
Usługa katalogowa Active Directory centralizuje procesy uwierzytelniania w środowiskach domenowych. Domyślna konfiguracja starszych systemów dopuszcza nawiązywanie anonimowych sesji LDAP. Brak wymogu wstępnej autoryzacji pozwala na odpytywanie kontrolera domeny o bazowe struktury obiektów. Mechanizmy RPC udostępniają z kolei otwarte interfejsy służące do zdalnego zarządzania kontami.

### Wektor Ataku
Moduł rozpoznania nawiązuje bezpośrednie połączenie z portami usług katalogowych. Wykorzystanie pustego loginu oraz pustego hasła inicjuje sesję anonimową (Null Session). Narzędzie `rpcclient` pozwala na odpytanie serwera o obowiązujące zasady złożoności haseł.

```bash
rpcclient -U "" -N 192.168.1.100 -c "getdompwinfo; enumdomusers"
```

Zestawione polecenie wymusza zwrot parametrów polityki domenowej oraz pełnej listy zarejestrowanych użytkowników. Ustanowione połączenie funkcjonuje całkowicie poza systemami wykrywania intruzów.

### Analiza Wyniku
Kontroler domeny zwraca precyzyjne wartości minimalnej długości hasła oraz czasu blokady konta. Pozyskanie kompletnej listy identyfikatorów logowania (SID) umożliwia przygotowanie celowanego ataku słownikowego (Password Spraying). Zgromadzone dane pozwalają na wyeliminowanie ryzyka przypadkowego zablokowania kont administracyjnych podczas weryfikacji słabych poświadczeń w sieci.



## Analiza sesji SMB i indeksowanie zasobów

### Anatomia Protokołu
Protokół SMB realizuje funkcje udostępniania plików oraz drukarek sieciowych. Błędna konfiguracja uprawnień często umożliwia zestawienie sesji bez jakiegokolwiek uwierzytelnienia. Ufność tego mechanizmu pozwala niezalogowanym maszynom na eksplorację struktury katalogów. Brak restrykcyjnej kontroli dostępu do udziałów ułatwia lokalizację zapomnianych plików konfiguracyjnych.

### Wektor Ataku
Moduł audytowy inicjuje skanowanie dostępnych zasobów. Narzędzie `smbclient` weryfikuje możliwość anonimowego podłączenia do wykrytego udziału. Następnie wyspecjalizowane skrypty wykonują rekursywne przeszukiwanie drzewa katalogów (Spidering) pod kątem zdefiniowanych rozszerzeń. Oprogramowanie `netexec` automatyzuje ten proces w całych podsieciach.

```bash
nxc smb 192.168.1.0/24 -u '' -p '' --spider uwierzytelnianie --pattern '.*(kdbx|xml|config).*'
```

Aplikacja przeszukuje wszystkie odnalezione pliki pasujące do wyrażenia regularnego. Operacja przebiega równolegle na dziesiątkach serwerów.

### Analiza Wyniku
Zrzut ekranu terminala prezentuje listę niechronionych udziałów zidentyfikowanych w sieci. Narzędzia indeksujące zwracają pełne ścieżki do baz danych KeePass oraz skryptów wdrożeniowych zawierających hasła w postaci jawnej. Pozyskanie takich artefaktów omija całkowicie konieczność przeprowadzania złożonych ataków siłowych. Napastnik uzyskuje bezpośredni materiał uwierzytelniający gotowy do natychmiastowego wykorzystania w usługach docelowych.



## Detekcja znanych podatności (CVE)

### Anatomia Protokołu
Usługi warstwy siódmej udostępniają interfejsy programistyczne oraz panele administracyjne. Zaniedbania aktualizacyjne pozostawiają luki w kodzie źródłowym. Braki weryfikacji danych wejściowych umożliwiają wstrzykiwanie złośliwych instrukcji. Udokumentowane słabości oprogramowania podlegają kategoryzacji w globalnych bazach Common Vulnerabilities and Exposures.

### Wektor Ataku
Moduł audytowy wykorzystuje silnik `nuclei` do zautomatyzowanej weryfikacji podatności w aplikacjach webowych. Oprogramowanie wysyła spreparowane żądania HTTP na podstawie aktualizowanych szablonów diagnostycznych.

```bash
nuclei -u [https://192.168.1.50](https://192.168.1.50) -t cves/ -severity critical,high
```

Aplikacja ładuje tysiące sygnatur i bombarduje serwer docelowy ładunkami testowymi. Zawężenie skanowania do podatności krytycznych optymalizuje czas trwania analizy.

### Analiza Wyniku
Narzędzie weryfikuje nagłówki odpowiedzi oraz zawartość struktury DOM. Pozytywna identyfikacja sygnatury w zwracanym kodzie generuje alert bezpieczeństwa. Wynik zawiera precyzyjny identyfikator podatności oraz udokumentowany wektor eksploatacji. Potwierdzenie obecności luki ułatwia błyskawiczne wdrożenie dedykowanego exploita pozwalającego na zdalne wykonanie kodu (RCE).



## Weryfikacja autoryzacji baz danych

### Anatomia Protokołu
Nierelacyjne bazy danych optymalizują wydajność kosztem domyślnych mechanizmów bezpieczeństwa. Systemy instalowane w środowiskach lokalnych często ignorują całkowicie wymóg uwierzytelniania. Dostęp do otwartego portu usługi pozwala na bezpośrednią manipulację strukturą danych. Brak szyfrowania warstwy transportowej dodatkowo obnaża transferowane rekordy.

### Wektor Ataku
Sondowanie środowiska bazodanowego wymaga użycia wyspecjalizowanych skryptów diagnostycznych. Aplikacja `nmap` wykorzystuje wbudowany silnik NSE do interakcji z usługami.

```bash
nmap -p 6379,9200 --script redis-info,elastic-info 192.168.1.0/24
```

Skaner odpytuje standardowe porty usług Redis oraz Elasticsearch. Aktywowane skrypty próbują nawiązać anonimowe sesje komunikacyjne w całej zadeklarowanej podsieci.

### Analiza Wyniku
System zwraca pełną konfigurację usługi oraz metadane zgromadzonych indeksów. Wykrycie braku autoryzacji ułatwia ekstrakcję informacji wrażliwych. Zrzut zawartości pamięci podręcznej dostarcza tokeny sesyjne oraz klucze API. Zdobyte artefakty otwierają drogę do przełamania kolejnych systemów ochronnych wewnątrz infrastruktury biznesowej.



## Identyfikacja systemów przemysłowych (ICS/OT)

### Anatomia Protokołu
Urządzenia automatyki przemysłowej opierają komunikację na starych standardach projektowych. Protokoły produkcyjne ignorują kryptografię oraz bezpieczne uwierzytelnianie. Obecność sterowników PLC w nieizolowanych segmentach sieci biurowej wystawia proces technologiczny na trywialne ataki. Komputer zainfekowany złośliwym kodem uzyskuje bezpośredni dostęp do warstwy fizycznej przedsiębiorstwa.

### Wektor Ataku
Identyfikacja urządzeń OT wymaga odpytania specyficznych portów komunikacyjnych. Skrypty enumeracyjne ekstrahują banery sprzętowe bez zakłócania cyklu produkcyjnego.

```bash
nmap -p 102,502 --script s7-info,modbus-discover 192.168.1.0/24
```

Polecenie sondy kieruje zapytania diagnostyczne na porty wykorzystywane przez systemy Siemens S7 oraz protokół Modbus. Narzędzie pasywnie zbiera identyfikatory procesorów sterujących.

### Analiza Wyniku
Odpowiedź sterownika zawiera dokładny model urządzenia oraz numery seryjne poszczególnych modułów sprzętowych. Pozyskanie tych parametrów umożliwia dopasowanie precyzyjnych ładunków ofensywnych. Brak twardej segmentacji między siecią IT oraz siecią OT stwarza krytyczne zagrożenie dla ciągłości biznesowej. Intruz operujący z poziomu biura zyskuje możliwość ingerencji w logikę sterującą maszynami na hali produkcyjnej.

Wersja 2:
# Kurs testów infrastruktury wewnętrznej

## 00. Wprowadzenie do architektury sieciowej

### Model komunikacji

Sieć lokalna stanowi zbiór połączonych sieci lokalnych LAN (Local Area Network) wymieniających informacje na podstawie wielowarstwowego modelu odniesienia OSI (Open Systems Interconnection). Bezpieczeństwo wyższych płaszczyzn komunikacyjnych zależy bezpośrednio od integralności niższych warstw. Warstwa łącza danych odpowiada za transfer ramek wewnątrz tego samego segmentu. Warstwa sieciowa wyznacza trasy pakietów pomiędzy różnymi podsieciami. Testy penetracyjne infrastruktury wewnętrznej sprawdzają mechanizmy operujące na tych dwóch poziomach. Przejęcie kontroli nad fundamentami komunikacyjnymi skutkuje możliwością manipulacji wyższymi warstwami aplikacji.

### Domeny rozgłoszeniowe

Środowisko sieciowe wykorzystuje nieustanną transmisję rozgłoszeniową zwaną broadcastem. Urządzenia komunikują w ten sposób swoją obecność, poszukują usług oraz negocjują parametry trasowania. Nadmiarowy ruch ujawnia pełną topologię otoczenia. Pasywny nasłuch pozwala zmapować architekturę zachowując całkowitą ciszę sieciową.

### Zaufanie a autoryzacja

Przełączniki dostępowe weryfikują podłączony sprzęt na podstawie statycznych adresów fizycznych. Zaufanie do jawnie przesyłanych identyfikatorów eliminuje mechanizmy uwierzytelniania kryptograficznego. Protokoły sieciowe operujące w warstwie drugiej domyślnie ufają zadeklarowanym parametrom. Akceptacja nieszyfrowanych ramek pozwala na swobodne fałszowanie tożsamości sprzętowej.

### Metodyka audytu

Proces analityczny opiera się na metodyce zakładającej uprzednie naruszenie obwodu bezpieczeństwa znanej jako assume breach. Bezpośrednie podłączenie komputera do wewnętrznego gniazdka dostępowego całkowicie omija zewnętrzne zapory brzegowe. Testy penetracyjne prowadzą do znalezienia podatności lub lokalnych luk konfiguracyjnych.

---

## Rozdział I. Warstwa dostępowa

### 01. Pasywne mapowanie

Aktywne skanery sieciowe generują nadmiarowy ruch. Systemy zabezpieczeń powinny rejestrować tego typu anomalie. Z tego względu audyt rozpoczyna się od metod charakteryzujących się najniższym profilem widoczności, zapobiegając przedwczesnemu ujawnieniu obecności intruza. Przeprowadzenie ukrytego rekonesansu wymaga całkowitej eliminacji pakietów wychodzących.

Oprogramowanie analizuje ruch w warstwie łącza danych. Urządzenia sieciowe nieustannie emitują pakiety rozgłoszeniowe. Pasywny nasłuch rejestruje całą propagowaną komunikację, obejmując w szczególności komunikaty protokołu odwzorowania adresów ARP (Address Resolution Protocol). Proces ten pozwala zmapować lokalną topologię, identyfikując aktywne hosty przy całkowitym ominięciu wysyłania własnych ramek.

Do przeprowadzenia niejawnego skanowania środowiska wykorzystuje się polecenie.

```bash
netdiscover -i [INTERFACE] -p
```

`[INTERFACE]` Lokalny interfejs sieciowy podłączony do badanej infrastruktury.

```text
192.168.1.1     a0:36:9f:xx:xx:xx   Cisco Systems
192.168.1.10    b8:27:eb:xx:xx:xx   Raspberry Pi Foundation
192.168.1.55    00:15:99:xx:xx:xx   Samsung Electronics
```

Wyodrębnienie pierwszych trzech oktetów adresu sprzętowego, zwanych OUI, ujawnia producenta interfejsu sieciowego. Zestawienie dostarcza listę aktywnych adresów, ułatwiając odróżnienie urządzeń wbudowanych od elementów infrastruktury trasującej lub stacji roboczych. Zgromadzone informacje stanowią fundament ułatwiający późniejsze, aktywne skanowanie wyselekcjonowanych celów.

### 02. Omijanie kontroli dostępu

Zabezpieczenie portów dostępowych standardem IEEE 802.1X wymaga obsługi certyfikatów kryptograficznych. Jeżeli urządzenie nie obsługuje takich mechanizmów, administratorzy wdrażają wyjątki uwierzytelniające oparte wyłącznie na adresach fizycznych, znane jako MAC Authentication Bypass. Zaufanie do statycznego identyfikatora sprzętowego eliminuje całkowicie mechanizmy szyfrujące, co stanowi podatność.

Proces omijania blokady polega na sklonowaniu adresu dodanego przez administratora do listy wyjątków. Nasłuch pasywny z poprzedniej lekcji dostarcza identyfikator autoryzowanego urządzenia. Oprogramowanie modyfikuje adres fizyczny lokalnej karty sieciowej. Przełącznik weryfikuje podrobiony znacznik, otwierając dostęp do zastrzeżonej sieci.

Do przeprowadzenia zmiany tożsamości sprzętowej wykorzystuje się zestaw poleceń.

```bash
ip link set dev [INTERFACE] down
macchanger -m [MAC_ADDRESS] [INTERFACE]
ip link set dev [INTERFACE] up
```

`[INTERFACE]` Lokalny interfejs sieciowy podłączony do badanego gniazdka.

`[MAC_ADDRESS]` Przechwycony adres fizyczny zaufanego urządzenia.

Brak błędu w terminalu po podniesieniu interfejsu potwierdza skuteczną zmianę konfiguracji jądra systemu. Połączenie skutkuje uzyskaniem prawidłowej dzierżawy z serwera alokacji adresów DHCP (Dynamic Host Configuration Protocol). Komputer audytora zyskuje stabilny dostęp do infrastruktury gotowy do dalszej eksploracji.

### 03. Przełamywanie izolacji

Segmentacja logiczna służy do podziału sieci na odizolowane domeny transmisyjne, wykorzystując standard wirtualnych sieci lokalnych IEEE 802.1Q, zwanych VLAN. Przełączniki dostępowe często posiadają domyślnie aktywny protokół dynamicznej negocjacji magistrali, znany jako DTP. Akceptacja nieszyfrowanych pakietów konfiguracyjnych stanowi błąd architektoniczny umożliwiający ucieczkę z wyizolowanego segmentu.

Utworzenie wirtualnego podinterfejsu pozwala systemowi operacyjnemu na dekapsulację ruchu z obcych stref. Proces omijania blokady polega na emulacji zachowania urządzenia sieciowego. Oprogramowanie wysyła fałszywe ramki negocjacyjne, wymuszając zmianę stanu portu na połączenie magistralowe zwane trunkiem, transmitujące ruch wielu odrębnych sieci wirtualnych. Przełącznik zaczyna przesyłać na badany port pakiety opatrzone znacznikiem, trafiające bezpośrednio do zdefiniowanego podinterfejsu.

Do przeprowadzenia ataku oraz zmiany konfiguracji wykorzystuje się zestaw poleceń.

```bash
yersinia dtp -attack 1 -interface [INTERFACE]
ip link add link [INTERFACE] name [VLAN_INTERFACE] type vlan id [VLAN_ID]
ip link set dev [VLAN_INTERFACE] up
```

`[INTERFACE]` Główny interfejs fizyczny podłączony do przełącznika.

`[VLAN_INTERFACE]` Nazwa nowego podinterfejsu, na przykład eth0.10.

`[VLAN_ID]` Numer docelowej sieci przeznaczonej do infiltracji.

Brak błędu w terminalu po podniesieniu wirtualnego podinterfejsu potwierdza sukces operacji. Przełamanie segmentacji skutkuje natychmiastową widocznością obcych domen rozgłoszeniowych. Uruchomienie klienta dynamicznej alokacji adresów zapewnia pobranie prawidłowej dzierżawy z chronionej puli sieciowej.

---

## Rozdział II. Płaszczyzna sterowania

### 01. Zatruwanie adresacji L2

Protokół odwzorowania adresów ARP służy do zamiany logicznych adresów IP na fizyczne adresy sprzętowe MAC. Mechanizm ten funkcjonuje w sposób bezstanowy, pozbawiony sprzętowej weryfikacji nadawcy. Urządzenia sieciowe bezwarunkowo akceptują wszystkie odbierane komunikaty, aktualizując własne tablice pamięci podręcznej. Brak kryptograficznego uwierzytelniania tworzy podatność umożliwiającą przejęcie kontroli nad przepływem informacji wewnątrz pojedynczego segmentu sieci.

Proces manipulacji polega na emisji spreparowanych ramek przypisujących obcy adres sieciowy do fizycznego interfejsu. Oprogramowanie wysyła fałszywe pakiety jednocześnie do atakowanej stacji roboczej oraz do rutera. Nadpisane tablice adresacji wymuszają dwukierunkowe przekierowanie ruchu przez komputer audytora. Równoległe uruchomienie funkcji przekazywania pakietów na poziomie jądra systemu zapobiega przerwaniu łączności, podtrzymując ciągłość komunikacji sieciowej.

Do przeprowadzenia ataku typu pośrednik, zwanego Man in the Middle, wykorzystuje się zestaw poleceń.

```bash
echo 1 > /proc/sys/net/ipv4/ip_forward
arpspoof -i [INTERFACE] -t [TARGET_IP] [ROUTER_IP]
```

`[INTERFACE]` Lokalny interfejs sieciowy audytora.

`[TARGET_IP]` Adres logiczny atakowanej maszyny.

`[ROUTER_IP]` Adres logiczny rutera sieciowego.

Generowanie cyklicznych komunikatów w terminalu potwierdza rozpoczęcie nadawania ramek. Skuteczne nadpisanie tablic pamięci podręcznej skutkuje fizycznym przechwyceniem przesyłanych pakietów. Przejęcie roli węzła pośredniczącego umożliwia zrzut ruchu sieciowego do pliku oraz późniejszą ekstrakcję materiału uwierzytelniającego.

### 02. Kradzież poświadczeń przez rozgłoszeniowe rozwiązywanie nazw

System Windows, gdy nie potrafi rozwiązać nazwy hosta przez DNS, wysyła rozgłoszeniowe zapytanie do całego segmentu sieci, korzystając z protokołów zapasowych LLMNR i NBT NS. Mechanizm ten powstał dla wygody i nie zawiera żadnej weryfikacji odpowiadającego urządzenia. Każdy host w segmencie może odpowiedzieć twierdząco, podszywając się pod poszukiwaną nazwę.

Oprogramowanie nasłuchuje tego typu zapytania i odpowiada na każde z nich, niezależnie od tego czy poszukiwana nazwa rzeczywiście istnieje. Stacja robocza ofiary, przekonana że odpowiada legalny host sieciowy, automatycznie inicjuje proces uwierzytelnienia wobec komputera audytora, przesyłając zaszyfrowany skrót swojego hasła.

Do przeprowadzenia nasłuchu i zbierania materiału wykorzystuje się polecenie.

```bash
responder -I [INTERFACE]
```

`[INTERFACE]` Lokalny interfejs sieciowy nasłuchujący ruch rozgłoszeniowy segmentu.

Pojawienie się w logach wpisu ze skrótem w formacie NetNTLMv2 potwierdza przechwycenie materiału uwierzytelniającego. Skrót nie zawiera hasła w postaci jawnej, lecz nadaje się do łamania w trybie offline lub do bezpośredniego przekazania dalej w sieci w ramach ataku przekaźnikowego.

### 03. Przejęcie rozwiązywania nazw przez DHCPv6

Stos protokołu IPv6 pozostaje aktywny domyślnie na większości stacji Windows, nawet jeśli administratorzy sieci zarządzają wyłącznie adresacją IPv4. Monitoring bezpieczeństwa skupia się niemal wyłącznie na znanym, nadzorowanym protokole, pozostawiając równoległy kanał komunikacyjny całkowicie bez kontroli.

Oprogramowanie odpowiada na rozgłoszeniowe zapytania konfiguracyjne DHCPv6, zanim zrobi to legalny serwer sieciowy. Stacja robocza akceptuje odpowiedź komputera audytora jako prawowitą konfigurację, przyjmując go jako swój główny serwer rozwiązywania nazw. Cały późniejszy ruch związany z rozwiązywaniem nazw domenowych przepływa od tej pory przez audytora, niezależnie od ustawień interfejsu IPv4.

Do przeprowadzenia przejęcia wykorzystuje się polecenie.

```bash
mitm6 -d [DOMAIN]
```

`[DOMAIN]` Nazwa domeny wewnętrznej będącej celem audytu.

Sprawdzenie konfiguracji sieciowej stacji ofiary ujawnia zmieniony adres serwera DNS na interfejsie IPv6. Pozycja pośrednika w ruchu rozwiązywania nazw stanowi zazwyczaj punkt wyjścia do dalszej eskalacji wobec usług katalogowych domeny.

---

## Rozdział III. Eksploracja infrastruktury i Active Directory

### 01. Przeszukiwanie udziałów SMB

Wewnętrzne serwery plikowe gromadzą przez lata ogromne ilości dokumentów, kopii zapasowych i skryptów automatyzacji. Ręczne przejrzenie takiego zasobu jest niewykonalne, a jednocześnie to właśnie tam najczęściej trafiają zapomniane pliki zawierające poświadczenia.

Oprogramowanie łączy się z każdym hostem udostępniającym usługę SMB w badanym zakresie adresów, korzystając z sesji anonimowej lub gościnnej, która w wielu środowiskach pozostaje otwarta mimo braku jawnego zezwolenia. Narzędzie rekursywnie przeszukuje dostępne udziały, porównując nazwy napotkanych plików z listą wzorców wysokiego ryzyka.

Do przeprowadzenia przeszukania wykorzystuje się polecenie.

```bash
nxc smb [TARGET_RANGE] -u '' -p '' --spider [SHARE] --pattern [PATTERN]
```

`[TARGET_RANGE]` Zakres adresów IP badanej infrastruktury lub plik z listą hostów.

`[SHARE]` Nazwa udostępnionego zasobu podlegającego przeszukaniu.

`[PATTERN]` Wzorzec nazw plików wskazujących na obecność poświadczeń.

Wynikiem działania jest lista ścieżek do odnalezionych plików, obejmująca między innymi bazy haseł w formacie kdbx oraz skrypty administracyjne z twardo zakodowanymi danymi logowania. Odnalezione pliki stanowią materiał wyjściowy do dalszej części audytu.

### 02. Wyliczanie danych przez RPC

Dalsze działanie przeciwko kontrolerowi domeny wymaga znajomości listy kont oraz obowiązującej polityki haseł. Bez tej wiedzy jakakolwiek próba uwierzytelnienia niesie ryzyko zablokowania konta i ujawnienia obecności audytora.

Wiele kontrolerów domeny pozostawia otwartą możliwość nawiązania sesji bez podawania żadnych poświadczeń. Oprogramowanie wykorzystuje taką sesję do odpytania zdalnych procedur wywoływanych usługi katalogowej, uzyskując listę identyfikatorów kont oraz parametry obowiązującej polityki bezpieczeństwa haseł.

Do przeprowadzenia zapytań wykorzystuje się polecenie.

```bash
rpcclient -U "" -N [DC_IP]
```

Wewnątrz powłoki narzędzia wydawane są polecenia enumdomusers oraz getdompwinfo.

`[DC_IP]` Adres IP kontrolera domeny będącego celem zapytań.

Zwrócona lista kont użytkowników oraz próg blokady konta pozwalają zaplanować kolejny etap audytu w sposób nienarażający żadnego konta na zablokowanie.

---

## Rozdział IV. Weryfikacja baz danych

### 01. Wykrywanie otwartych instancji

Bazy pamięci podręcznej oraz dokumentowe bazy danych wdrażane są często wewnątrz sieci wewnętrznej bez włączonego uwierzytelniania, ponieważ administratorzy zakładają że sama izolacja sieciowa stanowi wystarczające zabezpieczenie. Założenie to jest błędne w modelu zakładającym wcześniejsze naruszenie obwodu.

Narzędzie skanuje badany zakres adresów pod kątem domyślnych portów tego typu usług i nawiązuje próbne połączenie bez podawania żadnych poświadczeń. Udana odpowiedź serwisu potwierdza brak jakiejkolwiek bariery dostępu.

Do przeprowadzenia skanowania wykorzystuje się polecenie.

```bash
nmap -p 6379,27017 --open [TARGET_RANGE]
```

`[TARGET_RANGE]` Zakres adresów IP badanej infrastruktury.

Lista hostów zwróconych jako otwarte wskazuje instancje gotowe do bezpośredniego połączenia bez logowania. Każda z nich wymaga dalszej weryfikacji zawartości.

### 02. Weryfikacja danych wrażliwych

Sam fakt otwartego dostępu do instancji nie potwierdza jeszcze obecności danych wrażliwych. Potwierdzenie realnego ryzyka wymaga bezpośredniego zbadania przechowywanej zawartości.

Oprogramowanie klienckie łączy się z odnalezioną instancją bez podawania poświadczeń, wylistowuje dostępne struktury danych i odczytuje próbkę rekordów w poszukiwaniu treści wskazujących na dane osobowe, tokeny sesji lub poświadczenia zapisane w postaci jawnej.

Do połączenia z bazą pamięci podręcznej wykorzystuje się polecenie.

```bash
redis-cli -h [TARGET_IP] KEYS '*'
```

Do połączenia z bazą dokumentową wykorzystuje się polecenie.

```bash
mongosh --host [TARGET_IP] --eval 'db.adminCommand({listDatabases:1})'
```

`[TARGET_IP]` Adres IP odnalezionej, otwartej instancji bazy danych.

Zwrócona lista kluczy lub baz danych wraz z próbką ich zawartości pozwala jednoznacznie ocenić skalę ryzyka związanego z pozostawieniem instancji bez uwierzytelniania.
