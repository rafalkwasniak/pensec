<?php

namespace App\Services;

use App\Models\Report;
use App\Support\Diagnostics;
use App\Support\NmapOutput;
use App\Support\Polish;
use App\Support\ReportSections;
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
 * "we did not look". Lists come back whole; how much of a list fits on a page
 * or into a prompt is decided where it is shown, and said there.
 *
 * Two generations of probe output are read. The older one carries nmap's
 * console text and per-module result lists. Schema 3 adds structured scans,
 * an asset inventory, CVE correlations and - most usefully - the probe's own
 * account of which test groups ran (`audit_group_status`) and how the run ended
 * (`report_summary`). Where both exist, the structured form wins.
 */
class ReportFacts
{
    /**
     * Every top-level key read below. ReportSections decodes these and nothing
     * else, so a key used here but missing from this list silently reads as
     * absent - keep the two in step.
     */
    public const SECTIONS = [
        'scan_time', 'scan_started_at', 'orchestrator_ip', 'discovered_hosts_count', 'hosts',
        'nmap_results', 'nmap_structured', 'asset_inventory', 'deep_vulnerabilities',
        'ics_ot_risks', 'service_fingerprints', 'nse_script_evidence', 'diagnostics', 'module_status',
        'audit_group_status', 'report_summary', 'device_security_posture', 'cve_correlations',
        'smb_null_sessions', 'broadcast_poisoning_risks', 'nuclei_results',
        'default_credentials', 'ldap_leaks', 'infrastructure_risks', 'unattributed_identity_attempts',
    ];

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
     * The probe's pass/fail checks, in the order a reader meets them, each with
     * the audit group that says whether it actually ran. Without the group an
     * empty result list cannot be told apart from a test that never started.
     *
     * @var array<string, array{label: string, group: string|null}>
     */
    private const EXPOSURE_MODULES = [
        'smb_null_sessions' => ['label' => 'Anonimowe sesje i udziały SMB', 'group' => 'smb'],
        'broadcast_poisoning_risks' => ['label' => 'Zatruwanie rozgłoszeń', 'group' => 'responder'],
        'nuclei_results' => ['label' => 'Skanowanie szablonami znanych podatności', 'group' => 'nuclei'],
        'default_credentials' => ['label' => 'Domyślne poświadczenia', 'group' => 'credential_auditing'],
        'ldap_leaks' => ['label' => 'Wycieki z katalogu LDAP', 'group' => 'directory_services'],
        'infrastructure_risks' => ['label' => 'Ryzyka infrastrukturalne', 'group' => 'tls'],
    ];

    /** @var array<string, string> */
    private const AUDIT_GROUPS = [
        'network_discovery' => 'Wykrywanie urządzeń',
        'port_scanning' => 'Skanowanie portów',
        'vulnerability_scanning' => 'Pogłębione testy podatności',
        'nuclei' => 'Skanowanie szablonami (nuclei)',
        'nuclei_oast' => 'Testy pozapasmowe nuclei (OAST)',
        'service_fingerprinting' => 'Identyfikacja usług i urządzeń',
        'smb' => 'Udziały i sesje SMB',
        'snmp' => 'SNMP',
        'databases' => 'Bazy danych',
        'tls' => 'Konfiguracja TLS',
        'directory_services' => 'Usługi katalogowe (LDAP/AD)',
        'ics_ot' => 'Protokoły przemysłowe ICS/OT',
        'web_fuzzing' => 'Testy aplikacji WWW',
        'credential_auditing' => 'Audyt poświadczeń',
        'network_diagnostics' => 'Diagnostyka sieci',
        'l2_active' => 'Aktywne testy warstwy 2',
        'responder' => 'Zatruwanie rozgłoszeń (Responder)',
        'wifi' => 'Sieci bezprzewodowe',
        'external_diagnostics' => 'Diagnostyka łącza zewnętrznego',
    ];

    /**
     * Module names from `module_status` that belong to no audit group, for the
     * reports summarised per module.
     *
     * @var array<string, string>
     */
    private const MODULES = [
        'nuclei_template_validation' => 'Walidacja szablonów nuclei',
        'cve_correlations' => 'Korelacja wersji z bazą CVE',
        'device_security_posture' => 'Ocena konfiguracji urządzeń',
        'host_liveness' => 'Dostępność urządzeń',
        'routing_evidence' => 'Analiza trasowania',
        'routing_topology' => 'Topologia sieci',
    ];

    /**
     * Which audit group a module belongs to, so a report that only has
     * `module_status` is summarised in the same groups as a newer one - and
     * the section notes and exposure checks find their group either way.
     *
     * @var array<string, string>
     */
    private const MODULE_GROUPS = [
        'nmap_primary' => 'port_scanning',
        'nmap_targeted' => 'port_scanning',
        'nmap_structured' => 'port_scanning',
        'vuln' => 'vulnerability_scanning',
        'nuclei' => 'nuclei',
        'nuclei_oast' => 'nuclei_oast',
        'smb' => 'smb',
        'loot' => 'smb',
        'ftp' => 'credential_auditing',
        'ssh' => 'credential_auditing',
        'ldap' => 'directory_services',
        'ad_enum' => 'directory_services',
        'ad_policy' => 'directory_services',
        'ics' => 'ics_ot',
        'fingerprints' => 'service_fingerprinting',
        'device_identity' => 'service_fingerprinting',
        'asset_inventory' => 'service_fingerprinting',
        'snmp' => 'snmp',
        'deep_databases' => 'databases',
        'compliance_ssl' => 'tls',
        'web_fuzz' => 'web_fuzzing',
        'responder' => 'responder',
        'network_discovery' => 'network_discovery',
    ];

    /** A result in one of these states never produced a verdict. */
    private const FAILED_STATUSES = ['error', 'failed', 'timeout', 'blocked', 'disconnected_during_audit'];

    /** Did not apply to this target at all - neither a result nor a gap. */
    private const IGNORED_STATUSES = ['skipped', 'not_applicable', 'disabled'];

    /** Ran and came back without anything to report. */
    private const CLEAN_STATUSES = ['ok', 'completed', 'clean'];

    /** @var array<string, string> */
    private const STATUS_PHRASES = [
        'failed' => 'test nie wykonał się',
        'error' => 'test zakończył się błędem',
        'timeout' => 'test przekroczył limit czasu',
        'blocked' => 'test zablokowany przez zakres lub politykę badania',
        'partial' => 'test wykonany częściowo',
        'disconnected_during_audit' => 'urządzenie zniknęło z sieci w trakcie badania',
    ];

    /**
     * How the run ended, in the probe's words, and what a reader has to be told
     * about it. A clean finish needs no sentence.
     *
     * @var array<string, string|null>
     */
    private const OUTCOMES = [
        'completed' => null,
        'completed_with_module_errors' => 'Badanie zostało ukończone, ale część testów nie wykonała się poprawnie.',
        'operator_stop' => 'Badanie zostało przerwane przez operatora przed ukończeniem - wyniki są niepełne.',
    ];

    /**
     * Facts for a stored report, read without decoding the whole document.
     *
     * @return array<string, mixed>
     */
    public static function forReport(Report $report): array
    {
        return self::from(ReportSections::of($report));
    }

    /**
     * @param  array<string, mixed>  $document  the report object; nuclei's findings may be any iterable
     * @return array<string, mixed>
     */
    public static function from(array $document): array
    {
        $coverage = self::coverage($document);
        $hosts = self::hosts($document);
        $services = self::services($hosts);
        $deepFindings = self::deepFindings($document);
        $ics = self::icsEndpoints($document);
        $exposure = self::exposure($document, $coverage['groups']);
        $diagnostics = self::diagnostics($document);

        return [
            'scan' => [
                'performed_at' => self::string($document, 'scan_time') ?? self::string($document, 'scan_started_at'),
                'orchestrator_ip' => self::string($document, 'orchestrator_ip'),
                'outcome' => $coverage['outcome'],
                'outcome_note' => $coverage['outcome_note'],
            ],
            'totals' => [
                'hosts_discovered' => max(count($hosts), self::int($document, 'discovered_hosts_count')),
                'hosts_reachable' => count(array_filter($hosts, fn (array $h): bool => $h['reachable'])),
                'hosts_with_open_ports' => count(array_filter($hosts, fn (array $h): bool => $h['open_ports'] !== [])),
                'open_ports' => count($services),
                'deep_findings' => count($deepFindings),
                'deep_findings_notable' => count(array_filter($deepFindings, fn (array $f): bool => $f['notable'])),
                'ics_endpoints' => count($ics),
                'exposure_findings' => array_sum(array_map(fn (array $m): int => count($m['findings']), $exposure)),
                'modules_failed' => count(array_filter($exposure, fn (array $m): bool => $m['failed'])),
            ],
            'coverage' => $coverage['groups'],
            'hosts' => $hosts,
            'services' => $services,
            'deep_findings' => $deepFindings,
            'ics_endpoints' => $ics,
            'fingerprints' => self::fingerprints($document),
            'exposure' => $exposure,
            'diagnostics' => $diagnostics,
        ] + self::ranked($document, $deepFindings, $services, $ics, $exposure, $diagnostics, $coverage);
    }

    /**
     * Which test groups ran, which did not, and on which devices - plus how the
     * run as a whole ended.
     *
     * The probe calls a group `failed` when any one of its paths failed, so the
     * status alone overstates: a port scan that worked on twenty hosts and timed
     * out on three is `failed`. The evidence paths say which hosts were affected
     * and whether anything in the group ran at all, and that is what is kept.
     *
     * Reports without `audit_group_status` fall back to `module_status`, with
     * each module folded into the group it belongs to, so one timed-out test on
     * forty hosts is one gap naming forty hosts rather than forty gaps.
     *
     * @param  array<string, mixed>  $document
     * @return array{groups: array<string, array<string, mixed>>, outcome: string|null, outcome_note: string|null}
     */
    private static function coverage(array $document): array
    {
        $groups = [];

        foreach (self::map($document, 'audit_group_status') as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $hosts = [];
            $ran = false;
            $skipped = false;

            foreach (is_array($entry['evidence'] ?? null) ? $entry['evidence'] : [] as $evidence) {
                $path = is_array($evidence) ? (string) ($evidence['path'] ?? '') : '';
                $status = is_array($evidence) ? mb_strtolower((string) ($evidence['status'] ?? '')) : '';

                // audit_plan.* and profile_semantics.* say a test was enabled,
                // not that it ran.
                if (! str_starts_with($path, 'module_status.') && ! str_starts_with($path, 'diagnostics.')) {
                    continue;
                }

                if (in_array($status, self::CLEAN_STATUSES, true)) {
                    $ran = true;
                } elseif (in_array($status, self::IGNORED_STATUSES, true)) {
                    $skipped = true;
                } elseif (self::isProblem($status) && ($ip = self::hostOfModule(substr($path, 14))) !== null) {
                    $hosts[$ip] = $status;
                }
            }

            $groups[(string) $key] = self::group((string) $key, mb_strtolower((string) ($entry['status'] ?? '')), $ran, $hosts, $skipped);
        }

        if ($groups === []) {
            $byGroup = [];

            foreach (self::map($document, 'module_status') as $key => $status) {
                $status = is_string($status) ? mb_strtolower($status) : '';
                $key = (string) $key;
                $ip = self::hostOfModule($key);
                $module = $ip !== null ? substr($key, strrpos($key, '.') + 1) : $key;

                // `host.<ip>` on its own summarises the host's modules, which are
                // listed separately; diagnostics have a section of their own that
                // already says when one did not run.
                if (($ip === null && str_starts_with($key, 'host.')) || str_starts_with($key, 'diagnostics.')) {
                    continue;
                }

                $group = self::MODULE_GROUPS[$module] ?? $module;
                $byGroup[$group] ??= ['ran' => false, 'worst' => null, 'hosts' => [], 'skipped' => false];

                if (in_array($status, self::CLEAN_STATUSES, true)) {
                    $byGroup[$group]['ran'] = true;
                } elseif (in_array($status, self::IGNORED_STATUSES, true)) {
                    $byGroup[$group]['skipped'] = true;
                } elseif (self::isProblem($status)) {
                    $byGroup[$group]['worst'] ??= $status;

                    if ($ip !== null) {
                        $byGroup[$group]['hosts'][$ip] = $status;
                    }
                }
            }

            foreach ($byGroup as $group => $state) {
                $status = $state['worst'] ?? (! $state['ran'] && $state['skipped'] ? 'skipped' : 'ok');
                $groups[$group] = self::group($group, $status, $state['ran'], $state['hosts'], $state['skipped']);
            }
        }

        $summary = self::map($document, 'report_summary');
        $outcome = is_string($summary['overall_result'] ?? null) ? $summary['overall_result'] : null;

        return [
            'groups' => $groups,
            'outcome' => $outcome,
            'outcome_note' => $outcome === null
                ? null
                : (array_key_exists($outcome, self::OUTCOMES) ? self::OUTCOMES[$outcome] : 'Badanie zakończyło się stanem: '.self::humanise($outcome).'.'),
        ];
    }

    /**
     * @param  array<string, string>  $hosts  address => status, for the hosts the group failed on
     * @return array<string, mixed>
     */
    private static function group(string $key, string $status, bool $ran, array $hosts, bool $skipped = false): array
    {
        uksort($hosts, strnatcmp(...));

        return [
            'label' => self::AUDIT_GROUPS[$key] ?? self::MODULES[$key] ?? self::humanise($key),
            'status' => $status,
            'problem' => self::isProblem($status),
            'ran' => $ran,
            // Every path said the test did not apply - no SMB service, nothing
            // to log in to. Neither a result nor a gap.
            'skipped' => $skipped && ! $ran && ! self::isProblem($status),
            'hosts' => array_keys($hosts),
            'phrase' => self::STATUS_PHRASES[$status] ?? null,
        ];
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
     * @param  array<string, mixed>  $document
     * @param  list<array<string, mixed>>  $deepFindings
     * @param  list<array<string, mixed>>  $services
     * @param  list<array<string, mixed>>  $ics
     * @param  array<string, array<string, mixed>>  $exposure
     * @param  list<array<string, mixed>>  $diagnostics
     * @param  array{groups: array<string, array<string, mixed>>, outcome: string|null, outcome_note: string|null}  $coverage
     * @return array<string, mixed>
     */
    private static function ranked(array $document, array $deepFindings, array $services, array $ics, array $exposure, array $diagnostics, array $coverage): array
    {
        $findings = [];
        $gaps = [];

        if ($coverage['outcome_note'] !== null) {
            $gaps[] = self::gap($coverage['outcome_note'], 'Przebieg badania');
        }

        // An address that changed owner mid-scan: the probe sets those results
        // aside rather than pin them on either device. We never show them as
        // findings - saying so is the honest coverage note. The IP-keyed data
        // the rest of this class reads describes the address's current owner.
        $unattributed = self::list($document, 'unattributed_identity_attempts');

        if ($unattributed !== []) {
            $gaps[] = self::gap(
                'Dla '.Polish::count(count($unattributed), 'urządzenia', 'urządzeń', 'urządzeń')
                    .' tożsamość zmieniła się w trakcie badania; ich ustaleń nie przypisano do żadnego urządzenia',
                'Tożsamość urządzeń',
            );
        }

        $groups = $coverage['groups'];
        $stopped = $groups !== [] && array_filter($groups, fn (array $g): bool => ! $g['problem'] || $g['ran']) === [];

        // A run stopped before anything finished would otherwise list every
        // test group on its own line, each saying the same thing.
        if ($stopped) {
            $gaps[] = self::gap('Żaden z '.count($groups).' testów nie został ukończony: '
                .implode(', ', array_column($groups, 'label')), 'Przebieg badania');
            $groups = [];
        }

        foreach ($groups as $group) {
            if (! $group['problem']) {
                continue;
            }

            $title = match (true) {
                count($group['hosts']) === 1 => $group['label'].': test nie powiódł się na tym urządzeniu',
                $group['hosts'] !== [] => $group['label'].': test nie powiódł się na '.self::devices(count($group['hosts'])),
                $group['phrase'] !== null => $group['label'].': '.$group['phrase'],
                default => $group['label'].': test nie wykonał się poprawnie',
            };

            $gaps[] = self::gap($title, 'Przebieg badania', $group['hosts']);
        }

        $cveGroups = self::cveGroups($document);
        $correlated = array_flip(array_column($cveGroups, 'ip'));

        foreach ($deepFindings as $finding) {
            if (! $finding['notable']) {
                continue;
            }

            // The probe's CVE correlation is built from this very script's
            // output; where it exists, the same CVEs would otherwise be listed
            // twice under two titles.
            if ($finding['name'] === 'vulners' && isset($correlated[$finding['ip']])) {
                continue;
            }

            $grade = Severity::ofScript($finding['output']);

            // The probe already decided this script found a vulnerability; if
            // the bounded excerpt was cut before nmap's own State: line, the
            // grade reads INFO and would understate it. Floor it to high and
            // unconfirmed - never below what the probe asserts.
            if (($finding['state'] ?? null) === 'vulnerable' && $grade['level'] === Severity::INFO) {
                $grade = ['level' => Severity::HIGH, 'confirmed' => false, 'inconclusive' => false];
            }

            if ($grade['inconclusive'] && $grade['level'] === Severity::INFO) {
                $gaps[] = self::gap('Test '.$finding['name'].' nie uzyskał odpowiedzi', 'Pogłębione testy', [$finding['ip']]);

                continue;
            }

            // vulners prints a CPE or a raw CVE/URL line first, which titleOf
            // would pick up; its CVEs are already listed beside the finding.
            $title = $finding['name'] === 'vulners'
                ? 'Znane podatności usługi (dopasowanie po wersji)'
                : Severity::titleOf($finding['output'], $finding['name']);

            $findings[] = self::finding(
                $grade['level'],
                $title,
                $finding['ip'],
                $finding['name'].(($finding['port'] ?? null) !== null ? ' · port '.$finding['port'] : ''),
                'Pogłębione testy',
                $finding['cves'] ?? Severity::cvesIn($finding['output']),
                $grade['confirmed'],
                $grade['inconclusive'] ? 'Test nie zdołał potwierdzić ustalenia.' : null,
            );
        }

        foreach (self::map($document, 'device_security_posture') as $ip => $posture) {
            foreach (is_array($posture) && is_array($posture['findings'] ?? null) ? $posture['findings'] : [] as $finding) {
                $level = is_array($finding) ? Severity::ofProbeWord($finding['severity'] ?? null) : Severity::INFO;
                $state = is_array($finding) ? self::findingState($finding) : null;

                if ($level === Severity::INFO || self::isDroppedState($state)) {
                    continue;
                }

                $ports = array_filter((array) ($finding['evidence']['tcp_ports'] ?? []), is_scalar(...));

                $findings[] = self::finding(
                    $level,
                    is_string($finding['title'] ?? null) ? $finding['title'] : 'Ustalenie dotyczące konfiguracji urządzenia',
                    (string) $ip,
                    $ports !== [] ? 'port '.implode(', ', $ports) : null,
                    'Konfiguracja urządzeń',
                    [],
                    match ($state) {
                        'confirmed' => true,
                        null => ($finding['evidence']['classification'] ?? null) !== 'candidate',
                        default => false,
                    },
                    is_string($finding['remediation'] ?? null) ? $finding['remediation'] : null,
                );
            }
        }

        foreach ($cveGroups as $group) {
            $level = Severity::ofCvss($group['score'], $group['confirmed']);

            if ($level === Severity::INFO) {
                continue;
            }

            $findings[] = self::finding(
                $level,
                'Znane podatności w '.$group['software'].' ('.count($group['cves']).' CVE, najwyższy CVSS '.$group['score'].')',
                $group['ip'],
                $group['port'] !== null ? 'port '.$group['port'] : null,
                'Korelacja wersji z bazą CVE',
                $group['cves'],
                $group['confirmed'],
                $group['confirmed'] ? null : 'Dopasowanie po numerze wersji - wymaga potwierdzenia, czy podatny kod jest faktycznie używany.',
            );
        }

        foreach ($services as $service) {
            $exposed = is_int($service['port']) ? Severity::ofPort($service['port']) : null;

            if ($exposed === null) {
                continue;
            }

            $findings[] = self::finding(
                $exposed['level'],
                $exposed['name'].' dostępny w sieci',
                $service['ip'],
                $service['port'].'/'.$service['transport'],
                'Usługi',
                [],
                true,
                $exposed['why'],
            );
        }

        foreach ($ics as $endpoint) {
            $level = Severity::ofProbeWord($endpoint['severity'] ?? null);

            if ($level === Severity::INFO) {
                continue;
            }

            $findings[] = self::finding(
                $level,
                (is_string($endpoint['protocol'] ?? null) ? $endpoint['protocol'] : 'Protokół przemysłowy').' dostępny w sieci',
                $endpoint['ip'],
                ($endpoint['port'] ?? '?').'/'.($endpoint['transport'] ?? '?'),
                'ICS/OT',
                [],
                true,
                null,
            );
        }

        foreach ($exposure as $module) {
            if (! $module['present']) {
                $gaps[] = self::gap($module['label'].' — sonda nie przekazała wyniku', 'Ekspozycja');

                continue;
            }

            if ($module['failed']) {
                // Its audit group already put this hole on the list, with the
                // devices it covers.
                if (! ($coverage['groups'][$module['group']]['problem'] ?? false)) {
                    $gaps[] = self::gap($module['label'].' — test nie wykonał się', 'Ekspozycja');
                }

                continue;
            }

            foreach ($module['findings'] as $finding) {
                $level = Severity::ofProbeWord($finding['severity'] ?? null);

                if ($level === Severity::INFO) {
                    continue;
                }

                $title = $finding['name'] ?? $finding['assessment'] ?? $finding['type'] ?? $module['label'];

                $findings[] = self::finding(
                    $level,
                    is_scalar($title) ? (string) $title : $module['label'],
                    isset($finding['ip']) && is_scalar($finding['ip']) ? (string) $finding['ip'] : null,
                    isset($finding['template'], $finding['matches'])
                        ? $finding['template'].' ('.Polish::count($finding['matches'], 'trafienie', 'trafienia', 'trafień').')'
                        : null,
                    $module['label'],
                    [],
                    // A nuclei template match is an observation the contract is
                    // explicit must not be presented as a confirmed finding.
                    ! isset($finding['template']),
                    null,
                );
            }
        }

        foreach ($diagnostics as $diagnostic) {
            if ($diagnostic['error'] !== null) {
                $gaps[] = self::gap($diagnostic['label'].' — test nie wykonał się', 'Diagnostyka');
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
                        $gaps[] = self::gap($diagnostic['label'].' — '.$field['value'], 'Diagnostyka');
                    }

                    continue;
                }

                $findings[] = self::finding(
                    $field['level'],
                    $field['label'] !== null ? $field['label'].': '.$field['value'] : $field['value'],
                    null,
                    null,
                    $diagnostic['label'],
                    [],
                    true,
                    null,
                );
            }
        }

        $gaps = self::mergeGaps($gaps);

        // Stable: within a level, findings keep the order the sections gave them.
        $order = array_keys($findings);
        array_multisort(array_map(fn (array $f): int => Severity::rank($f['level']), $findings), $order, $findings);

        $counts = array_fill_keys(Severity::ORDER, 0);

        foreach ($findings as $finding) {
            $counts[$finding['level']]++;
        }

        $actionable = count(array_filter($findings, fn (array $f): bool => Severity::actionable($f['level'])));

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
     * CVE correlations folded per piece of software on a host. The probe lists
     * every CVE on its own - forty-odd lines for one old dnsmasq - and the fix is
     * the same for all of them: update that software. One finding per software,
     * carrying the full CVE list and graded by its worst score.
     *
     * @param  array<string, mixed>  $document
     * @return list<array{ip: string, port: int|string|null, software: string, cves: list<string>, score: float, confirmed: bool}>
     */
    private static function cveGroups(array $document): array
    {
        $groups = [];

        foreach (self::map($document, 'cve_correlations') as $ip => $correlation) {
            foreach (is_array($correlation) && is_array($correlation['findings'] ?? null) ? $correlation['findings'] : [] as $cve) {
                if (! is_array($cve) || ! is_string($cve['cve_id'] ?? null) || self::isDroppedState(self::findingState($cve))) {
                    continue;
                }

                $service = is_array($cve['affected_service'] ?? null) ? $cve['affected_service'] : [];
                $software = trim(implode(' ', array_filter([
                    is_string($service['product'] ?? null) ? $service['product'] : null,
                    is_string($service['version'] ?? null) ? $service['version'] : null,
                ]))) ?: 'oprogramowaniu nieznanej wersji';
                $port = is_scalar($cve['port'] ?? null) ? $cve['port'] : null;
                $key = $ip.'|'.$port.'|'.$software;

                $groups[$key] ??= ['ip' => (string) $ip, 'port' => $port, 'software' => $software, 'cves' => [], 'score' => 0.0, 'confirmed' => false];
                $groups[$key]['cves'][] = mb_strtoupper($cve['cve_id']);
                $groups[$key]['score'] = max($groups[$key]['score'], is_numeric($cve['cvss_score_reported'] ?? null) ? (float) $cve['cvss_score_reported'] : 0.0);
                $groups[$key]['confirmed'] = $groups[$key]['confirmed']
                    || self::findingState($cve) === 'confirmed'
                    || (self::findingState($cve) === null && ($cve['verification_required'] ?? true) === false);
            }
        }

        foreach ($groups as &$group) {
            $group['cves'] = array_values(array_unique($group['cves']));
        }

        return array_values($groups);
    }

    /**
     * Splits each exposure check into what it found, what it checked and found
     * clean, and where it broke.
     *
     * This distinction is the whole point. A probe run where nuclei never
     * started still returns one entry per host - each carrying an error - and
     * counting those as findings would put "5 trafień skanowania szablonami"
     * into a client's report when the scanner in fact never ran. A module that
     * failed is reported as failed, which is both honest and the thing somebody
     * needs to know to go fix the probe.
     *
     * An empty result list is only "clean" when the module's audit group says
     * the test ran. Schema 3 writes `[]` for a test that was stopped before it
     * started, and reading that as "nothing found" is the one mistake this
     * document must not make. The oldest reports carry no groups at all; for
     * those, empty still means the probe found nothing.
     *
     * @param  array<string, mixed>  $document
     * @param  array<string, array<string, mixed>>  $groups
     * @return array<string, array<string, mixed>>
     */
    private static function exposure(array $document, array $groups): array
    {
        $modules = [];

        foreach (self::EXPOSURE_MODULES as $key => ['label' => $label, 'group' => $groupKey]) {
            $present = array_key_exists($key, $document);
            $group = $groupKey !== null ? ($groups[$groupKey] ?? null) : null;

            $sorted = ['findings' => [], 'errors' => [], 'checked' => 0];

            if ($present && $key === 'nuclei_results' && is_array($document[$key]) && isset($document[$key]['representation'])) {
                $sorted = self::nuclei($document[$key]);
            } elseif ($present) {
                $quiet = 0;

                foreach (self::results($document[$key]) as $result) {
                    match (self::classify($result)) {
                        'error' => $sorted['errors'][] = self::compact($result),
                        'finding' => $sorted['findings'][] = self::compact($result),
                        'checked' => $sorted['checked']++,
                        'quiet' => $quiet++,
                        default => null,
                    };
                }

                // "Nothing observed" from a tool that also reported failing is
                // the tool's placeholder, not an observation.
                $sorted['checked'] += $sorted['errors'] === [] ? $quiet : 0;
            }

            $evidence = $sorted['findings'] !== [] || $sorted['checked'] > 0;
            $notApplicable = $present && ! $evidence && $sorted['errors'] === [] && ($group['skipped'] ?? false);
            $unverified = $groups !== [] && ! ($group['ran'] ?? false) && ! $notApplicable;
            $failed = $present && ! $evidence && ($sorted['errors'] !== [] || $unverified);

            $modules[$key] = [
                'label' => $label,
                'group' => $groupKey,
                'present' => $present,
                'findings' => $sorted['findings'],
                'errors' => $sorted['errors'],
                'checked' => $sorted['checked'],
                'observations' => $sorted['observations'] ?? 0,
                'failed' => $failed,
                'not_applicable' => $notApplicable,
                // Ran somewhere but not everywhere: a clean result covers only
                // the part that ran, and the page has to say so.
                'partial' => $present && ! $failed && ($sorted['errors'] !== [] || ($group['problem'] ?? false)),
                'missed_hosts' => $group['hosts'] ?? [],
            ];
        }

        return $modules;
    }

    /**
     * Nuclei in the probe's normalised form: each finding is a tuple of a
     * template index, a target index and the match itself, and a busy network
     * yields tens of thousands of them - 13 549 in one real report, every one an
     * informational match. They are folded into one entry per template and host,
     * counted, so the document lists what was found rather than every time it
     * was found. `findings` may be a generator; it is read exactly once.
     *
     * @param  array<string, mixed>  $section
     * @return array{findings: list<array<string, mixed>>, errors: list<array<string, mixed>>, checked: int}
     */
    private static function nuclei(array $section): array
    {
        $templates = is_array($section['templates'] ?? null) ? $section['templates'] : [];
        $targets = is_array($section['targets'] ?? null) ? $section['targets'] : [];
        $folded = [];

        foreach (is_iterable($section['findings'] ?? null) ? $section['findings'] : [] as $tuple) {
            if (! is_array($tuple) || ! is_int($tuple[0] ?? null)) {
                continue;
            }

            $template = is_array($templates[$tuple[0]] ?? null) ? $templates[$tuple[0]] : [];
            $target = is_array($targets[$tuple[1] ?? -1] ?? null) ? $targets[$tuple[1]] : [];
            $fields = is_array($target['fields'] ?? null) ? $target['fields'] : [];
            // Attribute to the address, not the URL: `host` can be a scheme://…
            // form, so the IP is preferred when the probe supplies one.
            $host = (string) ($fields['ip'] ?? $fields['host'] ?? $target['attributed_target'] ?? '?');
            $id = (string) ($template['template-id'] ?? 'szablon '.$tuple[0]);

            $folded[$id.'|'.$host] ??= [
                'template' => $id,
                'name' => (string) ($template['info']['name'] ?? $id),
                'severity' => (string) ($template['info']['severity'] ?? 'info'),
                'ip' => $host,
                'matches' => 0,
            ];
            $folded[$id.'|'.$host]['matches']++;
        }

        $findings = array_values($folded);
        usort($findings, fn (array $a, array $b): int => [Severity::rank(Severity::ofProbeWord($a['severity'])), $b['matches']]
            <=> [Severity::rank(Severity::ofProbeWord($b['severity'])), $a['matches']]);

        $errors = [];
        $checked = 0;

        foreach (is_array($section['scans'] ?? null) ? $section['scans'] : [] as $ip => $scan) {
            if (str_starts_with((string) $ip, '_') || ! is_array($scan)) {
                continue;
            }

            $runs = [['host', $scan['host'] ?? null]];

            foreach (is_array($scan['web'] ?? null) ? $scan['web'] : [] as $web) {
                $runs[] = ['web', is_array($web) ? ($web['result'] ?? null) : null];
            }

            foreach ($runs as [$mode, $run]) {
                $status = is_array($run) && is_string($run['status'] ?? null) ? mb_strtolower($run['status']) : null;

                if ($status === null) {
                    continue;
                }

                if (in_array($status, self::FAILED_STATUSES, true)) {
                    $errors[] = ['ip' => (string) $ip, 'skan' => $mode, 'status' => $status];
                } elseif (in_array($status, self::CLEAN_STATUSES, true)) {
                    $checked++;
                }
            }
        }

        return ['findings' => $findings, 'errors' => $errors, 'checked' => $checked, 'observations' => self::nucleiObservations($section)];
    }

    /**
     * How many technical extractor hits (clock times, hex colours and the like)
     * the probe grouped away from the findings. The contract is explicit they
     * are counted context, not vulnerabilities, so they are tallied and shown
     * as a note - never as findings.
     *
     * @param  array<string, mixed>  $section
     */
    private static function nucleiObservations(array $section): int
    {
        $total = 0;

        foreach (is_array($section['grouped_observations'] ?? null) ? $section['grouped_observations'] : [] as $group) {
            $total += is_array($group) && is_numeric($group['occurrence_count'] ?? null) ? (int) $group['occurrence_count'] : 0;
        }

        $location = is_array($section['security_location_observations'] ?? null) ? $section['security_location_observations'] : [];
        $columns = is_array($location['columns'] ?? null) ? array_values($location['columns']) : [];
        $index = array_search('occurrence_count', $columns, true);

        foreach (is_array($location['rows'] ?? null) ? $location['rows'] : [] as $row) {
            $total += is_array($row) && $index !== false && is_numeric($row[$index] ?? null) ? (int) $row[$index] : 0;
        }

        return $total;
    }

    /**
     * What one module result is: an error, a finding, a check that came back
     * clean, a "nothing observed" note, or something that did not apply.
     *
     * @param  array<string, mixed>  $result
     */
    private static function classify(array $result): string
    {
        $status = is_string($result['status'] ?? null) ? mb_strtolower($result['status']) : null;
        $error = $result['error'] ?? null;

        // RESPONDER_START_FAILED and its kind carry a severity, but describe the
        // tool breaking, not the network.
        $failedTool = is_string($result['type'] ?? null) && str_ends_with(mb_strtoupper($result['type']), '_FAILED');

        if (in_array($status, self::FAILED_STATUSES, true) || (is_string($error) && $error !== '') || $failedTool) {
            return 'error';
        }

        if (in_array($status, self::IGNORED_STATUSES, true)) {
            return 'ignored';
        }

        if (($result['finding_type'] ?? null) === 'no_observation') {
            return 'quiet';
        }

        $severity = $result['severity'] ?? $result['info']['severity'] ?? null;

        if (in_array($status, self::CLEAN_STATUSES, true) && $severity === null) {
            return 'checked';
        }

        return 'finding';
    }

    /**
     * Walks a module's value down to its leaves. A leaf is an associative array
     * that either carries a status/error field or holds nothing but scalars;
     * anything else is a container and gets descended into. Written this way so
     * a module keyed by host, wrapped in sub-checks, or handed over as a flat
     * list all come back as the same list of results.
     *
     * A leaf without an address of its own inherits one from the key it sat
     * under (`192.168.0.2` or `192.168.0.2_ssh`), so every result can still be
     * attributed to a device.
     *
     * @return list<array<string, mixed>>
     */
    private static function results(mixed $value, ?string $ip = null): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        if (! array_is_list($value) && self::isLeaf($value)) {
            return [$ip !== null && ! isset($value['ip']) ? $value + ['ip' => $ip] : $value];
        }

        $results = [];

        // A list of plain values is a list of results; a scalar beside objects
        // - the container's own fields, or an interface name the probe dropped
        // into a list of observations - labels the container and is not one.
        $scalarsAreResults = array_is_list($value) && array_filter($value, is_array(...)) === [];

        foreach ($value as $key => $entry) {
            if (is_scalar($entry)) {
                if ($scalarsAreResults) {
                    $results[] = ['value' => $entry];
                }

                continue;
            }

            $inherited = is_string($key) && preg_match('/^([0-9a-f.:]+?)(?:_[a-z].*)?$/i', $key, $match) && (filter_var($match[1], FILTER_VALIDATE_IP) !== false)
                ? $match[1]
                : $ip;

            foreach (self::results($entry, $inherited) as $result) {
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
     * Discovered addresses joined with what the port scan saw on each. A host
     * that answered discovery but not the scan is kept and marked unreachable,
     * because that gap is itself worth reporting; a host whose scan failed is
     * marked as such, which is a different thing again.
     *
     * Schema 3 supplies the scan as structured data and an asset inventory
     * beside it; both are preferred over parsing nmap's console text.
     *
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private static function hosts(array $document): array
    {
        $scans = self::map($document, 'nmap_results');
        $structured = self::map($document, 'nmap_structured');
        $inventory = self::map($document, 'asset_inventory');

        $discovered = array_map(strval(...), array_filter(self::list($document, 'hosts'), is_string(...)));

        // An address that only appears in a scan map still belongs in the table.
        foreach ([...array_keys($scans), ...array_keys($structured)] as $ip) {
            $discovered[] = (string) $ip;
        }

        $discovered = array_values(array_unique($discovered));
        usort($discovered, strnatcmp(...));

        return array_map(function (string $ip) use ($scans, $structured, $inventory): array {
            $asset = is_array($inventory[$ip] ?? null) ? $inventory[$ip] : [];
            $scan = is_array($structured[$ip] ?? null) ? $structured[$ip] : null;
            $output = is_string($scans[$ip] ?? null) ? $scans[$ip] : '';
            $mac = $output !== '' ? NmapOutput::macAddress($output) : null;

            $host = [
                'ip' => $ip,
                'mac' => self::known($asset['mac_address'] ?? null) ?? $mac['mac'] ?? null,
                'vendor' => self::known($asset['manufacturer'] ?? null) ?? self::known($asset['mac_vendor'] ?? null) ?? $mac['vendor'] ?? null,
                'model' => self::known($asset['model'] ?? null) ?? self::known($asset['model_candidates'][0] ?? null),
                'os' => self::known($asset['os']['name'] ?? null),
                'scanned' => $output !== '' || $scan !== null,
                'reachable' => $output !== '' && NmapOutput::hostIsUp($output),
                'scan_failed' => null,
                'open_ports' => $output !== '' ? NmapOutput::openPorts($output) : [],
            ];

            if ($scan !== null) {
                $status = is_string($scan['status'] ?? null) ? mb_strtolower($scan['status']) : '';
                $ports = self::structuredPorts($scan);

                $host['reachable'] = $ports !== [] || $status === 'ok' || $host['reachable'];
                $host['open_ports'] = $ports !== [] ? $ports : $host['open_ports'];
                $host['scan_failed'] = in_array($status, self::FAILED_STATUSES, true) ? (self::STATUS_PHRASES[$status] ?? $status) : null;
            }

            return $host;
        }, $discovered);
    }

    /**
     * @param  array<string, mixed>  $scan
     * @return list<array{port: int|string, transport: string, service: string, version: string|null, state: string}>
     */
    private static function structuredPorts(array $scan): array
    {
        $ports = [];

        foreach (is_array($scan['ports'] ?? null) ? $scan['ports'] : [] as $port) {
            $state = is_array($port) && is_string($port['state'] ?? null) ? $port['state'] : '';

            if (! str_starts_with($state, 'open')) {
                continue;
            }

            $ports[] = [
                'port' => is_numeric($port['port'] ?? null) ? (int) $port['port'] : (string) ($port['port'] ?? '?'),
                'transport' => mb_strtoupper(is_string($port['protocol'] ?? null) ? $port['protocol'] : 'tcp'),
                'service' => is_string($port['name'] ?? null) && $port['name'] !== '' ? $port['name'] : 'nieznana',
                'version' => trim(implode(' ', array_filter([
                    is_string($port['product'] ?? null) ? $port['product'] : null,
                    is_string($port['version'] ?? null) ? $port['version'] : null,
                ]))) ?: null,
                'state' => $state,
            ];
        }

        return $ports;
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
     * Two shapes. Up to schema 2 the probe sent nmap's console text per host in
     * `deep_vulnerabilities`, parsed here by NmapOutput. Schema 3 sends one
     * entry per script in `nse_script_evidence`, already carrying a bounded
     * `evidence_excerpt`, the script's own `state` and the CVEs it cited; the
     * two sources do not overlap on any stored report. The entry's `state` is
     * the probe's verdict and wins over the excerpt: `not_vulnerable` is clean
     * even when the excerpt quotes a CVE (`broadcast-avahi-dos` does), because
     * the quote is what the script looks for, not what it found.
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
                    'state' => null,
                    'cves' => null,
                ];
            }
        }

        $seen = [];

        foreach (self::map($document, 'nse_script_evidence') as $ip => $entries) {
            foreach (is_array($entries) ? $entries : [] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $script = (string) ($entry['script_id'] ?? 'skrypt NSE');
                $port = is_scalar($entry['port'] ?? null) ? (string) $entry['port'] : null;

                // The same script on the same port can be listed twice; two
                // ports (443 and 4443) are different endpoints and both stay.
                $key = $ip.'|'.$script.'|'.$port.'|'.($entry['evidence_sha256'] ?? '');

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;

                $excerpt = is_string($entry['evidence_excerpt'] ?? null) && $entry['evidence_excerpt'] !== ''
                    ? $entry['evidence_excerpt']
                    : implode("\n", array_filter((array) ($entry['security_signals'] ?? []), is_string(...)));
                // The excerpt is raw NSE console text; parsing it strips the
                // "| name:" prefixes so titleOf() reads the real first line.
                $parsed = NmapOutput::scripts($excerpt);
                $output = $parsed !== [] ? $parsed[0]['output'] : trim(preg_replace('/^\|_?\s?/m', '', $excerpt) ?? '');
                $state = is_string($entry['state'] ?? null) ? mb_strtolower($entry['state']) : null;
                $cves = array_values(array_unique(array_map(
                    mb_strtoupper(...),
                    array_filter((array) ($entry['cve_ids'] ?? []), is_string(...)),
                )));

                $findings[] = [
                    'ip' => (string) $ip,
                    'name' => $script,
                    'port' => $port,
                    'output' => $output,
                    'notable' => self::nseNotable($state, $output, $cves),
                    'state' => $state,
                    'cves' => $cves,
                ];
            }
        }

        usort($findings, fn (array $a, array $b): int => [$b['notable'], $a['ip']] <=> [$a['notable'], $b['ip']]);

        return $findings;
    }

    /**
     * Whether a schema-3 NSE entry is worth attention. The probe's `state` is
     * authoritative: `vulnerable` always, `not_vulnerable` never. An `observed`
     * script is only notable when it cited a CVE or its excerpt grades above
     * informational - an http-title or server-header stays an observation.
     *
     * @param  list<string>  $cves
     */
    private static function nseNotable(?string $state, string $output, array $cves): bool
    {
        return match ($state) {
            'not_vulnerable' => false,
            'vulnerable' => true,
            default => $cves !== [] || Severity::ofScript($output)['level'] !== Severity::INFO,
        };
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
        return self::perHost($document, 'ics_ot_risks');
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private static function fingerprints(array $document): array
    {
        return self::perHost($document, 'service_fingerprints');
    }

    /**
     * Entries kept per host (`{ip: [entry, …]}`), flattened with the address
     * added to each.
     *
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    private static function perHost(array $document, string $key): array
    {
        $entries = [];

        foreach (self::map($document, $key) as $ip => $list) {
            foreach (is_array($list) ? $list : [] as $entry) {
                if (is_array($entry)) {
                    $entries[] = $entry + ['ip' => (string) $ip];
                }
            }
        }

        return $entries;
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
     * A module result reduced to what is shown: its scalar fields, long text
     * clipped, the severity lifted out of nested `info`. The facts are stored
     * with every narrative, and one nuclei error alone once carried 527 KB of
     * console output that no page or prompt shows more than a line of.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private static function compact(array $result): array
    {
        $kept = [];

        foreach ($result as $key => $value) {
            if (is_string($value)) {
                $kept[$key] = mb_strlen($value) > 600 ? mb_substr($value, 0, 600).' […]' : $value;
            } elseif (is_scalar($value) || $value === null) {
                $kept[$key] = $value;
            }
        }

        if (! isset($kept['severity']) && is_scalar($result['info']['severity'] ?? null)) {
            $kept['severity'] = $result['info']['severity'];
        }

        return $kept;
    }

    /**
     * Gaps with the same title from the same section are one hole on several
     * devices: one line, every address kept. The same hole reported by two
     * sections - a module and the group it belongs to - is already avoided
     * where the module's gap is raised.
     *
     * @param  list<array{title: string, ip: string|null, hosts: list<string>, source: string}>  $gaps
     * @return list<array{title: string, ip: string|null, hosts: list<string>, source: string}>
     */
    private static function mergeGaps(array $gaps): array
    {
        $merged = [];

        foreach ($gaps as $gap) {
            $key = $gap['source'].'|'.$gap['title'];
            $hosts = $gap['ip'] !== null ? [$gap['ip']] : $gap['hosts'];

            $merged[$key] ??= ['title' => $gap['title'], 'source' => $gap['source'], 'hosts' => []];
            $merged[$key]['hosts'] = array_values(array_unique([...$merged[$key]['hosts'], ...$hosts]));
        }

        return array_values(array_map(fn (array $gap): array => self::gap($gap['title'], $gap['source'], $gap['hosts']), $merged));
    }

    /**
     * @param  list<string>  $hosts
     * @return array{title: string, ip: string|null, hosts: list<string>, source: string}
     */
    private static function gap(string $title, string $source, array $hosts = []): array
    {
        return [
            'title' => $title,
            'ip' => count($hosts) === 1 ? $hosts[0] : null,
            'hosts' => count($hosts) === 1 ? [] : $hosts,
            'source' => $source,
        ];
    }

    /**
     * @param  list<string>  $cves
     * @return array<string, mixed>
     */
    private static function finding(string $level, string $title, ?string $ip, ?string $where, string $source, array $cves, bool $confirmed, ?string $note): array
    {
        return [
            'level' => $level,
            'title' => $title,
            'ip' => $ip,
            'where' => $where,
            'source' => $source,
            'cves' => $cves,
            'confirmed' => $confirmed,
            'note' => $note,
        ];
    }

    private static function isProblem(string $status): bool
    {
        return in_array($status, self::FAILED_STATUSES, true) || $status === 'partial';
    }

    /**
     * The probe's verification verdict for a single finding, when it supplies
     * one. Schema-3 grouped reports carry `finding_state`; older reports do
     * not, and callers fall back to their own signals (CVSS confirmation,
     * evidence classification) when this is null.
     *
     * @param  array<string, mixed>  $finding
     */
    private static function findingState(array $finding): ?string
    {
        $state = $finding['finding_state'] ?? $finding['verification']['finding_state'] ?? null;

        return is_string($state) ? mb_strtolower($state) : null;
    }

    /**
     * A finding the probe cleared or ruled irrelevant never reaches the
     * document: a false positive would frighten a client over nothing, and a
     * not-applicable check is not a result.
     */
    private static function isDroppedState(?string $state): bool
    {
        return in_array($state, ['false_positive', 'not_applicable'], true);
    }

    /**
     * The address in a module path such as `host.192.168.0.1.nmap_primary`, or
     * null for a path that does not name a host's module.
     */
    private static function hostOfModule(string $path): ?string
    {
        if (! preg_match('/^host\.(.+)\.([a-z][a-z0-9_]*)$/i', $path, $match)) {
            return null;
        }

        return filter_var($match[1], FILTER_VALIDATE_IP) !== false ? $match[1] : null;
    }

    private static function devices(int $count): string
    {
        return $count.' '.($count === 1 ? 'urządzeniu' : 'urządzeniach');
    }

    private static function humanise(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * A value the probe filled in, or null for an empty or "(Unknown…)" one.
     */
    private static function known(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' && ! str_starts_with(trim($value), '(Unknown') ? trim($value) : null;
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
     */
    private static function int(array $document, string $key): int
    {
        $value = $document[$key] ?? null;

        return is_int($value) ? $value : 0;
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
