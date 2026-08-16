# Specyfikacja systemu Pensec

## 1. Informacje ogólne

### 1.1. Nazwa systemu

Pensec

### 1.2. Domena API

`https://pensec.top`

### 1.3. Cel systemu

System służy do zbierania raportów z testów bezpieczeństwa wykonywanych przez urządzenia oparte na Raspberry Pi.

Urządzenie wykonuje testy lokalnie, a po zakończeniu całego procesu przesyła do centralnego API jeden kompletny raport w formacie JSON.

API odbiera raport, identyfikuje urządzenie oraz konkretne uruchomienie/test i zapisuje dane w systemie.

System posiada również panel administracyjny umożliwiający zarządzanie urządzeniami oraz przeglądanie wykonanych procesów i raportów.

---

## 2. Architektura systemu

System składa się z dwóch podstawowych elementów:

1. urządzeń Raspberry Pi wykonujących testy,
2. serwera API wraz z panelem administracyjnym.

### 2.1. Urządzenie Raspberry Pi

Urządzenie jest odpowiedzialne za:

- wykonanie testów bezpieczeństwa,
- wygenerowanie identyfikatora raportu dla każdego uruchomienia testów,
- uwierzytelnienie się w API,
- przygotowanie kompletnego raportu JSON,
- przesłanie raportu do API po zakończeniu testów.

Urządzenie nie przesyła raportu porcjami podczas wykonywania testów.

Cały raport jest przesyłany do API jednorazowo, po zakończeniu procesu.

### 2.2. API

API jest centralnym punktem komunikacji z urządzeniami.

Odpowiada za:

- uwierzytelnienie urządzenia,
- odbieranie raportów,
- identyfikację urządzenia,
- identyfikację konkretnego raportu/testu,
- zapisanie raportu,
- zapisanie informacji niezbędnych do obsługi procesu,
- udostępnienie danych panelowi administracyjnemu.

Adres API:

`https://pensec.top`

### 2.3. Panel administracyjny

System posiada panel administracyjny przeznaczony dla administratora.

Panel umożliwia między innymi:

- logowanie administratora,
- dodawanie urządzeń do systemu,
- przypisywanie/autoryzowanie tokenów urządzeń,
- przeglądanie urządzeń znajdujących się w systemie,
- przeglądanie procesów/testów,
- sprawdzanie statusu procesu,
- sprawdzanie, czy proces został zakończony,
- przeglądanie szczegółów procesu i powiązanego raportu.

---

## 3. Identyfikacja urządzenia

Każde urządzenie posiada własny token.

Token służy do identyfikacji oraz autoryzacji urządzenia podczas komunikacji z API.

Urządzenie przesyła token podczas wysyłania raportu.

API na podstawie tokena rozpoznaje urządzenie, z którego pochodzi raport.

### 3.1. Rejestracja urządzenia

Administrator w panelu administracyjnym może dodać urządzenie do systemu poprzez wprowadzenie jego tokena.

Dopiero urządzenie znajdujące się w systemie może zostać zaakceptowane przez API.

Szczegółowy sposób generowania, przechowywania oraz zabezpieczenia tokenów wymaga doprecyzowania.

---

## 4. Identyfikacja raportu

Każde uruchomienie urządzenia rozpoczynające proces testowania otrzymuje unikalny identyfikator raportu.

Identyfikator jest generowany przez urządzenie.

Identyfikator raportu jest przesyłany razem z raportem do API.

Dzięki temu system może jednoznacznie powiązać dane z konkretnym uruchomieniem testów.

System rozróżnia więc:

- urządzenie — identyfikowane przez token,
- raport/test — identyfikowany przez unikalny identyfikator wygenerowany podczas danego uruchomienia.

Ten sam token urządzenia może być powiązany z wieloma raportami.

Każde kolejne uruchomienie testów powinno otrzymać nowy identyfikator raportu.

---

## 5. Cykl życia testu

Podstawowy cykl pracy urządzenia wygląda następująco:

1. Urządzenie zostaje włączone.
2. Urządzenie rozpoczyna przygotowanie do wykonania testów.
3. Urządzenie generuje unikalny identyfikator raportu.
4. Urządzenie wykonuje testy bezpieczeństwa.
5. Wyniki testów są gromadzone lokalnie.
6. Po zakończeniu wszystkich testów urządzenie tworzy kompletny raport JSON.
7. Urządzenie uwierzytelnia się w API przy użyciu swojego tokena.
8. Urządzenie przesyła do API kompletny raport.
9. API identyfikuje urządzenie na podstawie tokena.
10. API identyfikuje konkretny proces na podstawie identyfikatora raportu.
11. API zapisuje raport.
12. Proces zostaje uznany za zakończony po prawidłowym przyjęciu kompletnego raportu przez API.

Raport nie jest wysyłany do API w trakcie wykonywania poszczególnych testów.

---

## 6. Format raportu

Raport przesyłany przez urządzenie ma format JSON.

Przykładowy raport znajduje się w pliku:

`raport.json`

Plik ten stanowi materiał poglądowy przedstawiający rzeczywistą strukturę i skalę danych, które mogą być przesyłane przez urządzenie.

Przykładowy raport zawiera między innymi:

- czas wykonania skanu,
- adres IP urządzenia wykonującego skan,
- liczbę wykrytych hostów,
- listę wykrytych hostów,
- dane diagnostyczne,
- wyniki Nmap,
- wyniki głębokich testów,
- wyniki Nuclei,
- wyniki testów SMB,
- informacje o zagrożeniach związanych z broadcast poisoning,
- informacje dotyczące ICS/OT,
- fingerprinty usług,
- informacje dotyczące infrastruktury,
- inne dane generowane przez poszczególne moduły testujące.

Przykładowa struktura raportu zawiera zarówno proste wartości, jak i rozbudowane obiekty, tablice oraz długie wartości tekstowe.

Raport może zawierać również bardzo duże fragmenty surowych wyników narzędzi, np. wyniki Nmap dla wielu hostów.

Występują również zagnieżdżone struktury danych dotyczące testów ICS/OT oraz fingerprintów usług.

Plik `raport.json` należy traktować jako przykład poglądowy dotyczący skali i rodzaju danych. Nie należy na obecnym etapie traktować jego aktualnej struktury jako ostatecznego kontraktu API.

---

## 7. Przechowywanie raportów

Na początkowym etapie system powinien przechowywać raport JSON w formie surowej.

API nie powinno podczas odbierania raportu analizować jego całej struktury ani rozbijać go na poszczególne wyniki.

Raport powinien zostać zapisany w bazie danych jako kompletny, niezmieniony dokument JSON.

### 7.1. Zasada przechowywania danych

Dla każdego raportu system powinien przechowywać:

- identyfikator urządzenia,
- identyfikator raportu,
- informacje systemowe dotyczące raportu,
- kompletną zawartość raportu JSON.

Dokładny sposób przechowywania JSON w bazie danych wymaga późniejszego ustalenia.

Do rozważenia pozostaje między innymi:

- typ kolumny,
- maksymalny rozmiar raportu,
- ewentualna kompresja,
- sposób zabezpieczenia danych,
- sposób wykonywania kopii zapasowych.

---

## 8. Parser i analiza raportów

Analiza raportu nie jest częścią procesu jego odbierania.

Surowy raport powinien zostać zachowany jako źródło danych.

W późniejszym etapie aplikacja PHP będzie mogła pobrać surowy raport i przekazać go do parsera.

Parser będzie odpowiedzialny za:

- odczytanie struktury JSON,
- wyciągnięcie konkretnych danych,
- przetworzenie wyników poszczególnych testów,
- przygotowanie danych potrzebnych do dalszej analizy,
- przygotowanie informacji przeznaczonych do prezentacji w panelu.

Takie rozwiązanie pozwala zachować oryginalne dane nawet w przypadku późniejszej zmiany sposobu analizy raportów.

Zmiana parsera nie powinna powodować utraty oryginalnego raportu.

---

## 9. Dane systemowe a dane raportu

Należy rozdzielić dane techniczne systemu od danych dostarczanych przez urządzenie.

### 9.1. Dane systemowe

Do danych systemowych należą między innymi:

- urządzenie, które przesłało raport,
- token urządzenia lub jego wewnętrzny identyfikator,
- identyfikator raportu,
- data i czas odebrania raportu,
- status procesu,
- informacje dotyczące poprawności odebrania raportu.

### 9.2. Dane raportu

Dane raportu stanowi kompletny JSON wygenerowany przez urządzenie.

System nie powinien na etapie odbierania raportu wymagać znajomości wszystkich jego pól.

---

## 10. Procesy w panelu administracyjnym

Panel administracyjny powinien umożliwiać administratorowi przeglądanie procesów wykonanych przez urządzenia.

Dla każdego procesu powinno być możliwe ustalenie co najmniej:

- którego urządzenia dotyczył,
- jaki posiada identyfikator raportu,
- kiedy został wykonany/odebrany,
- czy został zakończony,
- jaki jest jego aktualny status.

Docelowy zestaw statusów oraz etapów procesu wymaga późniejszego doprecyzowania.

---

## 11. Urządzenia w systemie

Panel administracyjny powinien zawierać listę urządzeń dodanych do systemu.

Administrator powinien mieć możliwość:

- dodania urządzenia,
- autoryzowania urządzenia poprzez jego token,
- przeglądania urządzeń,
- sprawdzania powiązanych z urządzeniem raportów.

Szczegółowe informacje prezentowane dla urządzenia wymagają późniejszego ustalenia.

---

## 12. Bezpieczeństwo komunikacji

Urządzenie musi być uwierzytelnione przed przesłaniem raportu.

Podstawowym mechanizmem identyfikacji urządzenia jest token urządzenia.

Szczegółowy mechanizm autoryzacji wymaga ustalenia.

Do rozważenia pozostaje między innymi:

- format tokena,
- sposób generowania tokena,
- sposób przechowywania tokena po stronie urządzenia,
- możliwość unieważnienia tokena,
- możliwość wygenerowania nowego tokena,
- sposób zabezpieczenia komunikacji,
- reakcja API na nieautoryzowane urządzenie.

---

## 13. Obsługa błędów

Mechanizm obsługi błędów komunikacji pomiędzy urządzeniem i API wymaga późniejszego określenia.

Do ustalenia pozostają między innymi:

- co urządzenie robi w przypadku braku połączenia z API,
- czy raport jest przechowywany lokalnie do czasu skutecznego wysłania,
- czy urządzenie ponawia próbę wysłania,
- ile razy następuje ponowienie,
- jak długo raport może oczekiwać na wysłanie,
- co dzieje się w przypadku odrzucenia raportu przez API,
- jak API reaguje na ponowne przesłanie tego samego raportu.

---

## 14. Wymagania dotyczące spójności danych

Każdy raport musi być jednoznacznie powiązany z:

- konkretnym urządzeniem,
- konkretnym uruchomieniem/testem.

Identyfikator raportu generowany przez urządzenie powinien być unikalny.

API nie powinno dopuszczać do przypadkowego połączenia danych pochodzących z różnych uruchomień tego samego urządzenia.

---

## 15. Zakres pierwszej wersji systemu

Pierwsza wersja systemu powinna obejmować:

### Urządzenie

- generowanie identyfikatora raportu,
- autoryzację za pomocą tokena,
- wykonywanie testów,
- wygenerowanie kompletnego raportu JSON,
- jednorazowe przesłanie raportu po zakończeniu testów.

### API

- autoryzację urządzeń,
- odbieranie raportów,
- identyfikację urządzenia,
- identyfikację raportu,
- zapis metadanych raportu,
- zapis surowego JSON-a,
- podstawową obsługę statusu procesu.

### Panel administracyjny

- logowanie administratora,
- dodawanie urządzeń poprzez token,
- listę urządzeń,
- listę procesów/raportów,
- status procesu,
- podstawowy podgląd szczegółów procesu.

---

## 16. Elementy do doprecyzowania

Przed rozpoczęciem implementacji należy jeszcze ustalić między innymi:

1. Dokładny sposób autoryzacji urządzenia.
2. Format i długość tokena.
3. Sposób generowania tokenów.
4. Czy token może zostać unieważniony.
5. Format identyfikatora raportu.
6. Sposób generowania identyfikatora raportu.
7. Czy identyfikator raportu musi być globalnie unikalny, czy wystarczy unikalność w obrębie urządzenia.
8. Dokładne endpointy API.
9. Metody HTTP używane przez urządzenie.
10. Format requestu zawierającego token, identyfikator raportu i JSON.
11. Oczekiwane odpowiedzi API.
12. Maksymalny rozmiar raportu.
13. Sposób przechowywania JSON w bazie danych.
14. Obsługę ponownego przesłania tego samego raportu.
15. Obsługę błędów i ponawiania transmisji.
16. Zachowanie urządzenia bez dostępu do Internetu.
17. Statusy i etapy procesu widoczne w panelu.
18. Dokładny zakres informacji prezentowanych administratorowi.
19. Czy administrator może ręcznie zmieniać status procesu.
20. Czy raport po zapisaniu może być modyfikowany.
21. Czy parser będzie uruchamiany ręcznie, automatycznie czy przez kolejkę.
22. Czy parser będzie zapisywał wynik analizy jako osobne dane w bazie.
23. Czy system będzie przechowywał historię kolejnych analiz tego samego raportu.
24. Mechanizm logowania administratora.
25. Uprawnienia administracyjne.
26. Kopie zapasowe raportów.
27. Retencję i usuwanie starych raportów.
28. Wersjonowanie formatu raportu JSON.
29. Wersjonowanie API.
30. Sposób aktualizacji oprogramowania urządzenia.

---

## 17. Plik referencyjny

W projekcie dostępny jest plik:

`raport.json`

Plik należy traktować jako przykładowy raport referencyjny służący do określenia skali, typu oraz złożoności danych przesyłanych przez urządzenie.

Nie należy na obecnym etapie traktować jego aktualnej struktury jako ostatecznego kontraktu API.

Docelowa struktura raportu może zostać rozwinięta lub zmieniona wraz z rozwojem oprogramowania urządzenia.

Surowy raport powinien być przechowywany w systemie właśnie po to, aby zmiany parsera lub późniejsze rozszerzenie sposobu analizy nie powodowały utraty danych źródłowych.