<?php

namespace App\Services;

use App\Models\Report;
use App\Support\ResultText;
use App\Support\Severity;
use App\Support\TsharkEndpoints;

/**
 * Renders the facts as the plain-text brief the model reads.
 *
 * Deliberately not JSON. The document is handed over as short labelled lines so
 * the model spends its attention on the findings rather than on syntax, and so
 * that anything it writes can be traced back to a line a person can read.
 *
 * Long free-text blobs are clipped, and long lists are cut, and both are stated
 * in the text with the full count. A model that sees "(skrócono)" or "pominięto
 * 40" will not present a partial listing as exhaustive.
 */
class ReportBrief
{
    /** Beyond this a single diagnostic is a console dump, not a sentence. */
    private const MAX_TEXT = 900;

    /** Deep-scan findings are individually short, but there can be many. */
    private const MAX_FINDING = 400;

    /**
     * Lines per list. The ranked list is sorted most severe first, so what
     * falls past the limit is what matters least; every other list is cut the
     * same way and says how much was left out.
     */
    private const MAX_RANKED = 80;

    private const MAX_LIST = 40;

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
        return implode("\n", array_filter([
            'BADANIE',
            'Sonda: '.$report->device->name,
            'Identyfikator badania: '.$report->report_uid,
            'Data wykonania: '.($facts['scan']['performed_at'] ?? $report->received_at->format('Y-m-d H:i:s')),
            'Adres sondy w badanej sieci: '.($facts['scan']['orchestrator_ip'] ?? 'nieznany'),
            isset($facts['scan']['outcome_note']) ? 'Przebieg badania: '.$facts['scan']['outcome_note'] : null,
        ]));
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function totals(array $facts): string
    {
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
        ];

        $lines = ['LICZBY'];

        foreach ($labels as $key => $label) {
            $lines[] = $label.': '.($facts['totals'][$key] ?? 0);
        }

        $lines[] = 'Ustaleń wymagających działania: '.$facts['actionable'];
        $lines[] = 'Luk w pokryciu badania: '.count($facts['gaps']);

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

        $lines = ['USTALENIA WEDŁUG WAGI (kolejność i wagi są już wyliczone - nie zmieniaj ich)'];

        foreach (array_slice($facts['findings'], 0, self::MAX_RANKED) as $finding) {
            $where = implode(' ', array_filter([$finding['ip'], $finding['where']], fn ($part): bool => $part !== null));
            $cves = array_slice($finding['cves'], 0, 10);

            $lines[] = sprintf(
                '[%s] %s%s (źródło: %s)%s%s',
                mb_strtoupper(Severity::label($finding['level'])),
                $where !== '' ? $where.' — ' : '',
                self::clip($finding['title'], self::MAX_FINDING),
                $finding['source'],
                $cves !== [] ? ' '.implode(', ', $cves).(count($finding['cves']) > count($cves) ? ' i '.(count($finding['cves']) - count($cves)).' innych CVE' : '') : '',
                $finding['note'] !== null ? ' Uwaga: '.$finding['note'] : '',
            );
        }

        $lines[] = self::omitted(count($facts['findings']), self::MAX_RANKED, 'ustaleń o niższej wadze');

        return implode("\n", array_filter($lines));
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
            $hosts = $gap['hosts'] ?? [];

            $lines[] = '- '.($gap['ip'] !== null ? $gap['ip'].' — ' : '').$gap['title']
                .($hosts !== [] ? ' ('.self::listed($hosts, 12).')' : '');
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

        $lines = ['URZĄDZENIA'.self::notRun($facts, 'port_scanning')];

        foreach ($facts['hosts'] as $host) {
            $parts = [$host['ip']];
            $identity = trim(implode(' ', array_filter([$host['vendor'] ?? null, $host['model'] ?? null])));

            if ($host['mac'] !== null) {
                $parts[] = 'MAC '.$host['mac'];
            }

            if ($identity !== '') {
                $parts[] = $identity;
            }

            if (($host['os'] ?? null) !== null) {
                $parts[] = 'system '.$host['os'];
            }

            $parts[] = match (true) {
                ($host['scan_failed'] ?? null) !== null => 'skanowanie portów nieudane ('.$host['scan_failed'].')',
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
            return 'OTWARTE USŁUGI'.self::notRun($facts, 'port_scanning')."\n".'Żadne urządzenie nie wystawiło otwartego portu.';
        }

        $lines = ['OTWARTE USŁUGI'.self::notRun($facts, 'port_scanning')];

        foreach ($facts['services'] as $service) {
            $lines[] = sprintf(
                '%s %s/%s %s%s (stan: %s)',
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
     * Notable findings in full; clean ones are only counted, since "nothing
     * found" forty times over tells the model nothing it can use.
     *
     * @param  array<string, mixed>  $facts
     */
    private static function deepFindings(array $facts): string
    {
        $heading = 'POGŁĘBIONE TESTY'.self::notRun($facts, 'vulnerability_scanning');

        if ($facts['deep_findings'] === []) {
            return $heading."\n".'Testy nie zwróciły żadnych ustaleń.';
        }

        $notable = array_values(array_filter($facts['deep_findings'], fn (array $f): bool => $f['notable']));
        $lines = [$heading];

        foreach (array_slice($notable, 0, self::MAX_LIST) as $finding) {
            $lines[] = sprintf(
                '[istotne] %s %s: %s',
                $finding['ip'],
                $finding['name'],
                self::clip(str_replace("\n", ' / ', $finding['output']), self::MAX_FINDING),
            );
        }

        $lines[] = self::omitted(count($notable), self::MAX_LIST, 'istotnych ustaleń');

        $clean = count($facts['deep_findings']) - count($notable);

        if ($clean > 0) {
            $lines[] = 'Pozostałe '.$clean.' testów zakończyło się wynikiem czystym.';
        }

        return implode("\n", array_filter($lines));
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
                $lines[] = $module['label'].': TEST NIE WYKONAŁ SIĘ'
                    .($module['errors'] !== [] ? ' ('.count($module['errors']).' błędów narzędzia).' : '.');

                foreach (array_slice($module['errors'], 0, 3) as $error) {
                    $lines[] = '  - błąd: '.self::clip(ResultText::describe($error), self::MAX_FINDING);
                }

                continue;
            }

            if ($module['not_applicable'] ?? false) {
                $lines[] = $module['label'].': nie dotyczy - sonda pominęła test, bo w sieci nie było usług, których dotyczy.';

                continue;
            }

            $partial = ($module['partial'] ?? false)
                ? ' TEST NIE OBJĄŁ CAŁEJ SIECI'.(($module['missed_hosts'] ?? []) !== [] ? ' (nie powiódł się na '.count($module['missed_hosts']).' urządzeniach)' : '')
                    .' - wynik dotyczy tylko sprawdzonej części.'
                : '';

            // Grouped non-security extractor hits; counted context, not findings.
            $observations = ($module['observations'] ?? 0) > 0
                ? ' Ponadto '.$module['observations'].' technicznych obserwacji narzędzia (nie podatności).'
                : '';

            if ($module['findings'] === []) {
                $lines[] = $module['label'].': nie stwierdzono podatności.'.$partial.$observations;

                continue;
            }

            $lines[] = $module['label'].': '.count($module['findings']).' ustaleń.'.$partial.$observations;

            foreach (array_slice($module['findings'], 0, self::MAX_LIST) as $finding) {
                $lines[] = '  - '.self::clip(ResultText::describe($finding), self::MAX_FINDING);
            }

            $lines[] = self::omitted(count($module['findings']), self::MAX_LIST, 'ustaleń w tym teście', '  - ');
        }

        return implode("\n", array_filter($lines));
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private static function ics(array $facts): string
    {
        $heading = 'PROTOKOŁY ICS/OT'.self::notRun($facts, 'ics_ot');

        if ($facts['ics_endpoints'] === []) {
            return $heading."\n".'Nie wykryto punktów końcowych protokołów przemysłowych.';
        }

        $lines = [$heading];

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
            // the prose; the document itself shows the table.
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

    /**
     * Appended to a section heading when the test behind it did not run, so an
     * empty section is not read as a clean one.
     *
     * @param  array<string, mixed>  $facts
     */
    private static function notRun(array $facts, string $group): string
    {
        $state = $facts['coverage'][$group] ?? null;

        if (! ($state['problem'] ?? false)) {
            return '';
        }

        return match (true) {
            ! $state['ran'] => ' (TEN TEST SIĘ NIE ODBYŁ - brak wyników nie oznacza braku problemów)',
            $state['hosts'] !== [] => ' (test nie powiódł się na '.count($state['hosts']).' urządzeniach - wyniki dotyczą tylko pozostałych)',
            default => ' (test wykonany częściowo - wyniki mogą być niepełne)',
        };
    }

    /**
     * The line that says a list was cut, or null when it was not.
     */
    private static function omitted(int $total, int $shown, string $what, string $prefix = ''): ?string
    {
        return $total > $shown ? $prefix.'(pominięto '.($total - $shown).' kolejnych '.$what.' - łącznie '.$total.')' : null;
    }

    /**
     * @param  list<string>  $items
     */
    private static function listed(array $items, int $limit): string
    {
        return implode(', ', array_slice($items, 0, $limit))
            .(count($items) > $limit ? ' i '.(count($items) - $limit).' innych' : '');
    }

    private static function clip(string $text, int $limit): string
    {
        $text = trim($text);

        return mb_strlen($text) <= $limit
            ? $text
            : mb_substr($text, 0, $limit).' […] (skrócono)';
    }
}
