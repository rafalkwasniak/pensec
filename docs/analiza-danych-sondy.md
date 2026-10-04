# Analiza danych przesyłanych przez sondę Raspberry Pi

Dokument opisuje, co sonda faktycznie wysyła do API Pensec, jakie ma to słabe punkty
i błędy oraz jak je naprawić. Napisany po polsku, bo adresatem jest osoba pracująca
nad firmware urządzenia (reszta `docs/` jest po angielsku).

**Data analizy:** 2026-08-30
**Materiał źródłowy:**

- 26 raportów zapisanych w bazie produkcyjnej (`reports.id` 3–28, od 2026-08-16 do 2026-08-30),
- logi dostępowe serwera z okresu 17–27 sierpnia 2026 (`logs/Aug-2026.tar.gz*`),
- kod przyjmujący raport (`app/Services/ReportIntake.php`, `SubmitReportRequest`, `ReportController`),
- raport referencyjny `docs/raport.json` i specyfikacja `docs/specyfikacja.md`.

Analiza dotyczy **urządzenia**, nie API. Tam, gdzie sensowna jest zmiana po stronie
serwera, jest to wyraźnie zaznaczone w rozdziale 7.

---

## 1. Podsumowanie

| # | Usterka | Waga | Gdzie naprawić |
|---|---|---|---|
| A1 | Sonda wysyła ten sam raport w pętli tysiące razy (19 135 zbędnych żądań w 10 dni) | **krytyczna** | urządzenie |
| A2 | Skan uruchamia się dwa razy równolegle — dwa raporty z tego samego przebiegu, wzajemnie się zakłócające | **krytyczna** | urządzenie |
| B1 | `scan_time` nie jest czasem skanu — potrafi być starszy o 4 godziny lub 4 dni | **wysoka** | urządzenie |
| C1 | Nuclei nie zadziałał **ani razu** w żadnym z 26 raportów; od raportu 15 wysyła zrzut awarii Go | **wysoka** | urządzenie |
| C2 | Cztery moduły (`smb_null_sessions`, `default_credentials`, `ldap_leaks`, `infrastructure_risks`) zawsze puste lub nieobecne | **wysoka** | urządzenie |
| C3 | Moduł ICS/OT generuje fałszywe wykrycia — do 21 „protokołów przemysłowych" na jednym hoście z portów UDP bez odpowiedzi | **wysoka** | urządzenie |
| C4 | Parser wyjścia nmap skleja linie — pole `nmap_service` zawiera adres MAC i wyniki innego portu | **średnia** | urządzenie |
| C5 | 16% hostów wykrytych przez ARP nmap uznaje za wyłączone i pomija — bez śladu w raporcie | **średnia** | urządzenie |
| C6 | Skan obejmuje 36 portów TCP, skan „głęboki" dokładnie 5; zero portów UDP poza ICS | **średnia** | urządzenie |
| C7 | `dns_health` mierzy 8.8.8.8, a nie resolver klienta | **średnia** | urządzenie |
| C8 | `broadcast_poisoning_risks` zwraca w 26 raportach identyczne zdanie — moduł nic nie mierzy | **średnia** | urządzenie |
| C9 | Nasłuch tshark łapie 2–15 pakietów; okno pomiarowe za krótkie, żeby cokolwiek znaczyło | **średnia** | urządzenie |
| C10 | Pomiar pasma (speedtest) obciąża łącze klienta i przy dwóch przebiegach daje wyniki 5× rozbieżne | **niska** | urządzenie |
| D1 | Brak wersji formatu raportu, wersji firmware i identyfikacji przebiegu | **wysoka** | urządzenie + kontrakt |
| D2 | Te same klucze zmieniają typ między wersjami firmware (zdanie ↔ obiekt flag) | **wysoka** | urządzenie |
| D3 | Puste kolekcje serializowane jako `[]` zamiast `{}` | **niska** | urządzenie |
| D4 | Komunikat błędu Nuclei obcinany **od początku** — ginie właściwa treść błędu | **średnia** | urządzenie |
| D5 | Mieszanka języków i polszczyzna bez znaków diakrytycznych | **niska** | urządzenie |
| E1 | API nie loguje odrzuconych zgłoszeń — 50 błędów 422 bez możliwości diagnozy | **średnia** | API |
| E2 | Powtórne przyjęcia (HTTP 200) są niewidoczne w panelu | **średnia** | API |

Skala problemu w liczbach: w logowanym okresie 17–27 sierpnia sonda wykonała
**19 281 żądań POST `/api/v1/reports`**, z czego **24 utworzyły nowy raport**.
Pozostałe 19 257 to powtórki, odrzucenia i limity. Przy raporcie ważącym ~50 kB
oznacza to około **0,9 GB** danych wysłanych bez potrzeby — z łącza klienta, na
którym sam speedtest sondy pokazywał 2–8 Mbit/s uploadu.

---

## 2. Co sonda wysyła dzisiaj

Ciało żądania to `{"report_id": "<uuid>", "report": { … }}`. Dokument `report`
ma stabilny zestaw kluczy najwyższego poziomu:

```
scan_time                  string   "2026-08-30 08:40:59" (bez strefy)
orchestrator_ip            string   IP sondy w sieci klienta
discovered_hosts_count     int
hosts                      list     lista IP, kolejność losowa
diagnostics                object   9–11 pól, opisane niżej
nmap_results               object   IP → surowy tekst z nmap
deep_vulnerabilities       object   IP → surowy tekst z nmap + NSE
nuclei_results             object   IP → {host: {...}, web: [...]}
service_fingerprints       object   IP → lista portów
broadcast_poisoning_risks  list     wpisy {type, severity, assessment}
ics_ot_risks               object   IP → lista portów ICS
smb_null_sessions          []       zawsze puste
default_credentials        []       zawsze puste
ldap_leaks                 []       zawsze puste
infrastructure_risks       []       puste w 24 z 26 raportów
```

Rozmiar dokumentu: 12,7–57,3 kB. Limit API to 32 MB, więc rozmiar nie jest
problemem — problemem jest **co** ten rozmiar wypełnia (rozdział 5.1).

---

## 3. Warstwa transportowa

### A1. Pętla wysyłkowa bez warunku zakończenia — **krytyczne**

Sonda wysyła ten sam raport co ~10–11 sekund i nie przestaje mimo poprawnej
odpowiedzi serwera. Statystyka POST-ów na `/api/v1/reports` z logów 17–27 sierpnia:

| Kod | Liczba | Znaczenie |
|---|---:|---|
| 201 | 24 | raport zapisany po raz pierwszy |
| 200 | 19 135 | „ten raport już mam" (idempotencja) |
| 422 | 50 | żądanie odrzucone jako niepoprawne |
| 429 | 72 | przekroczony limit 30 żądań/min |

Najdłuższe serie:

```
18/08 14:06 → 19/08 12:03    21,9 h    7 180 żądań   (jeden raport)
25/08 18:24 → 26/08 09:50    15,4 h    4 897 żądań
26/08 14:01 → 26/08 21:23     7,4 h    2 323 żądań
19/08 12:31 → 19/08 18:18     5,8 h    1 891 żądań
```

Klientem jest `curl/8.14.1`. API odpowiada jednoznacznie: **201** = zapisano,
**200** = już był zapisany, oba z `"success": true` i pełną kartą raportu w ciele.
Urządzenie ma więc wszystko, czego potrzebuje, żeby przerwać pętlę — nie korzysta
z tego. Najbardziej prawdopodobne przyczyny: pętla `while` czekająca na kod, który
nigdy nie przyjdzie (np. tylko `201`), albo `curl` bez `--fail`/bez sprawdzania
`-w '%{http_code}'`, przez co skrypt nie odróżnia sukcesu od błędu.

**Naprawa (urządzenie):**

1. Traktować **każdą odpowiedź 2xx jako sukces końcowy** i usuwać raport z kolejki:
   ```bash
   code=$(curl -sS -o /tmp/resp.json -w '%{http_code}' \
       -X POST https://pensec.top/api/v1/reports \
       -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
       --max-time 120 --data @"$REPORT")
   case "$code" in
     2*)   rm -f "$REPORT" ;;                  # 200 i 201 kończą sprawę
     4*)   mv "$REPORT" "$FAILED_DIR"; log "odrzucony ($code): $(cat /tmp/resp.json)" ;;
     *)    retry_with_backoff ;;               # 5xx, timeout, brak sieci
   esac
   ```
2. Ponawiać **tylko** przy błędzie sieci i 5xx, z opóźnieniem wykładniczym
   (30 s → 1 min → 2 min → … → 30 min) i twardym limitem prób (np. 12) lub czasu (24 h).
3. Po 429 czekać tyle, ile mówi nagłówek `Retry-After`.
4. Kod 4xx (poza 429) **nigdy** nie kwalifikuje się do ponowienia — żądanie jest
   niepoprawne i kolejne 5 000 prób tego nie zmieni. Raport odkładać do katalogu
   „do przeglądu" i zapisać treść odpowiedzi w logu urządzenia.

### A2. Dwa równoległe przebiegi skanu — **krytyczne**

Raporty 20/21, 22/23, 24/25 i 26/27 to **pary**: ten sam `orchestrator_ip`, ten sam
`scan_time` co do sekundy, nakładające się znaczniki czasu nmap, ale **różne** listy
hostów, różne wyniki i osobne `report_id`.

```
20  orch=192.168.100.158  scan_time=2026-08-26 20:05:50  nmap 20:07…20:09
21  orch=192.168.100.158  scan_time=2026-08-26 20:05:50  nmap 20:07…20:10
    pasmo: 25,63 Mbit/s  vs  23,68 Mbit/s   (dwa speedtesty naraz)
    latencja avg: 1,695 ms  vs  6,717 ms
24  pasmo: 5,21 Mbit/s   vs  24,89 Mbit/s   (różnica 5×)
```

To nie jest retransmisja — to dwa procesy orkiestratora uruchomione w tej samej
sekundzie, skanujące tę samą sieć jednocześnie. Skutki:

- każdy skan przeszkadza drugiemu, więc **wyniki diagnostyczne są nieprawdziwe**
  (pasmo, latencja, nasłuch broadcast),
- listy hostów się różnią, więc ani jeden, ani drugi raport nie jest kompletny,
- podwójne obciążenie Pi 3B (1 GB RAM) — to najbardziej prawdopodobna przyczyna
  awarii Nuclei opisanej w C1,
- w panelu klient widzi dwa różne „badania" z tej samej chwili,
- podwójny ruch skanujący w sieci klienta.

**Naprawa (urządzenie):**

1. Znaleźć drugie źródło uruchomienia. Typowy zestaw: `systemctl list-units '*pentest*'`,
   `systemctl list-timers`, `crontab -l`, `/etc/cron.d/`, `~/.config/systemd/user/`.
   Klasyczna sytuacja: jednostka systemd z `Restart=` **oraz** wpis w cronie.
2. Wymusić pojedynczy przebieg blokadą, nawet po usunięciu drugiego wyzwalacza:
   ```bash
   exec 9>/var/lock/pensec-scan.lock
   flock -n 9 || { echo "skan już trwa, wychodzę"; exit 0; }
   ```
   albo `systemd-run --unit=pensec-scan --collect` (druga próba odbije się o zajętą nazwę).
3. Jeśli jednostka systemd jest jedynym wyzwalaczem, ustawić `Type=oneshot` i
   `Restart=no`, a cykliczność oprzeć na `.timer`, nie na restartach usługi.

### A3. Odrzucenia 422 powtarzane w nieskończoność

24 sierpnia między 19:12 a 19:28 sonda wysłała 78 żądań, z czego 50 dostało 422.
Raport z tego skanu (`scan_time = 2026-08-24 19:28:57`) trafił do bazy dopiero
**25 sierpnia o 18:24** — 23 godziny później, jako raport 15.

Powodu odrzucenia nie da się dziś ustalić, bo API nie zapisuje odrzuconych zgłoszeń
(patrz E1). API waliduje tylko dwie rzeczy: `report_id` musi być UUID-em, a `report`
musi być obiektem JSON (nie listą, nie stringiem). Najbardziej prawdopodobne
przyczyny to obcięty/uszkodzony JSON przy zapisie na kartę SD albo `report_id`
wygenerowany innym sposobem niż UUID.

**Naprawa (urządzenie):**

1. Walidować własny plik przed wysyłką: `jq empty raport.json || exit 1`.
2. Zapisywać raport atomowo — `tmp` + `fsync` + `rename` — żeby wysyłka nigdy nie
   dostała pliku w trakcie zapisu.
3. Logować lokalnie treść odpowiedzi 4xx; API zwraca czytelny `error.code` i `message`.

### A4. Limit 30 żądań/min

72 odpowiedzi 429 padły 27 sierpnia w oknie 19:02–19:14 — dokładnie wtedy, gdy dwie
równoległe pętle wysyłkowe (A1 × A2) zsumowały się do ~26 żądań/min. To skutek
uboczny A1 i A2; po ich naprawie limit przestaje mieć znaczenie. Nie ma powodu go
podnosić.

---

## 4. Warstwa czasu

### B1. `scan_time` nie oznacza czasu skanu — **wysokie**

`scan_time` jest jedynym polem odczytywanym przez API z dokumentu i służy do
odpowiedzi na pytanie „czy sonda skanowała późno, czy trzymała gotowy raport".
W większości raportów zgadza się ze znacznikami czasu w wyjściu nmap. W czterech
nie zgadza się drastycznie:

| Raport | `scan_time` | pierwszy nmap | rozbieżność |
|---|---|---|---|
| 11 | 2026-08-19 18:19:28 | 2026-08-23 13:41 CEST | **3 dni 19 h** |
| 15 | 2026-08-24 19:28:57 | 2026-08-25 18:17 CEST | **22 h 48 min** |
| 12 | 2026-08-23 16:03:11 | 2026-08-23 18:31 CEST | 2 h 28 min |
| 28 | 2026-08-30 08:40:59 | 2026-08-30 13:13 CEST | **4 h 32 min** |

Wzorzec jest charakterystyczny. `scan_time` raportu 11 (19.08, 18:19) leży
11 minut po `scan_time` raportu 10 (19.08, 18:08) — czyli tuż po ostatniej
aktywności urządzenia przed wyłączeniem. Raspberry Pi nie ma zegara podtrzymywanego
bateryjnie: po starcie `fake-hwclock` odtwarza czas z chwili ostatniego zamknięcia,
a dopiero synchronizacja NTP ustawia właściwy. **Sonda stempluje raport zanim czas
zostanie zsynchronizowany**, dlatego nmap uruchomiony kilka minut później drukuje
już poprawną godzinę, a `scan_time` zostaje w przeszłości.

Dodatkowo `scan_time` par z A2 jest identyczny co do sekundy dla dwóch osobnych
przebiegów, co potwierdza, że znacznik pochodzi z momentu startu procesu, a nie
z momentu skanowania.

Skutek dla klienta: w panelu i w PDF-ie badanie z 30 sierpnia wygląda na wykonane
4,5 godziny przed wysyłką, a badanie z raportu 11 na wykonane cztery dni wcześniej.
To dokładnie ta informacja, dla której `scanned_at` powstało — i dziś bywa fałszywa.

**Naprawa (urządzenie):**

1. Nie startować skanu przed synchronizacją czasu:
   ```bash
   systemctl enable systemd-time-wait-sync.service
   # w jednostce skanu:  After=time-sync.target   Wants=time-sync.target
   ```
   albo jednorazowo `chronyc waitsync 30 0.1` na początku skryptu.
2. Docelowo: moduł RTC (DS3231 na I²C, ~15 zł) — Pi w sieci klienta bywa bez
   dostępu do internetu, a wtedy NTP nie pomoże.
3. Stemplować czas **w momencie startu właściwego skanu**, a nie startu procesu,
   i wysyłać go w ISO 8601 z przesunięciem strefy:
   `"scan_started_at": "2026-08-30T13:13:41+02:00"`.
4. Dołożyć `"scan_finished_at"` oraz `"clock_synced": true|false`, żeby API mogło
   odróżnić „skan trwał długo" od „zegar był rozjechany".

### B2. Brak strefy czasowej w `scan_time`

Pole nie niesie strefy, więc API musi ją założyć — dziś `Europe/Warsaw`
(`config/pensec.php`, `pensec.reports.probe_timezone`). Analiza potwierdza, że to
założenie jest **poprawne**: w ponad dwudziestu raportach `scan_time` pokrywa się
z czasem CEST drukowanym przez nmap. Jest to jednak założenie, a nie fakt z danych —
sonda postawiona u klienta z inną strefą systemową natychmiast je złamie i wszystkie
opóźnienia przesuną się o pełne godziny. Rozwiązaniem jest ISO 8601 z offsetem
(punkt B1.3) albo UTC z jawnym `Z`.

### B3. Brak czasu trwania i czasu zakończenia

Z dokumentu nie da się odczytać, jak długo trwało badanie ani kiedy się skończyło —
poza wyłuskiwaniem godzin z tekstu nmap. To utrudnia zarówno diagnostykę
(np. „skan trwał 4 h, bo drugi proces go dławił"), jak i opis w raporcie dla klienta.

---

## 5. Warstwa modułów testujących

### C1. Nuclei nie zadziałał ani razu — **wysokie**

W 26 raportach nie ma **ani jednego** użytecznego wyniku Nuclei. Historia awarii
zmieniała się w czasie, ale skutek jest ten sam:

| Raporty | Objaw |
|---|---|
| 3–7 | `nuclei_results` w ogóle nieobecne lub puste |
| 8 | `[FTL] Could not run nuclei: no templates provided for scan`; podskan web dodatkowo `flag provided but not defined: -egm` |
| 9–14 | `[FTL] Could not run nuclei: could not create automatic scan service: could not get templates in directory: no templates found in path /opt/rpi-pentest/nuclei-templates-official` |
| 15–28 | zrzut awarii runtime'u Go — dziesiątki goroutine zablokowanych w `LoadTemplatesWithTags` / `templates.Cache.Has` na `sync.RWMutex.RLock` |

Osiem wpisów w całym zbiorze ma `status: "ok"` — wszystkie z `finding_count: 0`
i wszystkie w trybie `host`. Efektywne pokrycie skanowaniem szablonowym wynosi zero.

Awaria od raportu 15 to zawieszenie przy ładowaniu katalogu szablonów, z ogromną
liczbą równoległych goroutine czytających pliki. Na Pi 3B (4 rdzenie, 1 GB RAM,
karta SD) to typowy scenariusz wyczerpania pamięci lub limitu wątków — zwłaszcza
gdy **dwa** skany działają naraz (A2).

**Naprawa (urządzenie):**

1. Naprawić katalog szablonów: `nuclei -update-templates`, a ścieżkę wskazać jawnie
   przez `-templates-directory /opt/rpi-pentest/nuclei-templates-official`
   lub `NUCLEI_TEMPLATES_DIR`. Sprawdzić uprawnienia użytkownika usługi do tego katalogu.
2. Usunąć nieistniejącą flagę `-egm` z wywołania podskanu web.
3. Zdławić równoległość pod Pi: `-c 10 -rl 50 -bs 10 -timeout 10 -retries 1`
   (domyślne wartości są liczone dla maszyny serwerowej).
4. Ograniczyć zestaw szablonów zamiast ładować cały katalog — np. `-tags network,default-login,exposure`
   lub `-severity medium,high,critical`. Pełny katalog to ponad 10 000 plików YAML;
   samo ich sparsowanie zjada pamięć Pi.
5. Włączyć swap (`dphys-swapfile`, 1–2 GB) i uruchamiać Nuclei w jednostce z
   `MemoryMax=`, żeby awaria była kontrolowana, a nie zabijała całego skanu.
6. **Ustawić twardy limit czasu na moduł** (`timeout 600 nuclei …`) — dziś awaria
   potrafi zająć minuty i przedłużyć całe badanie.
7. Weryfikacja po naprawie: `nuclei -u http://<host> -stats` na samym Pi musi
   wypisać liczbę załadowanych szablonów większą od zera.

### C2. Cztery moduły nie produkują nic — **wysokie**

`smb_null_sessions`, `default_credentials`, `ldap_leaks` i `infrastructure_risks`
są w każdym raporcie puste (`[]`) albo w ogóle nieobecne. Wyjątkiem są raporty 15,
22 i 23, gdzie `infrastructure_risks` zawiera:

```json
{"192.168.100.45_ssl": {"expired_or_weak_certs": [{"port": 8443, "status": "Found Cert Data (Check PDF)"}]}}
```

To wpis-zaślepka: nie ma wystawcy, daty ważności, algorytmu ani odcisku certyfikatu,
za to jest odesłanie „Check PDF" do dokumentu, którego sonda nie wysyła. Klucz
`192.168.100.45_ssl` skleja IP z nazwą testu w jeden string, co dodatkowo utrudnia
parsowanie.

Nie da się dziś odróżnić „przetestowano i nic nie znaleziono" od „test się nie
uruchomił". API traktuje takie sekcje jako lukę w pokryciu i pisze o tym wprost
w PDF — czyli każdy raport dla klienta nosi widoczną adnotację o niekompletności badania.

**Naprawa (urządzenie):**

1. Każdy moduł ma zwracać **status swojego wykonania**, nie samą listę wyników
   (patrz proponowany format w rozdziale 8):
   ```json
   "smb_null_sessions": {"status": "ok",      "findings": []}
   "smb_null_sessions": {"status": "skipped", "reason": "brak hosta z otwartym 445/tcp", "findings": []}
   "smb_null_sessions": {"status": "failed",  "reason": "smbclient: command not found", "findings": []}
   ```
2. Sprawdzić, czy moduły w ogóle są wywoływane — `infrastructure_risks` pojawia się
   w 2 z 26 raportów, co wygląda na moduł uruchamiany warunkowo i po cichu pomijany.
3. Dokończyć moduł certyfikatów: wystawca, podmiot, `notBefore`/`notAfter`, algorytm
   podpisu, długość klucza, wersje TLS, samopodpisanie. Wszystko to daje
   `nmap --script ssl-cert,ssl-enum-ciphers -p 8443` w formacie XML.

### C3. ICS/OT — generator fałszywych trafień — **wysokie**

Raport 23, host 192.168.100.200: **21 wykrytych „protokołów przemysłowych"**
(Modbus Secure, CODESYS, EtherNet/IP, IEC 60870-5-104, KNXnet/IP, OPC UA TLS,
HART-IP…). Wszystkie mają:

```json
{"port": 802, "transport": "UDP", "state": "open|filtered", "severity": "INCONCLUSIVE"}
```

`open|filtered` na UDP w nmap oznacza dokładnie jedno: **port nie odpowiedział**.
Nmap nie potrafi w tym stanie odróżnić portu otwartego od odfiltrowanego, więc
raportuje oba naraz. Sonda bierze każdy taki brak odpowiedzi za wykrycie protokołu
przemysłowego, bo port ma numer z listy ICS. W całym zbiorze 406 wpisów w
`service_fingerprints` ma kształt `ics_protocol` + `ics_nmap_service` — czyli
zdecydowana większość „fingerprintów" pochodzi z tego mechanizmu.

W sieci biurowej z drukarką i routerem daje to raport pełen sterowników PLC.

**Naprawa (urządzenie):**

1. Nie raportować portów UDP w stanie `open|filtered`. Wpis powstaje wyłącznie
   przy `open` albo przy odpowiedzi na sondę protokołu.
2. Potwierdzać protokół zapytaniem, a nie numerem portu — nmap ma do tego skrypty:
   `modbus-discover`, `s7-info`, `enip-info`, `bacnet-info`, `iec-identify`,
   `omron-info`, `fins-info`. Dopiero odpowiedź na sondę jest wykryciem.
3. Jeśli warstwa „port z listy ICS bez odpowiedzi" ma zostać, musi trafiać do
   osobnej sekcji informacyjnej (`ics_candidates`), nigdy do `ics_ot_risks`.
4. `severity: "INCONCLUSIVE"` nie jest poziomem ryzyka. Wyniki niepotwierdzone
   nie należą do listy ryzyk.

### C4. Parser wyjścia nmap skleja linie — **średnie**

Pole `nmap_service` w `service_fingerprints` bywa zanieczyszczone treścią następnej
linii wyjścia:

```
"nmap_service": "ssl/http MAC Address: A0:F4:79:5A:EB:79 (Huawei Technologies)"
"nmap_service": "ssl/https-alt? 9000/tcp open  ssl/cslistener?"      ← dwa porty w jednym wpisie
"nmap_service": "rtsp 1 service unrecognized despite returning data. If you know the service…"
```

Drugi przykład jest najpoważniejszy: port 9000 został wchłonięty do opisu portu
8443 i **znika z raportu jako osobny port**. Przyczyną jest wyrażenie regularne
czytające do końca linii lub sklejające kolejne linie, gdy nmap łamie wynik.

**Naprawa (urządzenie):**

Przestać scrapować tekst. Nmap ma wyjście maszynowe:

```bash
nmap -sV -oX /tmp/scan.xml …          # XML, stabilny, ma schemat DTD
```

Parsować XML (`xmltodict`, `python-libnmap`), a surowy tekst nadal wysyłać obok —
API i tak go przechowuje i wykorzystuje. Struktura z XML daje za darmo: numer portu,
protokół, `state`, `reason`, `service`, `product`, `version`, `extrainfo`, `cpe`,
adres MAC z `vendor` — czyli wszystko, co dziś jest wyłuskiwane ręcznie i błędnie.

### C5. Hosty wykryte, ale nieprzeskanowane — **średnie**

Na 158 skanowań hostów w całym zbiorze **25 (16%)** kończy się w nmap komunikatem
`Nmap done: 1 IP address (0 hosts up)`, a w skanie głębokim:

```
Note: Host seems down. If it is really up, but blocking our ping probes, try -Pn
```

Host został wykryty na etapie discovery (ARP w sieci lokalnej zawsze go widzi), ale
skan portów pominął go, bo nie odpowiedział na sondę ICMP/TCP. Efekt: host figuruje
w `hosts` i w `discovered_hosts_count`, ma wpis w `nmap_results`, ale nie ma
żadnych wyników — i nic w dokumencie tego nie sygnalizuje. Czytelnik raportu
zobaczy host bez otwartych portów, choć nikt tych portów nie sprawdzał.

**Naprawa (urządzenie):**

1. Do skanu hostów wykrytych przez ARP używać `-Pn` — skoro urządzenie odpowiedziało
   na ARP, jest w sieci i pytanie o „host up" jest bezcelowe:
   `nmap -Pn -sV -oX … <ip>`.
2. Discovery oprzeć jawnie na `nmap -sn -PR <cidr>` (ARP) i przekazywać dalej listę
   z adresami MAC.
3. Gdy skan mimo wszystko nic nie zwróci, oznaczyć host w raporcie jako
   `"scan_status": "no_response"`, żeby luka była widoczna.

### C6. Wąski zakres portów — **średnie**

Zmierzone w całym zbiorze:

- skan podstawowy: **36 portów TCP** na hosta (w czterech przypadkach 26),
- skan „głęboki": **dokładnie 5 portów** — 21, 22, 80, 443, 445 — we wszystkich
  135 przebiegach,
- UDP: tylko lista portów ICS, poza tym zero.

Pięć portów w skanie nazwanym „deep_vulnerabilities" to bardzo mało. Poza zasięgiem
zostają m.in. 23 (telnet), 25/110/143 (poczta), 135/139 (RPC/NetBIOS), 1433, 3306,
5432 (bazy), 3389 (RDP), 5900 (VNC), 8080/8443 (panele web), 161 (SNMP), 1900 (UPnP),
53 (DNS), 123 (NTP) — czyli większość tego, co realnie bywa wystawione w sieci firmowej.

**Naprawa (urządzenie):**

1. Skan podstawowy: `--top-ports 1000` na hosta (na Pi z `-T4 --max-retries 2`
   to nadal kilkadziesiąt sekund na host).
2. Skan głęboki uruchamiać **na portach faktycznie znalezionych** przez skan
   podstawowy, a nie na sztywnej piątce: `-p $(porty_z_etapu_1)`.
3. Dodać krótki skan UDP na kilkunastu istotnych portach:
   `nmap -sU --top-ports 20` (53, 67, 123, 137, 161, 500, 1900, 5353…) — obowiązkowo
   z NSE potwierdzającym usługę, patrz C3.
4. Uwzględnić czas: pełne badanie ma prawo trwać dłużej, skoro raport i tak jest
   wysyłany dopiero po zakończeniu.

### C7. `dns_health` mierzy Google, nie klienta — **średnie**

W 25 z 26 raportów `dns_health.server` to `8.8.8.8`. Badanie mierzy więc czas
odpowiedzi publicznego resolvera Google, a nie serwera DNS sieci klienta — czyli
tego, którego stan faktycznie ma znaczenie dla użytkowników i który bywa źródłem
realnych problemów (DNS wewnętrzny, kontroler domeny, DNS rebinding, otwarty resolver).

**Naprawa (urządzenie):**

1. Odczytać resolver z DHCP (`resolvectl status`, `/etc/resolv.conf`, opcja 6 leasa)
   i mierzyć **jego** czas odpowiedzi.
2. Zachować 8.8.8.8 jako punkt odniesienia, ale w osobnym polu:
   ```json
   "dns_health": {
     "local": {"server": "192.168.0.1", "query_time_ms": 12, "source": "dhcp"},
     "reference": {"server": "8.8.8.8", "query_time_ms": 36}
   }
   ```
3. Przy okazji sprawdzić, czy lokalny resolver nie jest otwarty na świat i czy
   nie odpowiada na zapytania rekurencyjne spoza sieci.

### C8. `broadcast_poisoning_risks` zwraca stałą — **średnie**

We wszystkich 26 raportach, z czterech różnych sieci, sekcja zawiera dokładnie
jeden wpis o identycznej treści:

```json
{"type": "INFO", "severity": "INFO",
 "assessment": "Brak zdarzeń legacy name resolution oraz brak nowych hashy NetNTLMv2 w zadanym oknie pomiarowym."}
```

Moduł nigdy nie zwrócił niczego innego. W połączeniu z C9 (okno pomiarowe łapiące
2–15 pakietów) trudno uznać, że cokolwiek zostało zmierzone. Test LLMNR/NBT-NS/mDNS
poisoning polega na **aktywnym** odpowiadaniu na zapytania rozgłoszeniowe
(Responder w trybie analizy) przez czas liczony w minutach.

**Naprawa (urządzenie):**

1. Zwracać liczby, nie zdanie: ile zapytań LLMNR, NBT-NS, mDNS zaobserwowano,
   z ilu źródeł, w jakim oknie czasowym.
2. Wydłużyć nasłuch do co najmniej 5–10 minut i uruchamiać go **równolegle** z
   resztą skanu (dziś prawdopodobnie trwa sekundy).
3. Jeżeli nasłuch nie zebrał ruchu, jest to `status: "inconclusive"` z podaną
   długością okna, a nie stwierdzenie „brak zdarzeń".
4. Rozważyć `responder -A` (tryb wyłącznie analityczny, bez odpowiadania) —
   zgodny z charakterem badania nieinwazyjnego.

### C9. Nasłuch tshark bez materiału — **średnie**

`diagnostics.top_talkers` to surowy zrzut tabeli endpointów z tshark. Rozmiar próbki
w kolejnych raportach: 2, 2, 3, 4, 5, 5, 6, 7, 8, 15 wierszy. Raport 28:

```
192.168.0.107   3 pakiety   351 bajtów
224.0.0.251     3 pakiety   351 bajtów
```

Trzy pakiety mDNS. Na tej podstawie nie da się powiedzieć nic o ruchu w sieci,
a sekcja sugeruje analizę „największych rozmówców".

**Naprawa (urządzenie):**

1. Wydłużyć okno przechwytywania do 5–10 minut (`tshark -a duration:600`).
2. Sprawdzić interfejs i tryb — na porcie switcha bez mirroringu sonda zobaczy
   wyłącznie broadcast/multicast i własny ruch. To ograniczenie metody i trzeba je
   zapisać w raporcie, a nie udawać, że mierzy się cały ruch.
3. Wysyłać dane w postaci strukturalnej (`tshark -T json` albo `-z endpoints,ip`
   sparsowane do listy), a nie jako ramkę ASCII do parsowania po stronie serwera.
4. Dołożyć długość okna i liczbę pakietów łącznie — bez tego wynik jest nieczytelny.

### C10. Speedtest zaburza badanie i łącze klienta — **niskie**

`diagnostics.bandwidth` to trzy linie tekstu z speedtestu do internetu. W parach
raportów z A2 dwa równoległe testy dały 5,21 vs 24,89 Mbit/s oraz 5,52 vs
25,18 Mbit/s — czyli pomiar mierzył głównie sam siebie. Niezależnie od tego test
wysyca łącze klienta w trakcie badania i zaburza wszystkie pozostałe pomiary
(latencja, DNS, nasłuch).

**Naprawa (urządzenie):**

1. Uruchamiać pomiar pasma **jako ostatni krok**, po zakończeniu skanów.
2. Zwracać liczby, nie tekst: `{"ping_ms": 30.9, "download_mbps": 91.9, "upload_mbps": 2.04, "server": "…"}`.
3. Rozważyć uczynienie go opcjonalnym — u klienta z łączem produkcyjnym
   wysycenie uplinku w godzinach pracy jest realnym kosztem.

---

## 6. Warstwa formatu i kontraktu

### D1. Brak wersji formatu, wersji firmware i tożsamości przebiegu — **wysokie**

Dokument nie zawiera **żadnych metadanych o sobie samym**: ani wersji schematu, ani
wersji oprogramowania sondy, ani wersji użytych narzędzi (nmap 7.95 da się odczytać
tylko z tekstu), ani identyfikatora przebiegu w środku dokumentu (`report_id`
podróżuje wyłącznie w kopercie żądania). Punkt 28 listy „do doprecyzowania"
w `specyfikacja.md` — wersjonowanie formatu raportu JSON — jest nadal otwarty i
właśnie dlatego zmiany opisane w D2 przeszły niezauważone.

**Naprawa:** blok `meta` na początku dokumentu, patrz rozdział 8.

### D2. Te same klucze zmieniają typ między wersjami firmware — **wysokie**

Porównanie raportu 3 (16 sierpnia) z raportem 28 (30 sierpnia):

| Klucz | Raport 3 | Raport 28 |
|---|---|---|
| `mitm_vulnerability` | `"Brak blokady Gratuitous ARP. Krytyczna podatnosc na ataki Man in the Middle."` | `{"gratuitous_arp_blocked": false}` |
| `wpad_vulnerability` | zdanie z oceną „Krytyczna podatnosc" | `{"wpad_authoritative_dns": false}` |
| `ipv6_spoofing` | zdanie | `{"ipv6_spoofing_vulnerable": false}` |
| `rogue_dhcp` | `"Brak odpowiedzi DHCP Sprawdz podlaczenie do sieci"` | **klucz zniknął** |
| `vlan_hopping` | lista obiektów z `severity` i `assessment` | **klucz zniknął** |
| `network_fabric` | zdanie | **klucz zniknął** |
| `physical_port_security` | — | `{"dtp_trunking_active": false, "stp_bpdu_guard_missing": true}` |
| `wireless_security` | — | pojawił się w 8, 10, 11, potem zniknął |

Dwa problemy naraz. Po pierwsze, ten sam klucz raz jest stringiem, raz obiektem —
każdy parser pisany pod jedną wersję wywraca się na drugiej. Po drugie, przy
zamianie zdań na flagi **zgubiono ocenę ryzyka**: raport 3 mówił wprost „Krytyczna
podatnosc na ataki Man in the Middle", raport 28 mówi `gratuitous_arp_blocked: false`
i czytelnik musi wiedzieć, że `false` to zła wiadomość. Gorzej: w `physical_port_security`
`dtp_trunking_active: false` jest wynikiem **dobrym**, a `stp_bpdu_guard_missing: true`
**złym** — te same typy, przeciwne znaczenia. API musi dziś trzymać słownik znaczeń
(`App\Support\Diagnostics`), żeby to opisać po polsku.

Zniknięcie `vlan_hopping`, `rogue_dhcp` i `network_fabric` jest osobną stratą — to
były realne testy warstwy 2 i po prostu przestały się pojawiać.

**Naprawa (urządzenie):**

1. Ustalić kształt każdego pola diagnostycznego i **nigdy nie zmieniać typu istniejącego
   klucza**. Zmiana znaczenia = nowy klucz + podniesienie `meta.schema_version`.
2. Każdy test diagnostyczny w jednolitej strukturze, z oceną po stronie urządzenia:
   ```json
   "mitm_vulnerability": {
     "status": "ok",
     "verdict": "vulnerable",
     "severity": "high",
     "evidence": {"gratuitous_arp_blocked": false},
     "summary": "Brak blokady Gratuitous ARP — sieć podatna na ataki Man in the Middle."
   }
   ```
   `verdict` przyjmuje `secure` / `vulnerable` / `inconclusive`, `status`
   `ok` / `failed` / `skipped`. Wtedy `false` nigdy nie musi być interpretowane
   przez czytelnika ani przez serwer.
3. Przywrócić `vlan_hopping`, `rogue_dhcp` i `network_fabric` albo świadomie je
   usunąć i odnotować to w changelogu firmware — dziś nie wiadomo, które z dwojga zaszło.

### D3. Puste kolekcje jako `[]` zamiast `{}` — **niskie**

`nuclei_results`, `ics_ot_risks`, `service_fingerprints`, `infrastructure_risks` są
normalnie obiektami z kluczem-adresem IP, ale gdy nic nie zawierają, przychodzą jako
pusta **lista** `[]`. To klasyczny efekt serializacji pustego słownika w Pythonie/PHP.
Parser typowany na obiekt dostaje listę i albo się wywraca, albo cicho pomija sekcję.

**Naprawa (urządzenie):** wymuszać typ przy serializacji — w Pythonie `dict()`
zamiast `[]` jako wartość domyślna, a przy budowie dokumentu asercja, że sekcja
mapowana po IP jest słownikiem. Test jednostkowy na „raport z pustej sieci" wyłapie to raz na zawsze.

### D4. Komunikat błędu Nuclei obcinany od początku — **średnie**

Wartości `nuclei_results.*.host.error` zaczynają się w połowie tokenu:

```
"error": "=0x40043526d0 sp=0x4004352690 pc=0x7d6c8\nsync.(*RWMutex).RLock(...)…"
"error": "L({0x4001fa8d20, 0x5a}, {0x2f3ca30, 0x40005905e0})\n…"
```

Urządzenie zachowuje **ostatnie N bajtów** wyjścia błędu. W panice Go najważniejsza
informacja — komunikat i przyczyna — jest na **początku**, a dalej idą zrzuty setek
goroutine. Obcinanie od końca zostawia więc wyłącznie balast. Ten balast to od
raportu 15 **52–76% objętości całego dokumentu**:

```
raport 15:  59,1 kB total,  39,0 kB nuclei (66%)
raport 22:  51,2 kB total,  39,0 kB nuclei (76%)
raport 28:  51,1 kB total,  39,0 kB nuclei (76%)
```

**Naprawa (urządzenie):**

1. Obcinać **od początku**: pierwsze 2–4 kB stderr wystarczą do diagnozy.
2. Przy panice Go zatrzymywać wyłącznie pierwszą linię `panic:`/`fatal error:` oraz
   pierwszy stos, resztę pomijać z adnotacją `"truncated": true` i `"original_bytes": 39021`.
3. Nie powtarzać tego samego zrzutu dla każdego hosta z osobna — dziś ta sama awaria
   ładowania szablonów jest kopiowana 6–9 razy w jednym dokumencie.

### D5. Języki i znaki diakrytyczne — **niskie**

Dokument jest technicznie angielski (klucze, wartości nmap), ale niesie zdania po
polsku, i to niekonsekwentnie zapisane:

- z diakrytykami: `"Błąd przełączenia w tryb monitora."`, `"Brak zdarzeń legacy name resolution…"`,
- bez: `"Nieznany blad Nuclei."`, `"Krytyczna podatnosc na ataki Man in the Middle"`, `"Sprawdz podlaczenie do sieci"`.

To sugeruje dwa różne miejsca w kodzie firmware, jedno z nich z ucieczką przed
kodowaniem. Dla raportu, który trafia do klienta, „podatnosc" wygląda źle.

**Naprawa (urządzenie):** wszystkie komunikaty w UTF-8 z pełnymi diakrytykami
(`json.dump(..., ensure_ascii=False)`), źródła tekstów w jednym module. Docelowo:
urządzenie wysyła kod zdarzenia, a tekst dla klienta powstaje po stronie API — wtedy
tłumaczenie i redakcja są w jednym miejscu.

### D6. Czego w dokumencie brakuje

Poza wymienionym wyżej, do pełnego badania przydałyby się:

- **zakres badania**: CIDR sieci, interfejs, maska, brama, VLAN — dziś trzeba je zgadywać z listy IP,
- **adresy MAC i vendor** jako dane, nie jako fragment tekstu nmap (są w wyjściu, giną w parsowaniu),
- **czas trwania każdego modułu** — bez tego nie wiadomo, co przedłuża badanie,
- **statusy modułów** (C2) — najważniejszy brakujący element całego dokumentu,
- **deterministyczna kolejność** `hosts` (dziś losowa, co utrudnia porównywanie raportów),
- **wersje narzędzi**: nmap, nuclei, tshark, wraz z datą aktualizacji szablonów Nuclei.

---

## 7. Co warto zmienić po stronie API

To już nie jest wina urządzenia, ale bez tych zmian część powyższych usterek pozostaje
niewidoczna aż do ręcznej analizy.

### E1. Logowanie odrzuconych zgłoszeń

50 odpowiedzi 422 z 24 sierpnia jest dziś nie do zdiagnozowania — nie wiadomo, które
urządzenie, jaki `report_id` ani jaka reguła walidacji zawiodła. Warto logować na
poziomie `warning` każdą odpowiedź 4xx na `/api/v1/reports`: identyfikator urządzenia,
`report_id` (o ile przyszedł), kod błędu, rozmiar ciała, adres IP. Bez treści raportu —
sam fakt i powód.

### E2. Widoczność powtórnych zgłoszeń

Odpowiedź 200 („już mam ten raport") nie zostawia dziś żadnego śladu. Gdyby `reports`
miało kolumny `resubmission_count` i `last_submitted_at` inkrementowane w `ReportIntake`
przy trafieniu w istniejący `report_uid`, pętla z A1 byłaby widoczna w panelu
pierwszego dnia jako licznik „7 180" zamiast po dwóch tygodniach w logach Apache.
Zmiana jest mała i nie narusza kontraktu API — odpowiedź pozostaje ta sama.

### E3. Kontrakt na kopertę `meta`

Gdy urządzenie zacznie wysyłać blok `meta` (rozdział 8), warto:

- odczytywać `meta.schema_version` i zapisywać w `reports` obok `scanned_at` —
  to pozwoli w przyszłości uruchomić właściwy parser dla właściwej wersji,
- czytać `meta.scan_started_at` / `scan_finished_at` zamiast `scan_time`, zachowując
  `ScanTime` jako ścieżkę zgodności wstecznej dla dokumentów bez `meta`,
- opisać `meta` w `docs/OpenAPI/openapi.yaml` **zanim** firmware zacznie to wysyłać —
  kontrakt w tym projekcie idzie przed kodem.

Do czasu wprowadzenia `meta` obecne założenie `pensec.reports.probe_timezone = Europe/Warsaw`
jest poprawne i należy je zostawić: w ponad dwudziestu raportach `scan_time` pokrywa
się z czasem CEST drukowanym przez nmap.

---

## 8. Proponowany kształt raportu (v2)

Szkic docelowego dokumentu. Zmiany są addytywne tam, gdzie to możliwe — sekcje
z wynikami zachowują nazwy, zmienia się ich opakowanie.

```json
{
  "meta": {
    "schema_version": "2.0",
    "firmware_version": "1.4.2",
    "device_serial": "10000000abcd1234",
    "run_id": "1eaa062f-2727-48cb-ad70-da05c3922a4e",
    "scan_started_at": "2026-08-30T13:13:41+02:00",
    "scan_finished_at": "2026-08-30T13:22:07+02:00",
    "duration_seconds": 506,
    "clock_synced": true,
    "tools": {
      "nmap": "7.95",
      "nuclei": "3.3.7",
      "nuclei_templates": "2026-08-28",
      "tshark": "4.2.2"
    }
  },
  "scope": {
    "orchestrator_ip": "192.168.0.109",
    "interface": "eth0",
    "cidr": "192.168.0.0/24",
    "gateway": "192.168.0.1",
    "discovered_hosts_count": 6
  },
  "hosts": [
    {
      "ip": "192.168.0.1",
      "mac": "50:0F:F5:97:CE:48",
      "vendor": "Tenda Technology",
      "scan_status": "ok",
      "ports": [
        {"port": 80, "transport": "tcp", "state": "open",
         "service": "http", "product": "GoAhead WebServer", "cpe": ["cpe:/a:goahead:webserver"]}
      ]
    }
  ],
  "modules": {
    "port_scan":        {"status": "ok",      "duration_seconds": 61},
    "deep_scan":        {"status": "ok",      "duration_seconds": 216},
    "nuclei":           {"status": "failed",  "reason": "nuclei: no templates found in /opt/rpi-pentest/nuclei-templates-official",
                         "findings": []},
    "smb_null_sessions":{"status": "skipped", "reason": "brak hosta z otwartym 445/tcp", "findings": []},
    "default_credentials": {"status": "ok", "findings": []},
    "ldap_leaks":       {"status": "skipped", "reason": "brak usługi LDAP", "findings": []},
    "ics_ot":           {"status": "ok",      "findings": [],
                         "candidates": [{"ip": "192.168.0.200", "port": 502, "transport": "udp",
                                         "state": "open|filtered", "note": "brak odpowiedzi na sondę Modbus"}]}
  },
  "diagnostics": {
    "latency":    {"status": "ok", "min_ms": 0.362, "avg_ms": 0.397, "max_ms": 0.43},
    "dns_health": {"status": "ok",
                   "local":     {"server": "192.168.0.1", "query_time_ms": 12, "source": "dhcp"},
                   "reference": {"server": "8.8.8.8", "query_time_ms": 36}},
    "mitm_vulnerability": {
      "status": "ok", "verdict": "vulnerable", "severity": "high",
      "evidence": {"gratuitous_arp_blocked": false},
      "summary": "Brak blokady Gratuitous ARP — sieć podatna na ataki Man in the Middle."
    },
    "egress_filtering": {
      "status": "ok", "verdict": "vulnerable", "severity": "medium",
      "evidence": {"allowed_ports": [4444, 3389, 6667]},
      "summary": "Ruch wychodzący na portach typowych dla C2 i zdalnego pulpitu nie jest filtrowany."
    },
    "traffic_capture": {
      "status": "ok", "window_seconds": 600, "packets_total": 3,
      "endpoints": [{"address": "192.168.0.107", "packets": 3, "bytes": 351}],
      "note": "Sonda widzi wyłącznie ruch rozgłoszeniowy — port dostępowy bez mirroringu."
    },
    "bandwidth": {"status": "ok", "ping_ms": 30.96, "download_mbps": 91.91, "upload_mbps": 2.04}
  },
  "raw": {
    "nmap_results": {"192.168.0.1": "…"},
    "deep_vulnerabilities": {"192.168.0.1": "…"}
  }
}
```

Zasady, które ten kształt utrwala:

1. **Każdy moduł mówi, czy się wykonał** — `status` jest obowiązkowy, `findings` puste
   znaczy „sprawdzono i czysto", a nie „nie wiadomo".
2. **Urządzenie ocenia, serwer opisuje** — `verdict` i `severity` powstają tam, gdzie
   jest wiedza o teście; serwer nie musi zgadywać, czy `false` to dobrze.
3. **Dane strukturalne obok surowych** — `raw` zostaje jako materiał dowodowy, ale
   nic się z niego nie wyłuskuje na siłę.
4. **Czas zawsze z offsetem**, nigdy „gołe" `Y-m-d H:i:s`.
5. **Wersja schematu w dokumencie** — bez niej każda kolejna zmiana firmware będzie
   cichą awarią parsera.

---

## 9. Kolejność napraw

**Najpierw (blokuje wiarygodność wszystkiego pozostałego):**

1. A1 — przerwać pętlę wysyłkową; 2xx kończy sprawę.
2. A2 — jeden przebieg skanu naraz (`flock` + usunięcie drugiego wyzwalacza).
3. B1 — czekać na synchronizację czasu przed stemplowaniem raportu.

**Następnie (raport przestaje kłamać o pokryciu):**

4. C1 — naprawić Nuclei albo wyłączyć go i raportować jako `skipped`; nie wysyłać
   40 kB zrzutów awarii.
5. C2 — statusy modułów zamiast pustych list.
6. C3 — koniec z ICS z portów UDP bez odpowiedzi.
7. D4 — obcinanie błędów od początku, nie od końca.

**Potem (jakość badania):**

8. C4 — parsowanie `nmap -oX` zamiast tekstu.
9. C5 — `-Pn` dla hostów wykrytych przez ARP.
10. C6 — realny zakres portów.
11. C7, C8, C9, C10 — DNS klienta, prawdziwe okno nasłuchu, speedtest na końcu.

**Równolegle, po stronie kontraktu:**

12. D1 + D2 — `meta` z wersją schematu, stabilne typy pól, przywrócenie utraconych testów warstwy 2.
13. E1 + E2 — logowanie odrzuceń i licznik powtórnych zgłoszeń w API.

---

## 10. Jak to zweryfikować po naprawie

- **A1:** po jednym badaniu w logu dostępowym mają być dokładnie **dwa** wpisy dla
  `POST /api/v1/reports` na raport w najgorszym razie (jeden 201, ewentualnie jeden
  ponowiony po błędzie sieci). `grep -c 'POST /api/v1/reports' access.log` na dobę
  z jednym skanem powinno dawać liczbę jednocyfrową.
- **A2:** w bazie żadne dwa raporty nie mają tego samego `scan_time`:
  ```sql
  SELECT scanned_at, COUNT(*) FROM reports GROUP BY scanned_at HAVING COUNT(*) > 1;
  ```
- **B1:** `scan_time` z dokumentu mieści się w kilku minutach od pierwszego znacznika
  `Starting Nmap … at …` w `nmap_results`, w każdym raporcie po restarcie urządzenia.
- **C1:** przynajmniej jeden wpis `nuclei_results` ma `status: "ok"` z niezerową
  liczbą załadowanych szablonów; rozmiar sekcji nuclei spada poniżej 5% dokumentu.
- **C3:** w sieci biurowej bez sterowników PLC `ics_ot_risks` jest puste.
- **C5:** żaden host nie ma jednocześnie wpisu w `hosts` i `0 hosts up` w `nmap_results`.
- **Ogólnie:** panel przestaje pokazywać ramkę „Luki w pokryciu badania" na raporcie
  z czystej sieci — dziś nosi ją każdy wygenerowany dokument.
