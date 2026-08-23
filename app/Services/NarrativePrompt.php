<?php

namespace App\Services;

use App\Enums\NarrativeVariant;
use App\Models\Report;

/**
 * Builds the brief handed to the model, and splits its answer back apart.
 *
 * The model is given facts, never the raw document. Everything it is allowed to
 * say about numbers is already in the brief, which is what keeps a generated
 * report honest: it cannot name a host that was not discovered or a port that
 * was not open, because it never saw anything else.
 *
 * It answers in labelled blocks so the template can put each piece of prose
 * next to the table it belongs to. A missing block costs that section its
 * commentary and nothing else - the facts are rendered either way.
 */
class NarrativePrompt
{
    /** Marker the model must put before each block. Kept odd on purpose. */
    private const MARKER = '### SEKCJA:';

    /**
     * The document's sections, in the order they are written and rendered.
     *
     * @var array<string, string>
     */
    public const SECTIONS = [
        'podsumowanie' => 'Podsumowanie badania',
        'wektory' => 'Ustalenia według wagi',
        'urzadzenia' => 'Wykryte urządzenia',
        'uslugi' => 'Usługi dostępne w sieci',
        'podatnosci' => 'Pogłębione testy podatności',
        'ekspozycja' => 'Ekspozycja i poświadczenia',
        'ics' => 'Protokoły przemysłowe ICS i OT',
        'diagnostyka' => 'Diagnostyka sieci',
        'rekomendacje' => 'Zalecenia',
    ];

    public static function system(NarrativeVariant $variant): string
    {
        $shared = <<<'TEXT'
        Piszesz treść raportu z badania bezpieczeństwa sieci przeprowadzonego przez sondę Pensec.
        Odpowiadasz WYŁĄCZNIE po polsku.

        Zasady, których nie wolno złamać:
        1. Opierasz się TYLKO na faktach z przekazanego zestawienia. Nie wolno Ci wymyślić ani
           jednego adresu IP, portu, urządzenia, nazwy usługi, numeru CVE ani liczby, której nie ma
           w zestawieniu. Jeśli czegoś nie ma w danych, nie istnieje.
        2. Nie powtarzaj tabel. Liczby, listy hostów i portów są renderowane osobno przez system;
           Ty piszesz tekst wokół nich. Pojedyncze liczby możesz przywołać w zdaniu, jeśli pomagają.
        3. Nie używaj nagłówków markdown (#, ##, ###) wewnątrz bloków. Dozwolone są akapity,
           listy punktowane oraz **pogrubienie**. Nie twórz tabel.
        4. Gdy sekcja nie ma żadnych ustaleń, napisz to wprost i krótko. Nie nadrabiaj objętości.
           Brak wyniku to też wynik i tak go opisujesz - bez dramatyzowania i bez bagatelizowania.
           Gdy test wypadł czysto, pisz "stan prawidłowy", a nie "brak danych wskazujących na".
           Tego sformułowania używaj WYŁĄCZNIE gdy test się wykonał i niczego nie wykrył.
        5. Nie wymyślaj zaleceń do problemów, których badanie nie wykazało.
        6. Zakaz uogólnień: każde ustalenie przypisz do konkretnego adresu IP, jeśli zestawienie go
           podaje. "Na jednym z urządzeń" jest zabronione, gdy znasz adres.
        7. Wagi ustaleń (krytyczne, wysokie, średnie) są już wyliczone i podane w zestawieniu.
           Nie podnoś ich ani nie obniżaj i nie wymyślaj własnych.

        Format odpowiedzi: każdy blok poprzedzasz linią "### SEKCJA: klucz", gdzie klucz jest
        dokładnie taki, jak podano niżej. Nie dodajesz żadnych innych bloków ani tekstu poza nimi.
        TEXT;

        $voice = match ($variant) {
            NarrativeVariant::Expert => <<<'TEXT'
            Odbiorcą jest administrator sieci lub specjalista bezpieczeństwa, a dokument ma być
            formalnym raportem z audytu.

            Rejestr: polszczyzna bezosobowa, strona bierna, chłodny ton rzeczoznawcy. Pisz
            "stwierdzono", "wykryto", "nie odnotowano" - nie "widzimy", "znaleźliśmy", "powinieneś".
            Bez emocji, bez moralizowania, bez zwrotów do czytelnika.

            Terminologii branżowej (skrypty NSE, NetNTLMv2, VLAN hopping, WPAD, egress filtering,
            ICS/OT) używaj swobodnie i nie tłumacz jej.

            W sekcji "rekomendacje" każdy punkt zaczynaj rzeczownikiem odczasownikowym:
            "Wyłączenie obsługi SSLv3 na 192.168.0.1", "Wdrożenie izolacji segmentu OT",
            "Aktualizacja oprogramowania". Nie "należy wyłączyć" ani "wyłącz".

            Każda sekcja to 2-5 zdań albo zwięzła lista.
            TEXT,
            NarrativeVariant::Client => <<<'TEXT'
            Odbiorcą jest właściciel firmy lub osoba zarządzająca, która nie jest technikiem.
            Pisz spokojnym, prostym językiem. Unikaj żargonu; jeśli termin jest nieunikniony,
            wytłumacz go w tym samym zdaniu w kilku słowach. Nie podawaj nazw narzędzi ani poleceń.
            Tłumacz co dane ustalenie oznacza w praktyce dla firmy: co ktoś niepowołany mógłby
            zrobić i czym to grozi. Nie strasz, ale nie ukrywaj problemów. Każda sekcja to
            2-4 zdania w naturalnej polszczyźnie.
            TEXT,
        };

        return $shared."\n\n".$voice;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    public static function user(array $facts, Report $report): string
    {
        $keys = implode(', ', array_keys(self::SECTIONS));

        $brief = ReportBrief::render($facts, $report);

        // The closing section is chosen from the evidence, not by the model. A
        // network with holes in its coverage never gets the congratulatory
        // ending, whatever the model makes of the findings.
        $closing = $facts['plan'] === 'repair'
            ? 'Sieć wymaga działań naprawczych. Sekcja "rekomendacje" to PLAN NAPRAWY: lista'
                .' uszeregowana od najpilniejszego, oparta wyłącznie na ustaleniach powyżej.'
                .' Nie chwal konfiguracji.'
            : 'Badanie nie wykazało ustaleń wymagających działania ani luk w pokryciu. Sekcja'
                .' "rekomendacje" to PLAN UTRZYMANIA: krótko potwierdź dobry stan konfiguracji'
                .' i wskaż, co utrzymać. Nie wymyślaj problemów, żeby zapełnić listę.';

        return <<<TEXT
        Oto zestawienie faktów z badania. To jedyne dane, jakimi dysponujesz.

        {$brief}

        {$closing}

        Napisz treść raportu w blokach, dokładnie w tej kolejności i z tymi kluczami:
        {$keys}

        Przypomnienie o kluczach:
        - podsumowanie: biznesowa ocena ryzyka w dwóch akapitach. Przy stanie krytycznym ostrzeż
          wprost; przy sieci bez ustaleń krótko to potwierdź.
        - wektory: omów ustalenia w kolejności wagi, zaczynając od krytycznych. Powiedz, na czym
          polega mechanizm każdego z nich i czym grozi. Wspomnij też o lukach w pokryciu badania.
        - urzadzenia: co znaleziono w sieci, ile urządzeń odpowiedziało i co to znaczy.
        - uslugi: usługi wystawione przez urządzenia i ryzyko z nimi związane.
        - podatnosci: ustalenia pogłębionych testów uruchomionych przeciwko znalezionym usługom.
        - ekspozycja: udziały SMB, zatruwanie rozgłoszeń, domyślne poświadczenia, wycieki z katalogu.
        - ics: punkty końcowe protokołów przemysłowych i to, czy mają prawo być w tej sieci.
        - diagnostyka: kondycja i konfiguracja sieci wynikająca z testów diagnostycznych.
        - rekomendacje: patrz akapit powyżej. Nie dopisuj punktu o ponownym audycie ani o
          okresowych badaniach - system dokłada go sam.
        TEXT;
    }

    /**
     * Splits the answer into its blocks. Anything before the first marker, or
     * under a key we did not ask for, is discarded rather than shown.
     *
     * @return array<string, string>
     */
    public static function split(string $answer): array
    {
        $pattern = '/^'.preg_quote(self::MARKER, '/').'\s*([a-z]+)\s*$/mi';

        $pieces = preg_split($pattern, $answer, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($pieces === false || count($pieces) < 3) {
            return [];
        }

        $sections = [];

        // Element 0 is whatever preceded the first marker; pairs follow.
        for ($i = 1; $i < count($pieces) - 1; $i += 2) {
            $key = mb_strtolower(trim($pieces[$i]));
            $body = trim($pieces[$i + 1]);

            if (array_key_exists($key, self::SECTIONS) && $body !== '') {
                $sections[$key] = $body;
            }
        }

        return $sections;
    }
}
