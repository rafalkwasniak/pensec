<?php

namespace App\Services;

use App\Models\Report;
use App\Support\Severity;

/**
 * Builds the brief for the technician repair guide, and splits the answer back.
 *
 * The guide is grounded the same way the reports are: the model is handed only
 * the findings the scan actually produced, each with the real device it sits on
 * (vendor, model, firmware) and the service and version behind it. It never
 * invents a problem - it only explains how to fix the ones on the list.
 *
 * The step-by-step "what to click" is the point of this document, so the model
 * is asked for concrete steps. Where an exact menu path depends on a firmware
 * version, it gives the most likely one and a step to verify it, rather than
 * asserting it as fact - a confidently wrong setting on production gear is
 * worse than a careful one. The template carries that caveat in writing.
 */
class RemediationPrompt
{
    /** Marker before each repair block, keyed by the finding's 1-based number. */
    private const MARKER = '### KROK:';

    /** Repair steps are only asked for the findings worth acting on. */
    private const MAX_FINDINGS = 40;

    public static function system(): string
    {
        return <<<'TEXT'
        Jesteś doświadczonym technikiem bezpieczeństwa sieci. Tworzysz praktyczny przewodnik
        naprawy dla technika klienta, który będzie wprowadzał zmiany w konkretnych urządzeniach.
        Odpowiadasz WYŁĄCZNIE po polsku.

        Zasady, których nie wolno złamać:
        1. Naprawiasz TYLKO usterki z przekazanej listy. Nie wymyślasz nowych problemów, adresów,
           urządzeń ani podatności. Każdy blok odpowiada jednej usterce z listy.
        2. Kroki dostosuj do wskazanego urządzenia (producent, model, wersja oprogramowania) oraz
           usługi i portu. Pisz konkretnie: co otworzyć, gdzie wejść, co ustawić albo jaką komendę
           wykonać na tym sprzęcie.
        3. Jeśli dokładna ścieżka w panelu zależy od wersji oprogramowania i nie masz pewności,
           podaj najbardziej prawdopodobną i wyraźnie dopisz, że należy ją potwierdzić w panelu lub
           dokumentacji producenta. Nie zmyślaj nazw opcji z pełną pewnością.
        4. Każdy blok kończ krokiem weryfikacji: jak sprawdzić, że usterka została usunięta.
        5. Nie podnoś ani nie obniżaj wagi usterki - jest już ustalona.
        6. Nie dopisuj wstępu, podsumowania ani punktu o ponownym audycie. System dokłada ramkę
           i stopkę sam.

        Format odpowiedzi: dla każdej usterki jeden blok. Blok zaczynasz linią "### KROK: N", gdzie
        N to numer usterki z listy. Pod nią numerowana lista kroków (1., 2., 3...), a na końcu
        osobny wiersz zaczynający się od "Weryfikacja:". Nie używaj nagłówków markdown wewnątrz bloku.
        TEXT;
    }

    /**
     * The actionable findings, numbered, each with the device and service it was
     * found on so the steps can be specific.
     *
     * @param  array<string, mixed>  $facts
     */
    public static function user(array $facts, Report $report): string
    {
        $findings = self::actionable($facts);

        if ($findings === []) {
            return 'Badanie sondy '.$report->device->name.' (identyfikator '.$report->report_uid.') nie'
                .' wykazało usterek wymagających naprawy. Napisz jedną linię: "### KROK: 0" i pod nią'
                .' krótkie zdanie, że konfiguracja nie wymaga zmian, a sieć należy utrzymać w obecnym stanie.';
        }

        $hosts = [];

        foreach ($facts['hosts'] as $host) {
            $hosts[$host['ip']] = $host;
        }

        $lines = [
            'Badanie sondy '.$report->device->name.', identyfikator '.$report->report_uid.'.',
            'Oto usterki do naprawy. To jedyne dane, jakimi dysponujesz. Dla każdej napisz blok'
                .' "### KROK: N" z konkretnymi krokami naprawy na wskazanym urządzeniu.',
            '',
        ];

        foreach ($findings as $index => $finding) {
            $number = $index + 1;
            $where = $finding['where'] !== null ? ' ('.$finding['where'].')' : '';
            $lines[] = 'USTERKA '.$number.' — ['.mb_strtoupper(Severity::label($finding['level'])).'] '
                .($finding['ip'] ?? 'cała sieć').$where.': '.$finding['title'];

            $host = $finding['ip'] !== null ? ($hosts[$finding['ip']] ?? null) : null;
            $device = $host !== null ? self::device($host) : null;

            if ($device !== null) {
                $lines[] = '  Urządzenie: '.$device;
            }

            $services = $host !== null ? self::services($host) : null;

            if ($services !== null) {
                $lines[] = '  Usługi na urządzeniu: '.$services;
            }

            if ($finding['note'] !== null && $finding['note'] !== '') {
                $lines[] = '  Szczegóły: '.$finding['note'];
            }

            if ($finding['cves'] !== []) {
                $lines[] = '  CVE: '.implode(', ', array_slice($finding['cves'], 0, 8));
            }

            if (($finding['confirmed'] ?? true) === false) {
                $lines[] = '  Uwaga: ustalenie niepotwierdzone - najpierw zweryfikuj, czy występuje.';
            }
        }

        $lines[] = '';
        $lines[] = 'Napisz bloki "### KROK: 1" … "### KROK: '.count($findings).'" w tej samej kolejności.';

        return implode("\n", $lines);
    }

    /**
     * The findings a technician acts on, in the report's own order, capped.
     *
     * @param  array<string, mixed>  $facts
     * @return list<array<string, mixed>>
     */
    public static function actionable(array $facts): array
    {
        $findings = array_values(array_filter(
            $facts['findings'] ?? [],
            fn (array $f): bool => Severity::actionable($f['level']),
        ));

        return array_slice($findings, 0, self::MAX_FINDINGS);
    }

    /**
     * Splits the answer into repair blocks keyed by finding number.
     *
     * @return array<int, string>
     */
    public static function split(string $answer): array
    {
        $pattern = '/^'.preg_quote(self::MARKER, '/').'\s*(\d+)\s*$/m';
        $pieces = preg_split($pattern, $answer, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($pieces === false || count($pieces) < 3) {
            return [];
        }

        $blocks = [];

        for ($i = 1; $i < count($pieces) - 1; $i += 2) {
            $number = (int) $pieces[$i];
            $body = trim($pieces[$i + 1]);

            if ($body !== '') {
                $blocks[$number] = $body;
            }
        }

        return $blocks;
    }

    /**
     * @param  array<string, mixed>  $host
     */
    private static function device(array $host): ?string
    {
        $parts = array_filter([
            $host['vendor'] ?? null,
            $host['model'] ?? null,
            ($host['os'] ?? null) !== null ? 'system '.$host['os'] : null,
        ]);

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }

    /**
     * The host's open ports with the software behind them - what a technician
     * logs into or reconfigures.
     *
     * @param  array<string, mixed>  $host
     */
    private static function services(array $host): ?string
    {
        $ports = array_map(function (array $p): string {
            $software = ($p['version'] ?? null) ?: ($p['service'] ?? null);

            return $p['port'].'/'.$p['transport'].($software ? ' '.$software : '');
        }, array_slice($host['open_ports'] ?? [], 0, 8));

        return $ports === [] ? null : implode(', ', $ports);
    }
}
