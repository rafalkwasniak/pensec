<?php

namespace App\Services;

use App\Support\Diagnostics;
use App\Support\NmapOutput;
use App\Support\Severity;

/**
 * Turns a stored report into the facts a document is allowed to state.
 *
 * This is the single source of every number that reaches a PDF. The tables in
 * the template read from here, and the prompt handed to the model is built from
 * here too - so the model is never the thing that counts hosts or decides which
 * port was open. It writes prose around figures it was given.
 *
 * Nothing is invented and nothing is dropped: a section with no findings comes
 * back as an empty list, and the template says so in words rather than omitting
 * the section. A reader must be able to tell "we looked and found nothing" from
 * "we did not look".
 */
class ReportFacts
{
    /**
     * Script output that only says the check came back clean. Used to decide
     * what gets emphasised, never what gets shown - every finding is kept.
     */
    private const QUIET_SCRIPT_OUTPUT = [
        "couldn't find",
        'could not find',
        'not vulnerable',
        'no results',
        'nothing found',
        'no accounts found',
    ];

    /**
     * The probe's pass/fail checks, in the order a reader meets them. Each is
     * classified rather than merely counted - see self::module().
     *
     * @var array<string, string>
     */
    private const EXPOSURE_MODULES = [
        'smb_null_sessions' => 'Anonimowe sesje i udziały SMB',
        'broadcast_poisoning_risks' => 'Zatruwanie rozgłoszeń',
        'nuclei_results' => 'Skanowanie szablonami znanych podatności',
        'default_credentials' => 'Domyślne poświadczenia',
        'ldap_leaks' => 'Wycieki z katalogu LDAP',
        'infrastructure_risks' => 'Ryzyka infrastrukturalne',
    ];

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public static function from(array $document): array
    {
        $hosts = self::hosts($document);
        $services = self::services($hosts);
        $deepFindings = self::deepFindings($document);
        $ics = self::icsEndpoints($document);
        $exposure = self::exposure($document);
        $diagnostics = self::diagnostics($document);

        return [
            'scan' => [
                'performed_at' => self::string($document, 'scan_time'),
                'orchestrator_ip' => self::string($document, 'orchestrator_ip'),
            ],
            'totals' => [
                'hosts_discovered' => count($hosts),
                'hosts_reachable' => count(array_filter($hosts, fn ($h) => $h['reachable'])),
                'hosts_with_open_ports' => count(array_filter($hosts, fn ($h) => $h['open_ports'] !== [])),
                'open_ports' => count($services),
                'deep_findings' => count($deepFindings),
                'deep_findings_notable' => count(array_filter($deepFindings, fn ($f) => $f['notable'])),
                'ics_endpoints' => count($ics),
                'exposure_findings' => array_sum(array_map(fn ($m) => count($m['findings']), $exposure)),
                'modules_failed' => count(array_filter($exposure, fn ($m) => $m['failed'])),
            ],
            'hosts' => $hosts,
            'services' => $services,
            'deep_findings' => $deepFindings,
            'ics_endpoints' => $ics,
            'fingerprints' => self::fingerprints($document),
            'exposure' => $exposure,
            'diagnostics' => $diagnostics,
        ] + self::ranked($deepFindings, $services, $ics, $exposure, $diagnostics);
    }

    /**
     * Everything worth acting on, gathered from every section and ordered by
     * weight, plus the places the badanie could not see.
     *
     * Without this a reader gets six sections of equal-looking text and has to
     * work out for themselves what to do first. The ranking is derived in code
     * from the evidence - see App\Support\Severity for how each grade is
     * reached - so the model orders nothing and promotes nothing.
     *
     * `gaps` is kept apart from `findings` on purpose. A test that never ran is
     * not a low-severity finding; presenting it as one would put it below real
     * problems, when it is precisely the thing that hides them.
     *
     * @param  list<array<string, mixed>>  $deepFindings
     * @param  list<array<string, mixed>>  $services
     * @param  list<array<string, mixed>>  $ics
     * @param  array<string, array<string, mixed>>  $exposure
     * @param  list<array<string, mixed>>  $diagnostics
     * @return array<string, mixed>
     */
    private static function ranked(array $deepFindings, array $services, array $ics, array $exposure, array $diagnostics): array
    {
        $findings = [];
        $gaps = [];

        foreach ($deepFindings as $finding) {
            if (! $finding['notable']) {
                continue;
            }

            $grade = Severity::ofScript($finding['output']);

            if ($grade['inconclusive'] && $grade['level'] === Severity::INFO) {
                $gaps[] = [
                    'title' => 'Test '.$finding['name'].' nie uzyskał odpowiedzi',
                    'ip' => $finding['ip'],
                    'source' => 'Pogłębione testy',
                ];

                continue;
            }

            $findings[] = [
                'level' => $grade['level'],
                'title' => Severity::titleOf($finding['output'], $finding['name']),
                'ip' => $finding['ip'],
                'where' => $finding['name'],
                'source' => 'Pogłębione testy',
                'cves' => Severity::cvesIn($finding['output']),
                'confirmed' => $grade['confirmed'],
                'note' => $grade['inconclusive'] ? 'Test nie zdołał potwierdzić ustalenia.' : null,
            ];
        }

        foreach ($services as $service) {
            $exposed = Severity::ofPort($service['port']);

            if ($exposed === null) {
                continue;
            }

            $findings[] = [
                'level' => $exposed['level'],
                'title' => $exposed['name'].' dostępny w sieci',
                'ip' => $service['ip'],
                'where' => $service['port'].'/'.$service['transport'],
                'source' => 'Usługi',
                'cves' => [],
                'confirmed' => true,
                'note' => $exposed['why'],
            ];
        }

        foreach ($ics as $endpoint) {
            $level = Severity::ofProbeWord($endpoint['severity'] ?? null);

            if ($level === Severity::INFO) {
                continue;
            }

            $findings[] = [
                'level' => $level,
                'title' => ($endpoint['protocol'] ?? 'Protokół przemysłowy').' dostępny w sieci',
                'ip' => $endpoint['ip'],
                'where' => ($endpoint['port'] ?? '?').'/'.($endpoint['transport'] ?? '?'),
                'source' => 'ICS/OT',
                'cves' => [],
                'confirmed' => true,
                'note' => null,
            ];
        }

        foreach ($exposure as $module) {
            if (! $module['present']) {
                $gaps[] = ['title' => $module['label'].' — sonda nie przekazała wyniku', 'ip' => null, 'source' => 'Ekspozycja'];

                continue;
            }

            if ($module['failed']) {
                $gaps[] = ['title' => $module['label'].' — test nie wykonał się', 'ip' => null, 'source' => 'Ekspozycja'];

                continue;
            }

            foreach ($module['findings'] as $finding) {
                $level = Severity::ofProbeWord($finding['severity'] ?? null);

                if ($level === Severity::INFO) {
                    continue;
                }

                $findings[] = [
                    'level' => $level,
                    'title' => (string) ($finding['assessment'] ?? $finding['type'] ?? $module['label']),
                    'ip' => isset($finding['ip']) ? (string) $finding['ip'] : null,
                    'where' => null,
                    'source' => $module['label'],
                    'cves' => [],
                    'confirmed' => true,
                    'note' => null,
                ];
            }
        }

        foreach ($diagnostics as $diagnostic) {
            if ($diagnostic['error'] !== null) {
                $gaps[] = ['title' => $diagnostic['label'].' — test nie wykonał się', 'ip' => null, 'source' => 'Diagnostyka'];
            }

            foreach ($diagnostic['fields'] as $field) {
                if (! $field['concern']) {
                    continue;
                }

                // A concern that means "this test did not happen" is coverage
                // missing, not something found in the network. When the test
                // also reported an error, that line already said so.
                if ($field['gap']) {
                    if ($diagnostic['error'] === null) {
                        $gaps[] = ['title' => $diagnostic['label'].' — '.$field['value'], 'ip' => null, 'source' => 'Diagnostyka'];
                    }

                    continue;
                }

                $findings[] = [
                    'level' => $field['level'],
                    'title' => $field['label'] !== null ? $field['label'].': '.$field['value'] : $field['value'],
                    'ip' => null,
                    'where' => null,
                    'source' => $diagnostic['label'],
                    'cves' => [],
                    'confirmed' => true,
                    'note' => null,
                ];
            }
        }

        usort($findings, fn (array $a, array $b): int => Severity::rank($a['level']) <=> Severity::rank($b['level']));

        $counts = array_fill_keys(Severity::ORDER, 0);

        foreach ($findings as $finding) {
            $counts[$finding['level']]++;
        }

        $actionable = count(array_filter($findings, fn ($f) => Severity::actionable($f['level'])));

        return [
            'findings' => $findings,
            'gaps' => $gaps,
            'severity_counts' => $counts,
            'actionable' => $actionable,
            // Which closing section the document ends with. A network with gaps
            // in its coverage never gets the congratulatory ending, because
            // nobody can vouch for what was not examined.
            'plan' => $actionable > 0 || $gaps !== [] ? 'repair' : 'maintain',
        ];
    }

    /**
     * Splits each exposure check into what it found and where it broke.
     *
     * This distinction is the whole point. A probe run where nuclei never
     * started still returns one entry per host - each carrying an error - and
     * counting those as findings would put "5 trafień skanowania szablonami"
     * into a client's report when the scanner in fact never ran. A module that
     * failed is reported as failed, which is both honest and the thing somebody
     * needs to know to go fix the probe.
     *
     * @param  array<string, mixed>  $document
     * @return array<string, array{label: string, present: bool, findings: list<array<string, mixed>>, errors: list<array<string, mixed>>, failed: bool}>
     */
    private static function exposure(array $document): array
    {
        $modules = [];

        foreach (self::EXPOSURE_MODULES as $key => $label) {
            $present = array_key_exists($key, $document);
            $results = $present ? self::results($document[$key]) : [];

            $errors = array_values(array_filter($results, self::isError(...)));
            $findings = array_values(array_filter($results, fn (array $r): bool => ! self::isError($r)));

            $modules[$key] = [
                'label' => $label,
                'present' => $present,
                'findings' => $findings,
                'errors' => $errors,
                // Errors and nothing else means the check never produced a verdict.
                'failed' => $errors !== [] && $findings === [],
            ];
        }

        return $modules;
    }

    /**
     * Walks a module's value down to its leaves. A leaf is an associative array
     * that either carries a status/error field or holds nothing but scalars;
     * anything else is a container and gets descended into. Written this way so
     * a module keyed by host, wrapped in sub-checks, or handed over as a flat
     * list all come back as the same list of results.
     *
     * @return list<array<string, mixed>>
     */
    private static function results(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        $results = [];

        if (! array_is_list($value)) {
            if (self::isLeaf($value)) {
                return [$value];
            }

            // A container keyed by host or sub-check. Its own scalar fields are
            // labels for the container, not results, so only arrays are followed.
            foreach ($value as $entry) {
                if (is_array($entry)) {
                    foreach (self::results($entry) as $result) {
                        $results[] = $result;
                    }
                }
            }

            return $results;
        }

        foreach ($value as $entry) {
            if (is_scalar($entry)) {
                $results[] = ['value' => $entry];

                continue;
            }

            foreach (self::results($entry) as $result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private static function isLeaf(array $value): bool
    {
        if (array_key_exists('status', $value) || array_key_exists('error', $value)) {
            return true;
        }

        foreach ($value as $entry) {
            if (is_array($entry)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private static function isError(array $result): bool
    {
        return ($result['status'] ?? null) === 'error'
            || (isset($result['error']) && $result['error'] !== '' && $result['error'] !== null);
    }

    /**
     * Discovered addresses joined with what the port scan saw on each. A host
     * that answered discovery but not the scan is kept and marked unreachable,
     * because that gap is itself worth reporting.
     *
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private static function hosts(array $document): array
    {
        $scans = self::map($document, 'nmap_results');

        /** @var list<string> $discovered */
        $discovered = array_values(array_filter(
            self::list($document, 'hosts'),
            fn ($ip): bool => is_string($ip),
        ));

        // An address that only appears in the scan map still belongs in the table.
        foreach (array_keys($scans) as $ip) {
            if (! in_array($ip, $discovered, true)) {
                $discovered[] = $ip;
            }
        }

        usort($discovered, fn (string $a, string $b): int => strnatcmp($a, $b));

        return array_map(function (string $ip) use ($scans): array {
            $output = is_string($scans[$ip] ?? null) ? $scans[$ip] : '';
            $mac = $output !== '' ? NmapOutput::macAddress($output) : null;

            return [
                'ip' => $ip,
                'mac' => $mac['mac'] ?? null,
                'vendor' => $mac['vendor'] ?? null,
                'scanned' => $output !== '',
                'reachable' => $output !== '' && NmapOutput::hostIsUp($output),
                'open_ports' => $output !== '' ? NmapOutput::openPorts($output) : [],
            ];
        }, $discovered);
    }

    /**
     * Every open port in the network, flattened, so the document can show one
     * table instead of one per host.
     *
     * @param  list<array<string, mixed>>  $hosts
     * @return list<array<string, mixed>>
     */
    private static function services(array $hosts): array
    {
        $services = [];

        foreach ($hosts as $host) {
            foreach ($host['open_ports'] as $port) {
                $services[] = $port + ['ip' => $host['ip']];
            }
        }

        return $services;
    }

    /**
     * NSE findings from the deep scan, attributed to the host they came from.
     *
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private static function deepFindings(array $document): array
    {
        $findings = [];

        foreach (self::map($document, 'deep_vulnerabilities') as $ip => $output) {
            if (! is_string($output)) {
                continue;
            }

            foreach (NmapOutput::scripts($output) as $script) {
                $findings[] = [
                    'ip' => (string) $ip,
                    'name' => $script['name'],
                    'output' => $script['output'],
                    'notable' => self::isNotable($script['output']),
                ];
            }
        }

        usort($findings, fn (array $a, array $b): int => [$b['notable'], $a['ip']] <=> [$a['notable'], $b['ip']]);

        return $findings;
    }

    /**
     * A finding is notable when its output is not one of nmap's ways of saying
     * "clean". Only drives emphasis and counts - nothing is hidden on this basis.
     */
    private static function isNotable(string $output): bool
    {
        $lowered = mb_strtolower($output);

        foreach (self::QUIET_SCRIPT_OUTPUT as $quiet) {
            if (str_contains($lowered, $quiet)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private static function icsEndpoints(array $document): array
    {
        $endpoints = [];

        foreach (self::map($document, 'ics_ot_risks') as $ip => $entries) {
            foreach (is_array($entries) ? $entries : [] as $entry) {
                if (is_array($entry)) {
                    $endpoints[] = $entry + ['ip' => (string) $ip];
                }
            }
        }

        return $endpoints;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private static function fingerprints(array $document): array
    {
        $fingerprints = [];

        foreach (self::map($document, 'service_fingerprints') as $ip => $entries) {
            foreach (is_array($entries) ? $entries : [] as $entry) {
                if (is_array($entry)) {
                    $fingerprints[] = $entry + ['ip' => (string) $ip];
                }
            }
        }

        return $fingerprints;
    }

    /**
     * Diagnostics, each described rather than dumped. See App\Support\Diagnostics
     * for why a raw `key: false` is not good enough to put in front of a reader.
     *
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private static function diagnostics(array $document): array
    {
        $diagnostics = [];

        foreach (self::map($document, 'diagnostics') as $key => $value) {
            $described = Diagnostics::describe((string) $key, $value);

            // A diagnostic with nothing to say at all is not worth a row; one
            // that only reports an error very much is.
            if ($described['rows'] === [] && $described['fields'] === []
                && $described['text'] === '' && $described['error'] === null) {
                continue;
            }

            $diagnostics[] = $described;
        }

        return $diagnostics;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private static function string(array $document, string $key): ?string
    {
        $value = $document[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<mixed>
     */
    private static function list(array $document, string $key): array
    {
        $value = $document[$key] ?? null;

        return is_array($value) ? array_values($value) : [];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private static function map(array $document, string $key): array
    {
        $value = $document[$key] ?? null;

        return is_array($value) ? $value : [];
    }
}
