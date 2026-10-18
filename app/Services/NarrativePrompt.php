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
        8. Nie używaj długich myślników (— ani –) wewnątrz zdań. Pisz płynnym tekstem: w miejscu,
           w którym narzuca się myślnik, użyj przecinka, nawiasu albo podziel myśl na dwa zdania.
           Zwykły łącznik w nazwach własnych (NetNTLMv2, VLAN-y) jest dozwolony.

        Format odpowiedzi: każdy blok poprzedzasz linią "### SEKCJA: klucz", gdzie klucz jest
        dokładnie taki, jak podano niżej. Nie dodajesz żadnych innych bloków ani tekstu poza nimi.
        TEXT;

        $voice = match ($variant) {
            NarrativeVariant::Expert => <<<'TEXT'
            Odbiorcą jest administrator sieci lub specjalista bezpieczeństwa, a dokument ma być
            formalnym, technicznym raportem z audytu. To wersja pogłębiona - bogatsza i bardziej
            szczegółowa niż wariant dla klienta.

            Rejestr: polszczyzna bezosobowa, strona bierna, chłodny ton rzeczoznawcy. Pisz
            "stwierdzono", "wykryto", "nie odnotowano" - nie "widzimy", "znaleźliśmy", "powinieneś".
            Bez emocji, bez moralizowania, bez zwrotów do czytelnika.

            Terminologii branżowej (skrypty NSE, NetNTLMv2, VLAN hopping, WPAD, egress filtering,
            ICS/OT) używaj swobodnie i nie tłumacz jej.

            Głębia techniczna. Każde ustalenie zakotwicz w konkretnym dowodzie z zestawienia:
            dokładna wersja oprogramowania i port, numer CVE oraz podany wynik CVSS, gdy występuje
            w danych, a także słowo stanu testu (VULNERABLE, LIKELY VULNERABLE, UNKNOWN), jeśli
            zostało przekazane. Nie wymyślaj wektorów CVSS ani liczb spoza zestawienia. Wyjaśniaj
            mechanizm podatności technicznie i wskazuj, dlaczego waga jest taka, a nie inna
            (ustalenie potwierdzone w działaniu kontra dopasowanie po numerze wersji). Gdzie to
            zasadne, odwołuj się do treści dowodu z pogłębionych testów.

            W sekcji "rekomendacje" stwórz listę numerowaną (1., 2., 3...), każdy punkt zaczynaj
            rzeczownikiem odczasownikowym i wiąż z konkretnym hostem, portem oraz usługą:
            "Wyłączenie obsługi SSLv3 na 192.168.0.1:8443", "Wdrożenie izolacji segmentu OT".
            Nie "należy wyłączyć" ani "wyłącz".

            Sekcje mogą być dłuższe niż w wariancie klienckim, od kilku zdań po rozbudowaną, zwięzłą
            listę, o ile każde zdanie niesie konkret z zestawienia. Nie lej wody dla objętości.
            TEXT,
            NarrativeVariant::Client => <<<'TEXT'
            Odbiorcą jest właściciel firmy lub osoba zarządzająca, która nie jest technikiem.
            Pisz spokojnym, prostym językiem. Unikaj żargonu; jeśli termin jest nieunikniony,
            wytłumacz go w tym samym zdaniu w kilku słowach. Nie podawaj nazw narzędzi ani poleceń.
            Tłumacz co dane ustalenie oznacza w praktyce dla firmy: co ktoś niepowołany mógłby
            zrobić i czym to grozi. Nie strasz, ale nie ukrywaj problemów. Każda sekcja to
            2-4 zdania w naturalnej polszczyźnie.

            W sekcji zaleceń przedstaw plan naprawy jako listę numerowaną (1., 2., 3...),
            uszeregowaną od najpilniejszego, korzystając z już nadanych wag. Nie zmieniaj wag ani
            nie dodawaj problemów spoza ustaleń. Każdy punkt to proste, konkretne działanie.
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
        // The model is asked for "### SEKCJA: klucz" before each block, but in
        // practice it sometimes drops the ###, misspells SEKCJA as SECCJA, or
        // writes the key with Polish diacritics ("urządzenia"). A strict match
        // would leave that marker sitting in the prose and leave the real
        // section empty, so the parser is deliberately tolerant: optional hashes,
        // the SE[KC][KC]JA spelling, then the key normalised before matching.
        // SE[KC]JA covers the real spellings seen: SEKCJA, SECCJA and SECJA.
        $pattern = '/^[ \t]*#{0,3}[ \t]*se[kc]{1,2}ja[ \t]*:[ \t]*(.+?)[ \t]*$/miu';

        $pieces = preg_split($pattern, $answer, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($pieces === false || count($pieces) < 3) {
            return [];
        }

        $sections = [];

        // Element 0 is whatever preceded the first marker; pairs follow.
        for ($i = 1; $i < count($pieces) - 1; $i += 2) {
            $key = self::normaliseKey($pieces[$i]);
            $body = self::stripForeign(trim($pieces[$i + 1]));

            if ($key !== null && $body !== '' && ! isset($sections[$key])) {
                $sections[$key] = $body;
            }
        }

        return $sections;
    }

    /**
     * DeepSeek occasionally drops a stray CJK token into otherwise Polish prose
     * (a Chinese "端口" for "port" was seen mid-sentence). Polish never uses these
     * scripts, so they are removed and the spacing they leave behind is tidied.
     */
    public static function stripForeign(string $text): string
    {
        $text = preg_replace('/[\x{3000}-\x{303F}\x{3400}-\x{9FFF}\x{FF00}-\x{FFEF}]/u', '', $text) ?? $text;

        return trim(preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text);
    }

    /**
     * Maps whatever the model wrote after the marker to one of our section keys,
     * stripping diacritics and trailing words, or null when it is none of them.
     */
    private static function normaliseKey(string $raw): ?string
    {
        $key = mb_strtolower(trim($raw));
        $key = strtr($key, [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z',
        ]);
        $key = preg_replace('/[^a-z]/', '', $key) ?? '';

        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, self::SECTIONS)) {
            return $key;
        }

        // The model may append words after the key ("uslugi sieciowe"); take the
        // known key the leftover starts with.
        foreach (array_keys(self::SECTIONS) as $known) {
            if (str_starts_with($key, $known)) {
                return $known;
            }
        }

        return null;
    }
}
