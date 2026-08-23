<?php

namespace App\Services;

use App\Models\Report;
use App\Support\Severity;
use App\Support\TsharkEndpoints;

/**
 * Renders the facts as the plain-text brief the model reads.
 *
 * Deliberately not JSON. The document is handed over as short labelled lines so
 * the model spends its attention on the findings rather than on syntax, and so
 * that anything it writes can be traced back to a line a person can read.
 *
 * Long free-text blobs are clipped, and clipping is stated in the text. A model
 * that sees "(skrócono)" will not present a partial listing as exhaustive.
 */
class ReportBrief
{
    /** Beyond this a single diagnostic is a console dump, not a sentence. */
    private const MAX_TEXT = 900;

    /** Deep-scan findings are individually short, but there can be many. */
    private const MAX_FINDING = 400;

    /**
     * @param  array<string, mixed>  $facts
     */
    public static function render(array $facts, Report $report): string
    {
        return implode("\n\n", array_filter([
            self::header($facts, $report),
            self::totals($facts),
            self::ranked($facts),
            self::gaps($facts),
            self::hosts($facts),
            self::services($facts),
            self::deepFindings($facts),
            self::exposure($facts),
            self::ics($facts),
            self::diagnostics($facts),
        ]));
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function header(array $facts, Report $report): string
    {
        return implode("\n", [
            'BADANIE',
            'Sonda: '.$report->device->name,
            'Identyfikator badania: '.$report->report_uid,
            'Data wykonania: '.($facts['scan']['performed_at'] ?? $report->received_at->format('Y-m-d H:i:s')),
            'Adres sondy w badanej sieci: '.($facts['scan']['orchestrator_ip'] ?? 'nieznany'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function totals(array $facts): string
    {
        $totals = $facts['totals'];

        $labels = [
            'hosts_discovered' => 'Wykrytych urządzeń',
            'hosts_reachable' => 'Odpowiedziało na skanowanie portów',
            'hosts_with_open_ports' => 'Urządzeń z co najmniej jednym otwartym portem',
            'open_ports' => 'Otwartych portów łącznie',
            'deep_findings' => 'Ustaleń z pogłębionych testów',
            'deep_findings_notable' => 'W tym ustaleń wymagających uwagi',
            'ics_endpoints' => 'Punktów końcowych ICS/OT',
            'exposure_findings' => 'Ustaleń w testach ekspozycji i poświadczeń',
            'modules_failed' => 'Modułów badania, które nie wykonały się poprawnie',
            'actionable' => 'Ustaleń wymagających działania',
        ];

        $lines = ['LICZBY'];

        foreach ($labels as $key => $label) {
            $lines[] = $label.': '.($totals[$key] ?? $facts[$key] ?? 0);
        }

        foreach ($facts['severity_counts'] as $level => $count) {
            $lines[] = 'Ustaleń o wadze '.mb_strtolower(Severity::label($level)).': '.$count;
        }

        return implode("\n", $lines);
    }

    /**
     * The ranked list. Weights are already decided; the model is told so, and
     * told not to re-grade anything.
     *
     * @param  array<string, mixed>  $facts
     */
    private static function ranked(array $facts): string
    {
        if ($facts['findings'] === []) {
            return 'USTALENIA WEDŁUG WAGI'."\n".'Badanie nie wykazało ustaleń wymagających działania.';
        }

        $lines = [
            'USTALENIA WEDŁUG WAGI (kolejność i wagi są już wyliczone - nie zmieniaj ich)',
        ];

        foreach ($facts['findings'] as $finding) {
            $parts = [];

            if ($finding['ip'] !== null) {
                $parts[] = $finding['ip'];
            }

            if ($finding['where'] !== null) {
                $parts[] = $finding['where'];
            }

            $lines[] = sprintf(
                '[%s] %s%s (źródło: %s)%s%s',
                mb_strtoupper(Severity::label($finding['level'])),
                $parts !== [] ? implode(' ', $parts).' — ' : '',
                self::clip($finding['title'], self::MAX_FINDING),
                $finding['source'],
                $finding['cves'] !== [] ? ' '.implode(', ', $finding['cves']) : '',
                $finding['note'] !== null ? ' Uwaga: '.$finding['note'] : '',
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Where the badanie could not see. Kept apart from the findings so the
     * model cannot present a hole in the coverage as a mild problem.
     *
     * @param  array<string, mixed>  $facts
     */
    private static function gaps(array $facts): string
    {
        if ($facts['gaps'] === []) {
            return '';
        }

        $lines = [
            'LUKI W POKRYCIU BADANIA (testy, które się nie odbyły - o tych obszarach NIE WOLNO'
            .' napisać, że są bezpieczne ani że nic w nich nie znaleziono)',
        ];

        foreach ($facts['gaps'] as $gap) {
            $lines[] = '- '.($gap['ip'] !== null ? $gap['ip'].' — ' : '').$gap['title'];
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function hosts(array $facts): string
    {
        if ($facts['hosts'] === []) {
            return 'URZĄDZENIA'."\n".'Nie wykryto żadnego urządzenia.';
        }

        $lines = ['URZĄDZENIA'];

        foreach ($facts['hosts'] as $host) {
            $parts = [$host['ip']];

            if ($host['mac'] !== null) {
                $parts[] = 'MAC '.$host['mac'].($host['vendor'] !== null ? ' ('.$host['vendor'].')' : '');
            }

            $parts[] = match (true) {
                ! $host['scanned'] => 'nie objęty skanowaniem portów',
                ! $host['reachable'] => 'nie odpowiedział podczas skanowania portów',
                $host['open_ports'] === [] => 'osiągalny, brak otwartych portów',
                default => 'osiągalny, otwarte porty: '.implode(', ', array_map(
                    fn (array $p): string => $p['port'].'/'.$p['transport'],
                    $host['open_ports'],
                )),
            };

            $lines[] = implode(', ', $parts);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function services(array $facts): string
    {
        if ($facts['services'] === []) {
            return 'OTWARTE USŁUGI'."\n".'Żadne urządzenie nie wystawiło otwartego portu.';
        }

        $lines = ['OTWARTE USŁUGI'];

        foreach ($facts['services'] as $service) {
            $lines[] = sprintf(
                '%s %d/%s %s%s (stan: %s)',
                $service['ip'],
                $service['port'],
                $service['transport'],
                $service['service'],
                $service['version'] !== null ? ' - '.$service['version'] : '',
                $service['state'],
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function deepFindings(array $facts): string
    {
        if ($facts['deep_findings'] === []) {
            return 'POGŁĘBIONE TESTY'."\n".'Testy nie zwróciły żadnych ustaleń.';
        }

        $lines = ['POGŁĘBIONE TESTY (ustalenia oznaczone [istotne] nie są zwykłym "czysto")'];

        foreach ($facts['deep_findings'] as $finding) {
            $lines[] = sprintf(
                '%s %s %s: %s',
                $finding['notable'] ? '[istotne]' : '[czysto]',
                $finding['ip'],
                $finding['name'],
                self::clip(str_replace("\n", ' / ', $finding['output']), self::MAX_FINDING),
            );
        }

        return implode("\n", $lines);
    }

    /**
     * The checks that most often come back empty. They are listed even when
     * empty, so the model states that they ran rather than skipping them.
     *
     * @param  array<string, mixed>  $facts
     */
    private static function exposure(array $facts): string
    {
        $lines = [
            'EKSPOZYCJA I POŚWIADCZENIA',
            'Uwaga: "test nie wykonał się" to NIE jest wynik czysty. Taki test nic nie sprawdził'
            .' i nie wolno o nim pisać, że niczego nie wykrył - napisz, że się nie odbył.',
        ];

        foreach ($facts['exposure'] as $module) {
            if (! $module['present']) {
                $lines[] = $module['label'].': brak danych w raporcie - sonda nie przekazała wyniku.';

                continue;
            }

            if ($module['failed']) {
                $lines[] = $module['label'].': TEST NIE WYKONAŁ SIĘ ('.count($module['errors']).' błędów narzędzia).';

                foreach (array_slice($module['errors'], 0, 3) as $error) {
                    $lines[] = '  - błąd: '.self::clip(self::describe($error), self::MAX_FINDING);
                }

                continue;
            }

            if ($module['findings'] === []) {
                $lines[] = $module['label'].': stan prawidłowy - nie stwierdzono podatności.';

                continue;
            }

            $lines[] = $module['label'].': '.count($module['findings']).' ustaleń.';

            foreach ($module['findings'] as $finding) {
                $lines[] = '  - '.self::clip(self::describe($finding), self::MAX_FINDING);
            }

            if ($module['errors'] !== []) {
                $lines[] = '  - uwaga: dodatkowo '.count($module['errors']).' błędów narzędzia w tym teście.';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function ics(array $facts): string
    {
        if ($facts['ics_endpoints'] === []) {
            return 'PROTOKOŁY ICS/OT'."\n".'Nie wykryto punktów końcowych protokołów przemysłowych.';
        }

        $lines = ['PROTOKOŁY ICS/OT'];

        foreach ($facts['ics_endpoints'] as $endpoint) {
            $lines[] = sprintf(
                '%s %s/%s %s, stan: %s, ocena sondy: %s',
                $endpoint['ip'],
                $endpoint['port'] ?? '?',
                $endpoint['transport'] ?? '?',
                $endpoint['protocol'] ?? 'nieznany protokół',
                $endpoint['state'] ?? 'nieznany',
                $endpoint['severity'] ?? 'brak oceny',
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function diagnostics(array $facts): string
    {
        if ($facts['diagnostics'] === []) {
            return 'DIAGNOSTYKA'."\n".'Brak wyników diagnostycznych.';
        }

        $lines = [
            'DIAGNOSTYKA',
            'Ustalenia poprzedzone "[!]" to te, które sonda uznała za niepokojące.',
        ];

        foreach ($facts['diagnostics'] as $diagnostic) {
            $lines[] = $diagnostic['label'].':';

            if ($diagnostic['error'] !== null) {
                $lines[] = '  - TEST NIE WYKONAŁ SIĘ: '.$diagnostic['error'];
            }

            foreach ($diagnostic['fields'] as $field) {
                $lines[] = '  - '.($field['concern'] ? '[!] ' : '')
                    .($field['label'] !== null ? $field['label'].': ' : '')
                    .self::clip($field['value'], self::MAX_FINDING);
            }

            // The traffic table is a ranking, so only the busiest few matter to
            // the prose; the document itself shows every row.
            foreach (array_slice($diagnostic['rows'], 0, 5) as $row) {
                $lines[] = sprintf(
                    '  - %s: %d pakietów, %s ruchu',
                    $row['address'],
                    $row['packets'],
                    TsharkEndpoints::bytes($row['bytes']),
                );
            }

            if ($diagnostic['text'] !== '') {
                $lines[] = '  - '.self::clip(str_replace("\n", ' / ', $diagnostic['text']), self::MAX_TEXT);
            }
        }

        return implode("\n", $lines);
    }

    private static function describe(mixed $entry): string
    {
        if (is_scalar($entry)) {
            return (string) $entry;
        }

        if (! is_array($entry)) {
            return '';
        }

        $parts = [];

        foreach ($entry as $key => $value) {
            if (is_scalar($value) && (string) $value !== '') {
                $parts[] = $key.': '.$value;
            }
        }

        return implode(', ', $parts);
    }

    private static function clip(string $text, int $limit): string
    {
        $text = trim($text);

        return mb_strlen($text) <= $limit
            ? $text
            : mb_substr($text, 0, $limit).' […] (skrócono)';
    }
}
