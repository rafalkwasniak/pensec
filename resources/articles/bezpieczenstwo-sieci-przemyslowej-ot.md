W zakładzie produkcyjnym sieć biurowa i sieć automatyki rzadko są od siebie tak odcięte, jak zakłada dokumentacja. Sterownik, panel operatorski albo serwer SCADA bywa osiągalny z komputera w biurze, z sieci gości lub przez łącze serwisowe dostawcy. Badanie sieci przemysłowej odpowiada na pytanie, które urządzenia automatyki są widoczne z miejsc, z których nie powinny być, i co o sobie ujawniają.

## Dlaczego OT wymaga osobnego podejścia

Systemy przemysłowe (OT, ICS) powstawały z myślą o niezawodności i pracy w czasie rzeczywistym, a nie o obronie przed atakiem. Wiele protokołów sterowania, takich jak Modbus, nie przewiduje uwierzytelniania: urządzenie wykonuje polecenie od każdego, kto potrafi je wysłać. Sterowniki pracują latami bez aktualizacji, bo każda zmiana oznacza ryzyko przestoju linii. Dlatego tam, gdzie w sieci biurowej wystarcza poprawka, w OT najczęściej trzeba ograniczyć dostęp do urządzenia, bo samego urządzenia szybko się nie zmieni.

Z tego samego powodu badanie OT musi być ostrożne. Celem jest identyfikacja i opis ekspozycji, a nie ingerencja w proces: nie są wysyłane polecenia zapisu do sterowników ani zmiany ich konfiguracji. Zakres i intensywność testów w segmentach przemysłowych ustalane są osobno, w zasadach prowadzenia prac.

## Co jest sprawdzane

Badanie infrastruktury przemysłowej obejmuje:

- skanowanie portów typowych dla automatyki, z użyciem protokołów TCP, UDP i SCTP,
- identyfikację protokołów sterowania, w tym Modbus, BACnet, EtherNet/IP, OPC UA, DNP3, PROFINET i EtherCAT,
- odczyt informacji identyfikacyjnych udostępnianych przez sterowniki, na przykład modelu modułu i wersji sprzętu w urządzeniach Siemens S7,
- powiązanie wykrytych urządzeń z adresami i segmentami sieci, z których są osiągalne.

Wynikiem jest lista punktów styku automatyki z resztą sieci. Każdy z nich zawiera adres, wykryty protokół i dane identyfikacyjne urządzenia, a jego waga zależy od miejsca, z którego urządzenie jest osiągalne.

## Najczęstsze ustalenia

W sieciach przemysłowych powtarzają się podobne problemy:

- **Brak rzeczywistej segmentacji.** Sterowniki odpowiadają z sieci biurowej, choć według projektu powinny być osiągalne tylko ze stacji inżynierskiej.
- **Ujawnianie danych identyfikacyjnych.** Model, wersja oprogramowania i numer seryjny sterownika ułatwiają dopasowanie znanych podatności do konkretnego urządzenia.
- **Protokoły bez uwierzytelniania.** Usługa Modbus lub BACnet dostępna z szerokiego segmentu oznacza, że możliwość sterowania zależy wyłącznie od dostępu do sieci.
- **Pozostawione kanały serwisowe.** Zdalny dostęp dla integratora, założony na czas wdrożenia i nigdy niewyłączony.

## Jak wyniki przekładają się na działania

Zalecenia dla OT koncentrują się na dostępie, nie na aktualizacjach: wydzieleniu strefy automatyki, ograniczeniu ruchu do niezbędnych relacji między stacją inżynierską a sterownikami, kontroli kanałów serwisowych i monitorowaniu ruchu na granicy stref. Takie zmiany da się zwykle wdrożyć bez zatrzymywania produkcji, w uzgodnionym oknie serwisowym.

Badanie wspiera też obowiązki wynikające z dyrektywy NIS2, która obejmuje między innymi wybrane sektory produkcji, energetykę i gospodarkę wodną. Opis zależności między badaniem a wymaganiami zawiera artykuł [Obowiązki NIS2 w praktyce](/wiedza/obowiazki-nis2-w-praktyce), a zakres pozostałych testów strona [specyfikacji](/tests).
