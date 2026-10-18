# Kurs Pensec — kontrakt redakcyjny

Jedno źródło prawdy dla pisania kursu. Każdy rozdział i każda lekcja powstają wg tych
zasad. Dokument jest roboczy — zmieniamy go świadomie, a nie przez pojedynczą lekcję.

---

## 1. Czym jest ten kurs

**Pozycjonowanie:** specjalistyczny kurs **rozpoznania i oceny ekspozycji infrastruktury
wewnętrznej** — metodyka realnego audytu, wykonywana ręcznie, z naciskiem na terminal i
składnię komend. Po polsku.

**DNA:** „budując narzędzie audytowe nauczyłem się tych technik i przekładam je na ręczne
rzemiosło". Kurs odwzorowuje **powierzchnię testów** tego narzędzia i uczy ich ręcznego
wykonania.

**Czym NIE jest:** nie jest pełnym kursem pentestera. Świadomie poza zakresem: ataki na
Active Directory (Kerberoasting, BloodHound, delegacje), eskalacja uprawnień, lateral
movement, pivoting, ręczny web-pentest, wieloetapowy projekt końcowy. To materiał na
ewentualne osobne kursy.

**Certyfikat** stwierdza ukończenie szkolenia z technik rozpoznania i oceny ekspozycji —
nie „pentestera".

## 2. Zasada naczelna: wierność narzędziu + głos audytora

Kurs uczy tego, co narzędzie audytowe realnie robi, jego realnymi narzędziami. A narzędzie
jest **pasywne / read-only / obserwacyjne**:

- **wykrywa i obserwuje** podatność, ale zwykle **jej nie potwierdza** od swojej strony,
- **łapie, nie łamie** (materiał uwierzytelniający zapisuje, nigdy nie łamie ani nie
  przekazuje dalej),
- **czyta, nie zmienia** (nazwy plików, banery — nigdy zapis).

Dlatego **głos kursu to audytor, nie napastnik.** Piszemy „operator wykrywa / obserwuje /
weryfikuje / dokumentuje", nie „intruz przejmuje / włamuje się". Zakazane jest ramowanie
„atak zakończony sukcesem".

**Nigdy nie wymieniamy sondy ani nazwy Pensec w treści lekcji.** Perspektywa: operator
wykonujący ocenę ręcznie.

## 3. Źródła wiedzy i poprawność

1. **Kod narzędzia = prawda.** Komendy, flagi, porty i techniki wyprowadzamy z realnej
   implementacji (`rpi-pentest/src/pensec/`). Nie wymyślamy komend. Jeśli komenda jest
   budowana dynamicznie, piszemy to wprost.
2. **Referencje** dla mechanizmów: dokumentacja narzędzi, RFC, MITRE ATT&CK.
3. **Walidacja bez labu.** Nie ma środowiska do uruchamiania komend. Ground truth to kod
   sondy (dokładne wywołania) oraz kanoniczna dokumentacja narzędzi; outputy modelujemy na
   realnym formacie narzędzi. Komend nie przedstawiamy jako „zweryfikowanych w terminalu".
   Komenda spoza kodu sondy zostaje oznaczona jako oparta na dokumentacji. Walidacja na żywo
   jest opcjonalna, gdyby lab się pojawił.

## 4. Reguły języka

- **Bez myślników** (— –) w prozie. Zdania proste lub przecinek.
- **Bez dwukropków i średników w prozie.** Zdanie łączymy kropką albo przecinkiem, nie
  dwukropkiem ani średnikiem. Jedyny dozwolony dwukropek **wprowadza polecenie lub składnię**
  tuż przed blokiem komendy, wzorem książki: „…służy następujące polecenie:” a pod spodem blok.
  Średnik występuje wyłącznie **wewnątrz komendy** (terminator instrukcji SQL), nigdy w prozie.
- **Nawiasy wyłącznie dla terminu angielskiego** przy pierwszym użyciu: `Rules of
  Engagement`, `handshake`, `null session`. Nie dla dopowiedzeń.
- **Unikamy „które/która/którego…"** — przebudowa na zdanie proste albo imiesłów.
- **Styl techniczny, bezosobowy, nominalny.** Rzeczowo, bez efekciarstwa i bez drugiej osoby.
- **Jeden desygnat = jeden termin** (słownik, sekcja 12). Raz wybrana forma obowiązuje w
  całym kursie.
- Terminy angielskie przy pierwszym użyciu kursywą w nawiasie, dalej normalnie.

## 4a. Wzorzec głosu (kalibracja na fragmentach książki)

Fragmenty książki autora potwierdzają i doprecyzowują §4. Naśladujemy **sposób
wyjaśniania**, nie treść (książka jest o SQL) i nie jej długość.

- **Otwarcie przez definicję i cel.** Sekcja zaczyna się od zwięzłej definicji mechanizmu z
  terminem angielskim w nawiasie, potem mówi, po co to jest i dlaczego ma znaczenie, dopiero
  potem przechodzi do działania. Wzór: „Indeks (*index*) jest dodatkową strukturą danych…
  Jego funkcją jest…".
- **Rejestr nominalny, bezosobowy:** „stosuje się", „należy pamiętać", „system zwraca",
  „zaleca się". Nigdy drugiej osoby, nigdy myślnika.
- **Świadomość konsekwencji i kompromisu.** Przy każdej operacji książka mówi, co pójdzie źle
  i jaka jest dobra praktyka. To naturalny nośnik zasady „zakazane skróty myślowe" i
  remediacji — amplifikujemy go.
- **Spokojne łączniki, jedno pojęcie na zdanie:** „Następnie", „Z tego względu", „W praktyce",
  „Natomiast", „Należy jednak uwzględnić", „Istotne jest".
- **Puenta decyzyjna:** „Wybór między X a Y zależy od…". Wprost służy DNA „ucz decyzji" i
  egzaminowi decyzyjnemu.
- **Nie przenosimy:** ramek egzaminu INF.03, operacji destrukcyjnych jako techniki, zrzutów
  ekranu (zastępuje je komentowany output), dłużyzn i powtórzeń.

## 5. Schemat merytoryczny każdej techniki

Prowadzimy czytelnika schematem:

```
Założenie → działanie → obserwacja → interpretacja → walidacja → wpływ
```

**Zakazane skróty myślowe** (muszą być wprost obalone tam, gdzie grożą):

- „działanie narzędzia = udany atak",
- „otwarty port = podatność",
- „skrót = hasło",
- „wynik skanera = dowód" (skaner może kłamać: nmap kończy kodem 0 po przekroczeniu czasu;
  `VULNERABLE: … State: UNKNOWN`).

Absencja jest wnioskiem tylko przy dowiedzionej kompletności pokrycia (stopki `arp-scan`/
`nmap`). Słaba ewidencja nie staje się werdyktem — zostaje obserwacją wymagającą
potwierdzenia.

## 6. Konwencja komend

- Komendy w blokach ` ```bash `, przykładowy output w ` ```text `.
- **Każdy parametr zmienny jako `[PLACEHOLDER]`** wielkimi literami w bloku komendy. Pod blokiem
  **nie dajemy słownika w tabelce ani tersnej listy flag z separatorem `·`** — całą składnię
  objaśniamy ciągłą prozą (sekcja „Wzorzec prezentacji komendy”).
- Output realistyczny i zanonimizowany (`xx:xx:xx`, `192.168.x.x`).
- **Używamy realnych narzędzi narzędzia audytowego**, nie „hollywoodzkich":

  | Obszar | Realne narzędzia |
  |---|---|
  | Mapowanie L2 | `arp-scan`, `ip neigh`, `tshark -z endpoints,ip` |
  | Switch/L2 | scapy (sniff DTP/STP, Dot1Q single/double-tag, STP BPDU) |
  | MAC | `macchanger -A` (tylko stealth, opcjonalnie) |
  | Nazwy/poświadczenia | `Responder -A` (pasywny), `Responder -d -w` (aktywny), `arping -U`, `mitm6`, `getent`, `nmap broadcast-dhcp-discover` |
  | Rozpoznanie | `nmap -sn`, `nmap -sV -O`, `nmap -sU` (snmp/nbstat/upnp), `ssl-cert`/`ssl-enum-ciphers`, `curl` |
  | Tożsamość/usługi | `nmap smb-enum-shares`, `mount -t cifs`, `smbmap`, `rpcclient`, `nmap ldap-search` |
  | Bazy/podatności | `nmap` NSE (`redis-info`, `mongodb-info`…), `curl`, `hydra`, `nmap --script vuln`, `nuclei`, `dirb` |
  | Wi-Fi | `ip`/`iw` (monitor mode), scapy (pasywny EAPOL → PCAP) |
  | OT/ICS | `nmap -sS -sU -sY` + NSE (`s7-info`, `modbus-discover`…), raw-socket banery |

  **Nie uczymy:** `arpspoof`/`ettercap`, `yersinia`, `nxc`/`netexec`, `hcxdumptool`,
  `hashcat`/`aircrack`, `impacket`/NTLM relay, BloodHound, `redis-cli`/`mongosh` jako
  podstawy — narzędzie ich nie używa.

### Wzorzec prezentacji komendy (styl książki, pełny)

Najmocniejszy nośnik stylu książki, przejmujemy go **w całości**. Komendę wprowadzamy, pokazujemy
jej składnię w bloku, a następnie w **ciągłej prozie** przechodzimy przez każdy element od lewej do
prawej, mówiąc **co dany przełącznik robi i co operator w jego miejsce podstawia**. Nie zostawiamy
wiszącego placeholdera bez objaśnienia ani tersnej listy flag po przecinku. Składnię tłumaczymy tak,
jak książka tłumaczy `SELECT` czy `UPDATE`: „Po słowie kluczowym X podaje się…”, „Kolejnym
elementem składni jest…”, „Opcjonalnie można zastosować…”.

1. **Zdanie wprowadzające** — co polecenie ustala i po co.
2. **Składnia w bloku** ` ```bash ` ze zmiennymi jako `[PLACEHOLDER]`.
3. **Objaśnienie prozą, element po elemencie** — każdy przełącznik i każdy placeholder opisuje
   zdanie, a nie tabelka ani lista z separatorem.
4. **Komentowany output** — blok ` ```text ` z wynikiem i krótką interpretacją linii obok (co jest
   informacją, co obserwacją, co wymaga potwierdzenia).

Wzór (lekcja nmap):

> Do rozpoznania wersji usług i systemu służy następujące polecenie:
> ```bash
> sudo nmap -sV -O -Pn -p [PORTS] [TARGET]
> ```
> Przełącznik `-sV` uruchamia rozpoznanie wersji usług, dzięki czemu nmap nawiązuje połączenie z
> otwartym portem i odczytuje baner zamiast poprzestawać na samym numerze. Przełącznik `-O` włącza
> szacowanie systemu operacyjnego na podstawie charakterystyki stosu sieciowego. Przełącznik `-Pn`
> pomija ponowne wykrywanie hosta, przydatny wtedy, gdy aktywność adresu ustalono we wcześniejszym
> etapie. Po przełączniku `-p` podaje się listę portów rozdzielonych przecinkami, na przykład
> `445,139,3389`, albo znak `-` oznaczający pełny zakres od 1 do 65535. Ostatnim elementem składni
> jest adres badanego hosta.
> (następnie komentowany output z interpretacją)

## 6a. Formatowanie treści i gęstość (z recenzji M0)

- **Bloki ` ``` ` są wyłącznie dla komend, składni i realnego outputu terminala.** Przepływy
  pojęć, fazy, wyliczenia taktyk, kroki procesu i „diagramy strzałkowe" zapisujemy **ciągłą
  prozą**, nie w bloku kodu. Zwykłej listy markdown używamy rzadko, tylko dla krótkich,
  równorzędnych pozycji. Wyjątek: schematyczny rysunek architektury laboratorium jako figura
  do zerknięcia.
- **Każdą rzecz mówimy raz.** Bez powtarzanego boilerplate (np. o zakresie kursu czy
  upoważnieniu) w wielu lekcjach. Jedno świadome miejsce.
- **Etykę i umowy przekazujemy subtelnie**, w naturalnym punkcie (lekcja o zasadach
  zaangażowania oraz później przy komendzie ustalającej uzgodniony zakres), a nie jako
  wyróżniony, powtarzany apel. Głos audytora podaje fakty; upoważnienie jest praktycznym
  ograniczeniem ustalonym raz.
- **Gęstość.** Każda lekcja niesie treść istotną i nieoczywistą. Zamiast rozwadniać cienką
  lekcję, łączymy ją lub tniemy. Unikamy fragmentów, które czytelnik przetwarza bez zysku.
- **Bez wstępu ogłaszającego ważność.** Nie piszemy, że coś jest ważne ani „stanowi fundament
  do…". Dobre wytłumaczenie samo pokazuje wagę. Lekcja zaczyna się od rzeczy, nie od zapowiedzi
  rzeczy.
- **Fundamenty też pokazują składnię.** Lekcja fundamentu zawiera polecenie *odczytujące*
  mechanizm (podgląd tablicy sąsiadów, tablicy trasowania, adresacji interfejsu), read-only.
  Technika ofensywna pozostaje w swoim module.
- **Bez doklejanego pytania na końcu lekcji.** Element interaktywny to osobny checkpoint na
  końcu rozdziału (silnik egzaminu), nie zdanie-pytanie w prozie.

## 7. Szkielety lekcji

Szkielet to **lista beatów do pokrycia, a nie zestaw sztywnych, powtarzanych nagłówków.**
Nagłówki dobieramy naturalnie do treści danej lekcji i różnicujemy je między lekcjami; krótka
lekcja może płynąć bez nagłówków. Nie stemplujemy tych samych tytułów przez cały kurs. W
lekcjach techniki stosujemy spójnie **naturalne, treściowe tytuły sekcji** nazywające omawianą
rzecz (jak w podręczniku), a nie generyczne etykiety „Mechanizm / Procedura". Krótkie lekcje
koncepcyjne (fundamenty) płyną bez nagłówków.

**Lekcja ataku/testu (6 sekcji):**
1. **Mechanizm** — jak działa protokół/technologia.
2. **Warunek podatności** — kiedy test może coś wykazać (bardzo ważne).
3. **Procedura** — komendy (`[PLACEHOLDER]` + objaśnienie prozą, element po elemencie).
4. **Obserwacja i interpretacja** — co output znaczy, a czego nie.
5. **Walidacja i wpływ** — jak potwierdzić i co to realnie dowodzi.
6. **Remediacja** — jedno zdanie obronne.

**Lekcja fundamentu (3 sekcje):**
1. **Mechanizm** · 2. **Znaczenie dla oceny** · 3. **Pojęcia** (ewentualny output).

Lekcja nie powtarza tytułu — tytuł renderuje strona z `structure.json`. Zaczynamy od
akapitu; nagłówki `##`/`###` to śródsekcje.

## 8. Interaktywność

- **Checkpoint po lekcji** — 1–3 pytania samosprawdzające (silnik egzaminu), format jak
  egzamin, ale lekki.
- **Komentowany output** — output z wyróżnieniem linii i krótką interpretacją obok („ta
  linia to informacja, nie wynik"). Realizacja techniczna do ustalenia przy pierwszej
  lekcji M4/M5, gdzie wchodzi pierwszy bogaty output.

## 9. Egzamin: laboratorium decyzyjne (budowane po treści)

Egzamin to **ciągły scenariusz liniowy** jednej oceny: kroki następują po sobie, kontekst
się przenosi, czytelnik wskazuje właściwą komendę, interpretuje wynik, podejmuje następny
krok lub ocenia „finding czy fałszywy alarm". Ocena po stronie serwera per krok, próg,
certyfikat. Bez pełnego rozgałęziania. Budujemy po napisaniu treści; `exam.json` zmieni się
wtedy na format scenariusza z krokami.

## 10. Mapa modułów i lekcje

Status: ✓ jest · ↻ rewizja · ＋ nowe. Granularność do dostrojenia w trakcie.

- **M0 Metodyka i bezpieczeństwo oceny** — ocena ≠ włamanie ↻, RoE i zakres ✓, Assume Breach
  ✓, ＋profile działania (SAFE→FULL), fazy ↻ (granica: do walidacji, nie dalej), ścieżka
  ekspozycji ↻, MITRE ✓, evidence ✓, ostrożna interpretacja ✓, bezpieczne działania/OT ✓,
  laboratorium ↻, proces ↻.
- **M1 Fundamenty sieciowe** ✓ — Ethernet, ARP, IPv4, switching, VLAN, DHCP, DNS, IPv6, porty.
- **M2 Warstwa dostępowa i L2** — pasywne mapowanie (`arp-scan`) ↻, top-talkers ＋, DTP/STP ＋,
  BPDU-guard ＋, MAC/OUI i słaba tożsamość ↻, test VLAN hopping (Dot1Q) ↻.
- **M3 Protokoły lokalne i poświadczenia** — MITM susceptibility (`arping -U`) ↻, LLMNR/NBT-NS
  ↻, Responder pasywny ↻, NetNTLMv2 ＋, DHCP/WPAD aktywny ↻, WPAD ↻, rogue DHCP ＋, IPv6/mitm6 ↻.
- **M4 Rozpoznanie i enumeracja usług** ＋ — recon vs enum, host discovery, liveness, skan
  `-sV -O`, interpretacja stanów, NSE, fingerprint, SNMP, UPnP/mDNS/NetBIOS, TLS/SSL.
- **M5 Tożsamość i usługi katalogowe** ↻ — SMB udziały, SMB spider ro, SMB loot (`smbmap`),
  RPC null session, LDAP anonymous.
- **M6 Usługi, bazy i podatności** ↻＋ — otwarte usługi, Redis/Memcached, MongoDB,
  Elastic/Couch/Influx/Prometheus, Oracle/DB2/Neo4j, relacyjne (`hydra`), MSSQL OOB, radar PII,
  NSE `vuln`, nuclei, side-effecty szablonów, korelacja CVE/EOL + weryfikacja findingu.
- **M7 Specjalistyczne** ＋ — Wi-Fi (monitor, EAPOL pasywny), OT/ICS (safe scanning, wykrywanie,
  banery raw).
- **M8 Diagnostyka, dowód i raport** ＋ — diagnostyka sieci, od obserwacji do findingu, severity
  bez przeszacowania, struktura raportu.

Razem ~55–65 lekcji.

## 11. Rewizja istniejącej treści i egzaminu

- **M0:** przeramować fazy/ścieżkę/laboratorium/proces z pełnego pentestu na granicę oceny
  (recon → enumeracja → analiza → ostrożna walidacja → evidence → raport); eksploatację,
  lateral movement i pivoting opisać jako etapy spoza zakresu tej oceny, wymagające osobnych
  uprawnień. Dodać lekcję o profilach działania.
- **Przepisać lekcje:** 03-plaszczyzna/01 (`arpspoof` → `arping -U`), 05-tozsamosc/01
  (`nxc` → `smbmap`/`mount`/NSE), 06-aplikacje/02 (`redis-cli`/`mongosh` → NSE + `curl`);
  w 03-plaszczyzna/02–03 zmienić głos na obserwacyjny.
- **Egzamin — poprawić 4 pytania:** Q1 (Wi-Fi pasywny, nie „wymusza handshake"), Q3 (brak MAB
  bypass → MAC jako słaba tożsamość), Q4 (VLAN = Dot1Q tagging, DTP pasywnie), Q7 (`arping -U`,
  nie `arpspoof` MITM).

## 12. Słownik terminów (zasiew, uzupełniamy w trakcie)

Jedna forma na pojęcie:

- skrót **NetNTLMv2** (nie „hash", nie „hasło") — materiał uwierzytelniający, nie hasło.
- **udział** SMB (*share*).
- **sesja anonimowa** (*null session*).
- **ocena ekspozycji** / **badanie** (nie „atak", nie „włam").
- **operator** / **audytor** (nie „intruz", „napastnik").
- **podatność** (*vulnerability*); **finding** (ustalenie) — ustalenie w raporcie.
- **pasywny** / **aktywny** / **intruzywny** — profile działania.
- **handshake** (zostaje po angielsku, kursywą przy pierwszym użyciu).
- **rozpoznanie** (*reconnaissance*) vs **enumeracja** (*enumeration*).

## 13. Kolejność pracy

Rozdziałami, po kolei, zaczynając od M0. Nie piszemy wszystkiego naraz. Każdy rozdział:
napisanie wg szkieletów → (lekcje z komendami) walidacja w terminalu → checkpointy →
aktualizacja `structure.json`. Egzamin na końcu, po treści.
