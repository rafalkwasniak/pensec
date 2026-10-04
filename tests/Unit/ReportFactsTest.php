<?php

namespace Tests\Unit;

use App\Services\ReportFacts;
use App\Support\NmapOutput;
use App\Support\Severity;
use App\Support\TsharkEndpoints;
use PHPUnit\Framework\TestCase;

/**
 * These cover the half of a PDF the model is not allowed to touch. If anything
 * here drifts, a generated report starts stating things the scan never found -
 * which is the one failure this whole design exists to prevent.
 */
class ReportFactsTest extends TestCase
{
    private const NMAP = <<<'TEXT'
    Starting Nmap 7.95 ( https://nmap.org ) at 2026-08-16 13:39 CEST
    Nmap scan report for 192.168.0.1
    Host is up (0.00046s latency).

    PORT     STATE  SERVICE      VERSION
    21/tcp   closed ftp
    80/tcp   open   http         GoAhead WebServer
    1741/udp open|filtered codesys
    MAC Address: 50:0F:F5:97:CE:48 (Tenda Technology,Ltd.Dongguan branch)

    Nmap done: 1 IP address (1 host up) scanned in 9.64 seconds
    TEXT;

    public function test_it_reads_the_port_table(): void
    {
        $ports = NmapOutput::ports(self::NMAP);

        $this->assertCount(3, $ports);
        $this->assertSame(80, $ports[1]['port']);
        $this->assertSame('http', $ports[1]['service']);
        $this->assertSame('GoAhead WebServer', $ports[1]['version']);
        $this->assertNull($ports[0]['version']);
    }

    public function test_open_ports_include_the_ones_nmap_could_not_rule_out(): void
    {
        $open = array_column(NmapOutput::openPorts(self::NMAP), 'port');

        // 21 is closed; 1741 is open|filtered and must not be treated as closed.
        $this->assertSame([80, 1741], $open);
    }

    public function test_it_reads_the_hardware_address_and_vendor(): void
    {
        $this->assertSame(
            ['mac' => '50:0F:F5:97:CE:48', 'vendor' => 'Tenda Technology,Ltd.Dongguan branch'],
            NmapOutput::macAddress(self::NMAP),
        );
    }

    public function test_an_unknown_vendor_is_not_reported_as_a_vendor(): void
    {
        $output = "MAC Address: 32:AB:BA:A0:ED:CE (Unknown)\n";

        $this->assertNull(NmapOutput::macAddress($output)['vendor']);
    }

    public function test_it_reads_nse_script_blocks_including_continuation_lines(): void
    {
        $output = <<<'TEXT'
        80/tcp open http
        | ssl-poodle:
        |   VULNERABLE:
        |   IDs: CVE:CVE-2014-3566
        |_  Disclosure date: 2014-10-14
        |_http-server-header: GoAhead-Webs
        TEXT;

        $scripts = NmapOutput::scripts($output);

        $this->assertCount(2, $scripts);
        $this->assertSame('ssl-poodle', $scripts[0]['name']);
        $this->assertStringContainsString('CVE-2014-3566', $scripts[0]['output']);
        $this->assertStringContainsString('2014-10-14', $scripts[0]['output']);
        $this->assertSame('GoAhead-Webs', $scripts[1]['output']);
    }

    public function test_a_host_that_answered_discovery_but_not_the_scan_is_kept_and_marked(): void
    {
        $facts = ReportFacts::from([
            'hosts' => ['192.168.0.1', '192.168.0.9'],
            'nmap_results' => [
                '192.168.0.1' => self::NMAP,
                '192.168.0.9' => "Starting Nmap 7.95\nNmap done: 1 IP address (0 hosts up) scanned in 3.54 seconds",
            ],
        ]);

        $this->assertSame(2, $facts['totals']['hosts_discovered']);
        $this->assertSame(1, $facts['totals']['hosts_reachable']);

        $silent = collect($facts['hosts'])->firstWhere('ip', '192.168.0.9');

        $this->assertTrue($silent['scanned']);
        $this->assertFalse($silent['reachable']);
    }

    public function test_clean_script_output_is_counted_but_not_flagged(): void
    {
        $facts = ReportFacts::from([
            'deep_vulnerabilities' => [
                '192.168.0.1' => implode("\n", [
                    "|_http-csrf: Couldn't find any CSRF vulnerabilities.",
                    '|_http-fileupload-exploiter: Could not find a file-type field.',
                    '|_ssl-poodle: VULNERABLE: SSL POODLE information leak',
                ]),
            ],
        ]);

        $this->assertSame(3, $facts['totals']['deep_findings']);
        $this->assertSame(1, $facts['totals']['deep_findings_notable']);
    }

    /**
     * The bug this guards against: nuclei returns one entry per host even when
     * it never ran, and counting those as findings put "5 trafień skanowania
     * szablonami" into a report where the scanner produced nothing at all.
     */
    public function test_a_module_that_only_errored_is_reported_as_failed_not_as_clean(): void
    {
        $facts = ReportFacts::from([
            'nuclei_results' => [
                '192.168.0.1' => [
                    'host' => [
                        'status' => 'error',
                        'target' => '192.168.0.1',
                        'error' => '[FTL] Could not run nuclei: no templates provided for scan',
                    ],
                    'web' => [
                        ['url' => 'http://192.168.0.1/', 'result' => ['status' => 'error', 'error' => 'flag not defined']],
                    ],
                ],
            ],
        ]);

        $nuclei = $facts['exposure']['nuclei_results'];

        $this->assertTrue($nuclei['present']);
        $this->assertTrue($nuclei['failed']);
        $this->assertCount(2, $nuclei['errors']);
        $this->assertSame([], $nuclei['findings']);
        $this->assertSame(0, $facts['totals']['exposure_findings']);
        $this->assertSame(1, $facts['totals']['modules_failed']);
    }

    public function test_a_module_missing_from_the_document_is_not_reported_as_clean(): void
    {
        $facts = ReportFacts::from([]);

        $this->assertFalse($facts['exposure']['smb_null_sessions']['present']);
        $this->assertFalse($facts['exposure']['smb_null_sessions']['failed']);
    }

    public function test_a_module_that_ran_and_found_nothing_is_clean(): void
    {
        $facts = ReportFacts::from(['smb_null_sessions' => []]);

        $this->assertTrue($facts['exposure']['smb_null_sessions']['present']);
        $this->assertFalse($facts['exposure']['smb_null_sessions']['failed']);
        $this->assertSame([], $facts['exposure']['smb_null_sessions']['findings']);
    }

    public function test_a_real_finding_survives_the_walk_intact(): void
    {
        $facts = ReportFacts::from([
            'broadcast_poisoning_risks' => [
                ['type' => 'INFO', 'severity' => 'INFO', 'assessment' => 'Brak zdarzeń legacy name resolution.'],
            ],
        ]);

        $module = $facts['exposure']['broadcast_poisoning_risks'];

        $this->assertFalse($module['failed']);
        $this->assertCount(1, $module['findings']);
        $this->assertSame('Brak zdarzeń legacy name resolution.', $module['findings'][0]['assessment']);
    }

    public function test_a_diagnostic_written_as_a_sentence_stays_a_sentence(): void
    {
        $facts = ReportFacts::from([
            'diagnostics' => [
                'dns_health' => 'DNS response time for 8.8.8.8: 32ms',
                'empty_one' => '',
            ],
        ]);

        $this->assertCount(1, $facts['diagnostics']);
        $this->assertSame('Kondycja DNS', $facts['diagnostics'][0]['label']);
        $this->assertSame('text', $facts['diagnostics'][0]['kind']);
        $this->assertSame('DNS response time for 8.8.8.8: 32ms', $facts['diagnostics'][0]['text']);
    }

    /**
     * A bare `false` is meaningless to a reader and actively misleading here:
     * it is the bad outcome, not the good one. Each known flag has to carry the
     * sentence that says what it means.
     */
    public function test_a_technical_flag_is_rendered_as_its_meaning_and_marked_when_it_is_a_concern(): void
    {
        $facts = ReportFacts::from([
            'diagnostics' => [
                'mitm_vulnerability' => ['gratuitous_arp_blocked' => false],
                'ipv6_spoofing' => ['ipv6_spoofing_vulnerable' => false],
            ],
        ]);

        [$mitm, $ipv6] = $facts['diagnostics'];

        $this->assertSame('fields', $mitm['kind']);
        $this->assertStringContainsString('Brak blokady Gratuitous ARP', $mitm['fields'][0]['value']);
        $this->assertTrue($mitm['fields'][0]['concern']);

        // Same literal `false`, opposite meaning: here it is the good outcome.
        $this->assertStringContainsString('Nie stwierdzono', $ipv6['fields'][0]['value']);
        $this->assertFalse($ipv6['fields'][0]['concern']);
    }

    public function test_measurements_keep_their_unit(): void
    {
        $facts = ReportFacts::from([
            'diagnostics' => ['latency' => ['min_ms' => 0.625, 'avg_ms' => 0.705]],
        ]);

        $this->assertSame('Najniższe', $facts['diagnostics'][0]['fields'][0]['label']);
        $this->assertSame('0.625 ms', $facts['diagnostics'][0]['fields'][0]['value']);
    }

    public function test_an_open_egress_port_list_is_a_concern_and_an_empty_one_is_not(): void
    {
        $open = ReportFacts::from(['diagnostics' => ['egress_filtering' => ['allowed_ports' => [4444, 3389]]]]);
        $shut = ReportFacts::from(['diagnostics' => ['egress_filtering' => ['allowed_ports' => []]]]);

        $this->assertSame('4444, 3389', $open['diagnostics'][0]['fields'][0]['value']);
        $this->assertTrue($open['diagnostics'][0]['fields'][0]['concern']);
        $this->assertFalse($shut['diagnostics'][0]['fields'][0]['concern']);
    }

    public function test_a_diagnostic_that_reported_an_error_says_so(): void
    {
        $facts = ReportFacts::from([
            'diagnostics' => [
                'wireless_security' => [
                    'monitor_mode_enabled' => false,
                    'error' => 'Błąd przełączenia w tryb monitora.',
                ],
            ],
        ]);

        $this->assertSame('Błąd przełączenia w tryb monitora.', $facts['diagnostics'][0]['error']);
    }

    public function test_label_and_value_lines_become_readings_but_a_lone_sentence_does_not(): void
    {
        $facts = ReportFacts::from([
            'diagnostics' => [
                'bandwidth' => "Ping: 25.487 ms\nDownload: 28.83 Mbit/s\nUpload: 7.36 Mbit/s",
                'network_fabric' => 'Port dostępowy zabezpieczony poprawnie.',
            ],
        ]);

        [$bandwidth, $fabric] = $facts['diagnostics'];

        $this->assertSame('fields', $bandwidth['kind']);
        $this->assertCount(3, $bandwidth['fields']);
        $this->assertSame('Download', $bandwidth['fields'][1]['label']);
        $this->assertSame('28.83 Mbit/s', $bandwidth['fields'][1]['value']);

        $this->assertSame('text', $fabric['kind']);
    }

    public function test_the_traffic_dump_becomes_a_table_sorted_by_traffic(): void
    {
        $facts = ReportFacts::from([
            'diagnostics' => [
                'top_talkers' => <<<'TEXT'
                ================================================================================
                IPv4 Endpoints
                Filter:<No Filter>
                 | Packets | | Bytes | | Tx Packets | | Tx Bytes | | Rx Packets | | Rx Bytes |
                192.168.100.180 3 642 3 642 0 0
                224.0.0.251 5 1414 0 0 5 1414
                ================================================================================
                TEXT,
            ],
        ]);

        $talkers = $facts['diagnostics'][0];

        $this->assertSame('talkers', $talkers['kind']);
        $this->assertCount(2, $talkers['rows']);
        $this->assertSame('224.0.0.251', $talkers['rows'][0]['address'], 'busiest first');
        $this->assertSame(1414, $talkers['rows'][0]['bytes']);
        $this->assertSame(5, $talkers['rows'][0]['rx_packets']);
        $this->assertSame('1,4 kB', TsharkEndpoints::bytes(1414));
        $this->assertSame('642 B', TsharkEndpoints::bytes(642));
    }

    public function test_an_unparseable_traffic_dump_still_reaches_the_page(): void
    {
        $facts = ReportFacts::from(['diagnostics' => ['top_talkers' => 'tshark: nie udało się otworzyć interfejsu']]);

        $this->assertSame('text', $facts['diagnostics'][0]['kind']);
        $this->assertStringContainsString('tshark', $facts['diagnostics'][0]['text']);
    }

    public function test_an_unknown_diagnostic_is_still_shown_rather_than_disappearing(): void
    {
        $facts = ReportFacts::from(['diagnostics' => ['brand_new_probe_test' => 'wynik']]);

        $this->assertCount(1, $facts['diagnostics']);
        $this->assertSame('Brand new probe test', $facts['diagnostics'][0]['label']);
        $this->assertSame('wynik', $facts['diagnostics'][0]['text']);
    }

    public function test_an_empty_document_yields_zeroes_rather_than_an_error(): void
    {
        $facts = ReportFacts::from([]);

        $this->assertSame(0, $facts['totals']['hosts_discovered']);
        $this->assertSame(0, $facts['totals']['open_ports']);
        $this->assertSame([], $facts['hosts']);
        $this->assertNull($facts['scan']['performed_at']);
    }

    /*
     * Schema 3 - the probe's account of its own run.
     */

    /**
     * The mistake this guards against: schema 3 writes `[]` for a test that was
     * stopped before it started, and the old reading turned that into "stan
     * prawidłowy - nie stwierdzono podatności" on a run where nothing ran.
     */
    public function test_an_empty_module_whose_test_never_ran_is_not_clean(): void
    {
        $facts = ReportFacts::from([
            'smb_null_sessions' => [],
            'audit_group_status' => [
                'smb' => ['status' => 'failed', 'evidence' => [['path' => 'diagnostics.fatal_error', 'status' => 'failed']]],
            ],
        ]);

        $smb = $facts['exposure']['smb_null_sessions'];

        $this->assertTrue($smb['failed']);
        $this->assertSame(1, $facts['totals']['modules_failed']);
        $this->assertSame('repair', $facts['plan']);
    }

    public function test_a_group_that_failed_on_some_hosts_names_them_and_keeps_the_rest_as_results(): void
    {
        $facts = ReportFacts::from([
            'smb_null_sessions' => ['192.168.0.9' => ['status' => 'ok', 'test' => 'smb']],
            'audit_group_status' => [
                'smb' => ['status' => 'failed', 'evidence' => [
                    ['path' => 'audit_plan.tests.smb', 'status' => 'ok'],
                    ['path' => 'module_status.host.192.168.0.9.smb', 'status' => 'ok'],
                    ['path' => 'module_status.host.192.168.0.10.smb', 'status' => 'timeout'],
                    ['path' => 'module_status.host.192.168.0.2.smb', 'status' => 'failed'],
                ]],
            ],
        ]);

        $smb = $facts['exposure']['smb_null_sessions'];

        $this->assertFalse($smb['failed']);
        $this->assertTrue($smb['partial']);
        $this->assertSame(1, $smb['checked']);
        $this->assertSame(['192.168.0.2', '192.168.0.10'], $smb['missed_hosts']);

        $gap = collect($facts['gaps'])->firstWhere('title', 'Udziały i sesje SMB: test nie powiódł się na 2 urządzeniach');

        $this->assertSame(['192.168.0.2', '192.168.0.10'], $gap['hosts']);
    }

    public function test_a_result_that_timed_out_or_did_not_apply_is_not_a_finding(): void
    {
        $facts = ReportFacts::from([
            'ldap_leaks' => [
                '192.168.0.1_policy' => ['status' => 'timeout', 'attempted' => false],
                '192.168.0.2_policy' => ['status' => 'skipped', 'applicability' => 'no_ldap'],
                '192.168.0.3_policy' => ['status' => 'ok', 'anonymous_bind' => false],
                '192.168.0.4_policy' => ['status' => 'ok', 'severity' => 'HIGH', 'type' => 'ANONYMOUS_BIND'],
            ],
            'broadcast_poisoning_risks' => [
                ['type' => 'NO_CREDENTIAL_MATERIAL_OBSERVED', 'severity' => 'INFO', 'finding_type' => 'no_observation'],
            ],
        ]);

        $ldap = $facts['exposure']['ldap_leaks'];

        $this->assertCount(1, $ldap['errors']);
        $this->assertSame(1, $ldap['checked']);
        $this->assertCount(1, $ldap['findings']);
        $this->assertSame('192.168.0.4', $ldap['findings'][0]['ip']);

        $this->assertSame([], $facts['exposure']['broadcast_poisoning_risks']['findings']);
        $this->assertSame(1, $facts['exposure']['broadcast_poisoning_risks']['checked']);
    }

    /**
     * 13 549 informational matches in one real report. They are folded per
     * template and host, and the findings may arrive as a generator, which
     * must be read exactly once.
     */
    public function test_normalised_nuclei_matches_are_folded_per_template_and_host(): void
    {
        $tuples = (function (): \Generator {
            for ($i = 0; $i < 1000; $i++) {
                yield [0, $i % 2, ['matched-at' => 'http://x/'.$i]];
            }

            yield [1, 0, ['matched-at' => 'http://x/admin']];
        })();

        $facts = ReportFacts::from([
            'nuclei_results' => [
                'representation' => 'normalized-ai-semantic-v1',
                'templates' => [
                    ['template-id' => 'secrets-patterns-pii', 'info' => ['name' => 'Secrets Patterns (PII)', 'severity' => 'info']],
                    ['template-id' => 'exposed-panel', 'info' => ['name' => 'Exposed admin panel', 'severity' => 'high']],
                ],
                'targets' => [
                    ['fields' => ['host' => '192.168.0.1']],
                    ['fields' => ['host' => '192.168.0.5']],
                ],
                'scans' => [
                    '192.168.0.1' => ['host' => ['status' => 'ok'], 'web' => [['result' => ['status' => 'timeout']]]],
                    '_batch_execution' => ['status' => 'failed'],
                ],
                'findings' => $tuples,
            ],
        ]);

        $nuclei = $facts['exposure']['nuclei_results'];

        $this->assertCount(3, $nuclei['findings']);
        $this->assertSame(['exposed-panel', '192.168.0.1', 1], [$nuclei['findings'][0]['template'], $nuclei['findings'][0]['ip'], $nuclei['findings'][0]['matches']]);
        $this->assertSame(500, $nuclei['findings'][1]['matches']);
        $this->assertSame([['ip' => '192.168.0.1', 'skan' => 'web', 'status' => 'timeout']], $nuclei['errors']);
        $this->assertTrue($nuclei['partial']);

        $this->assertSame(1, $facts['severity_counts']['high']);
        $this->assertSame('Exposed admin panel', $facts['findings'][0]['title']);
    }

    public function test_cve_correlations_are_one_finding_per_software_graded_below_a_confirmed_one(): void
    {
        $cve = fn (string $id, float $score): array => [
            'cve_id' => $id, 'port' => 53, 'cvss_score_reported' => $score, 'verification_required' => true,
            'affected_service' => ['product' => 'dnsmasq', 'version' => '2.83'],
        ];

        $facts = ReportFacts::from([
            'cve_correlations' => ['192.168.0.1' => ['findings' => [
                $cve('CVE-2021-3448', 4.3), $cve('CVE-2022-0934', 7.5), $cve('CVE-2020-25681', 8.1),
            ]]],
            'deep_vulnerabilities' => ['192.168.0.1' => '|_vulners: CVE-2022-0934 7.5 https://vulners.com/cve/CVE-2022-0934'],
        ]);

        $this->assertCount(1, $facts['findings']);

        $finding = $facts['findings'][0];

        // 8.1 is high; unverified, one step lower.
        $this->assertSame(Severity::MEDIUM, $finding['level']);
        $this->assertSame('Znane podatności w dnsmasq 2.83 (3 CVE, najwyższy CVSS 8.1)', $finding['title']);
        $this->assertCount(3, $finding['cves']);
        $this->assertFalse($finding['confirmed']);
    }

    public function test_a_structured_scan_wins_and_names_the_software_with_its_version(): void
    {
        $facts = ReportFacts::from([
            'hosts' => ['192.168.0.1', '192.168.0.7'],
            'nmap_structured' => [
                '192.168.0.1' => ['status' => 'ok', 'ports' => [
                    ['protocol' => 'tcp', 'port' => 22, 'state' => 'open', 'name' => 'ssh', 'product' => 'OpenSSH', 'version' => '8.9p1'],
                    ['protocol' => 'tcp', 'port' => 25, 'state' => 'closed', 'name' => 'smtp'],
                ]],
                '192.168.0.7' => ['status' => 'timeout', 'ports' => []],
            ],
            'asset_inventory' => ['192.168.0.1' => [
                'mac_address' => 'b4:b0:24:14:6f:6c', 'manufacturer' => 'TP-Link', 'model' => 'Archer AX72', 'os' => ['name' => 'Linux 3.18'],
            ]],
        ]);

        [$router, $silent] = $facts['hosts'];

        $this->assertSame('OpenSSH 8.9p1', $router['open_ports'][0]['version']);
        $this->assertSame(22, $router['open_ports'][0]['port']);
        $this->assertCount(1, $router['open_ports']);
        $this->assertSame(['TP-Link', 'Archer AX72', 'Linux 3.18'], [$router['vendor'], $router['model'], $router['os']]);
        $this->assertSame('test przekroczył limit czasu', $silent['scan_failed']);
    }

    public function test_without_audit_groups_module_statuses_are_summarised_per_module(): void
    {
        $facts = ReportFacts::from([
            'module_status' => [
                'host.192.168.0.1.nuclei' => 'timeout',
                'host.192.168.0.2.nuclei' => 'failed',
                'host.192.168.0.3.nuclei' => 'ok',
                'host.192.168.0.1' => 'partial',
                'diagnostics.vlan_hopping' => 'failed',
                'responder' => 'failed',
            ],
        ]);

        // Diagnostics and the per-host summary line are not modules of their own.
        $titles = array_column(array_filter($facts['gaps'], fn (array $gap): bool => $gap['source'] === 'Przebieg badania'), 'title');

        $this->assertSame([
            'Skanowanie szablonami (nuclei): test nie powiódł się na 2 urządzeniach',
            'Zatruwanie rozgłoszeń (Responder): test nie wykonał się',
        ], $titles);
    }

    public function test_a_run_stopped_before_anything_finished_says_so_once(): void
    {
        $failed = ['status' => 'failed', 'evidence' => [['path' => 'diagnostics.fatal_error', 'status' => 'failed']]];

        $facts = ReportFacts::from([
            'report_summary' => ['overall_result' => 'operator_stop'],
            'audit_group_status' => ['port_scanning' => $failed, 'smb' => $failed, 'tls' => $failed],
            'smb_null_sessions' => [],
        ]);

        $this->assertSame('operator_stop', $facts['scan']['outcome']);
        $this->assertStringContainsString('przerwane przez operatora', $facts['scan']['outcome_note']);
        $this->assertSame([
            'Badanie zostało przerwane przez operatora przed ukończeniem - wyniki są niepełne.',
            'Żaden z 3 testów nie został ukończony: Skanowanie portów, Udziały i sesje SMB, Konfiguracja TLS',
        ], array_slice(array_column($facts['gaps'], 'title'), 0, 2));

        // The SMB module's own "did not run" is already covered by the line above.
        $this->assertNotContains('Anonimowe sesje i udziały SMB — test nie wykonał się', array_column($facts['gaps'], 'title'));
    }

    /**
     * Report 39: operator stop, nothing finished - and a module whose group is
     * not in the list at all must not come out "stan prawidłowy" either.
     */
    public function test_once_groups_are_reported_an_empty_module_needs_its_group_to_have_run(): void
    {
        $failed = ['status' => 'failed', 'evidence' => [['path' => 'diagnostics.fatal_error', 'status' => 'failed']]];

        $facts = ReportFacts::from([
            'audit_group_status' => ['tls' => $failed, 'port_scanning' => $failed],
            'infrastructure_risks' => [],
            'ldap_leaks' => [],
        ]);

        $this->assertTrue($facts['exposure']['infrastructure_risks']['failed']);
        $this->assertTrue($facts['exposure']['ldap_leaks']['failed']);
    }

    /** Report 35: every SMB check was `skipped` - nothing to test, not "clean" and not a gap. */
    public function test_a_test_the_probe_skipped_everywhere_does_not_apply(): void
    {
        $facts = ReportFacts::from([
            'module_status' => ['host.192.168.0.1.smb' => 'skipped', 'host.192.168.0.2.smb' => 'skipped', 'host.192.168.0.1.nmap_primary' => 'ok'],
            'smb_null_sessions' => [],
        ]);

        $smb = $facts['exposure']['smb_null_sessions'];

        $this->assertTrue($smb['not_applicable']);
        $this->assertFalse($smb['failed']);
        $this->assertNotContains('Anonimowe sesje i udziały SMB — test nie wykonał się', array_column($facts['gaps'], 'title'));
    }

    /** Report 35: Responder failed to start, and said so as a HIGH entry. */
    public function test_a_tool_that_failed_to_start_is_an_error_not_a_finding(): void
    {
        $facts = ReportFacts::from([
            'broadcast_poisoning_risks' => [
                ['type' => 'RESPONDER_START_FAILED', 'severity' => 'HIGH', 'assessment' => 'Responder natychmiast zakończył działanie.'],
                ['type' => 'NO_CREDENTIAL_MATERIAL_OBSERVED', 'severity' => 'INFO', 'finding_type' => 'no_observation'],
            ],
        ]);

        $module = $facts['exposure']['broadcast_poisoning_risks'];

        $this->assertTrue($module['failed']);
        $this->assertSame(0, $module['checked']);
        $this->assertSame(0, $facts['severity_counts']['high']);
    }

    /** Report 37: one script failing on four hosts kept only the last address. */
    public function test_one_hole_on_several_devices_keeps_every_address(): void
    {
        $timeout = '|_http-vuln-cve2014-3704: ERROR: Script execution failed (use -d to debug)';

        $facts = ReportFacts::from(['deep_vulnerabilities' => [
            '192.168.0.5' => $timeout, '192.168.0.6' => $timeout, '192.168.0.9' => $timeout,
        ]]);

        $gap = collect($facts['gaps'])->firstWhere('title', 'Test http-vuln-cve2014-3704 nie uzyskał odpowiedzi');

        $this->assertSame(['192.168.0.5', '192.168.0.6', '192.168.0.9'], $gap['hosts']);
    }

    public function test_older_module_names_are_folded_into_the_audit_group_they_belong_to(): void
    {
        $facts = ReportFacts::from(['module_status' => [
            'host.192.168.0.2.vuln' => 'timeout',
            'host.192.168.0.3.vuln' => 'ok',
            'host.192.168.0.2.ftp' => 'failed',
            'host.192.168.0.3.ssh' => 'ok',
        ]]);

        $this->assertSame(['192.168.0.2'], $facts['coverage']['vulnerability_scanning']['hosts']);
        $this->assertTrue($facts['coverage']['vulnerability_scanning']['ran']);
        $this->assertSame('Audyt poświadczeń', $facts['coverage']['credential_auditing']['label']);
    }

    public function test_stored_results_keep_only_what_is_shown(): void
    {
        $facts = ReportFacts::from(['ldap_leaks' => ['192.168.0.1' => [
            'status' => 'failed', 'error' => str_repeat('x', 5000), 'raw' => ['huge' => str_repeat('y', 5000)],
        ]]]);

        $error = $facts['exposure']['ldap_leaks']['errors'][0];

        $this->assertArrayNotHasKey('raw', $error);
        $this->assertLessThan(700, mb_strlen($error['error']));
        $this->assertSame('192.168.0.1', $error['ip']);
    }

    /*
     * Schema 3 - NSE findings arrive one per script in nse_script_evidence.
     */

    public function test_schema_3_nse_evidence_becomes_deep_findings(): void
    {
        $facts = ReportFacts::from([
            'nse_script_evidence' => [
                '192.168.0.1' => [
                    [
                        'script_id' => 'http-slowloris-check', 'state' => 'vulnerable', 'port' => 80,
                        'cve_ids' => ['CVE-2007-6750'], 'evidence_sha256' => 'a',
                        'evidence_excerpt' => "| http-slowloris-check: \n|   VULNERABLE:\n|   Slowloris DOS attack\n|     State: LIKELY VULNERABLE\n|     IDs:  CVE:CVE-2007-6750",
                    ],
                    [
                        'script_id' => 'http-title', 'state' => 'observed', 'port' => 80, 'evidence_sha256' => 'b',
                        'evidence_excerpt' => '| http-title: Router',
                    ],
                ],
            ],
        ]);

        $deep = collect($facts['findings'])->where('source', 'Pogłębione testy');

        $slowloris = $deep->firstWhere('title', 'Slowloris DOS attack');
        $this->assertSame(Severity::HIGH, $slowloris['level']);
        $this->assertSame('http-slowloris-check · port 80', $slowloris['where']);
        $this->assertSame(['CVE-2007-6750'], $slowloris['cves']);

        // An http-title observation is counted but is not a finding.
        $this->assertSame(2, $facts['totals']['deep_findings']);
        $this->assertSame(1, $facts['totals']['deep_findings_notable']);
        $this->assertNull($deep->firstWhere('where', 'http-title · port 80'));
    }

    /**
     * broadcast-avahi-dos quotes a CVE in what it checks for; the probe's
     * not_vulnerable state must win, or a clean host reads as critical.
     */
    public function test_a_not_vulnerable_state_wins_over_a_cve_in_the_excerpt(): void
    {
        $facts = ReportFacts::from([
            'nse_script_evidence' => [
                '192.168.0.56' => [[
                    'script_id' => 'broadcast-avahi-dos', 'state' => 'not_vulnerable', 'port' => null,
                    'cve_ids' => ['CVE-2011-1002'], 'evidence_sha256' => 'c',
                    'evidence_excerpt' => "| broadcast-avahi-dos: \n|   Discovered hosts:\n|   DoS CVE-2011-1002\n|_  Hosts are all up (not vulnerable).",
                ]],
            ],
        ]);

        $this->assertSame([], collect($facts['findings'])->where('source', 'Pogłębione testy')->all());
        $this->assertSame(1, $facts['totals']['deep_findings']);
        $this->assertSame(0, $facts['totals']['deep_findings_notable']);
    }

    public function test_exact_duplicate_nse_records_collapse_but_distinct_ports_stay(): void
    {
        $poodle = fn (int $port): array => [
            'script_id' => 'ssl-poodle', 'state' => 'vulnerable', 'port' => $port, 'cve_ids' => ['CVE-2014-3566'],
            'evidence_sha256' => 'sha'.$port,
            'evidence_excerpt' => "| ssl-poodle: \n|   VULNERABLE:\n|   SSL POODLE information leak\n|     State: VULNERABLE\n|     IDs:  CVE:CVE-2014-3566",
        ];

        $facts = ReportFacts::from(['nse_script_evidence' => [
            '192.168.0.9' => [$poodle(443), $poodle(4443), $poodle(443)],
        ]]);

        $poodles = collect($facts['findings'])->where('title', 'SSL POODLE information leak');

        $this->assertSame(2, $poodles->count());
        $this->assertEqualsCanonicalizing(
            ['ssl-poodle · port 443', 'ssl-poodle · port 4443'],
            $poodles->pluck('where')->all(),
        );
        $this->assertSame(Severity::CRITICAL, $poodles->first()['level']);
    }

    public function test_a_vulnerable_state_floors_severity_when_the_excerpt_was_truncated(): void
    {
        // The excerpt was cut before nmap's State: line, so it grades INFO.
        $facts = ReportFacts::from(['nse_script_evidence' => [
            '192.168.0.1' => [[
                'script_id' => 'http-fileupload-exploiter', 'state' => 'vulnerable', 'port' => 80,
                'evidence_sha256' => 'd', 'evidence_excerpt' => '| http-fileupload-exploiter: uploaded shell',
            ]],
        ]]);

        $finding = collect($facts['findings'])->firstWhere('where', 'http-fileupload-exploiter · port 80');

        $this->assertSame(Severity::HIGH, $finding['level']);
        $this->assertFalse($finding['confirmed']);
    }

    public function test_vulners_deep_findings_are_dropped_where_cve_correlations_cover_the_host(): void
    {
        $doc = [
            'nse_script_evidence' => ['192.168.0.1' => [[
                'script_id' => 'vulners', 'state' => 'observed', 'port' => 53, 'cve_ids' => ['CVE-2021-3448'],
                'evidence_sha256' => 'e', 'evidence_excerpt' => "| vulners: \n|   cpe:/a:thekelleys:dnsmasq:2.83:\n|_    CVE-2021-3448\t4.3",
            ]]],
            'cve_correlations' => ['192.168.0.1' => ['findings' => [[
                'cve_id' => 'CVE-2021-3448', 'cvss_score_reported' => 7.5, 'port' => 53,
                'affected_service' => ['product' => 'dnsmasq', 'version' => '2.83'], 'verification_required' => true,
            ]]]],
        ];

        $facts = ReportFacts::from($doc);

        $this->assertNull(collect($facts['findings'])->firstWhere('source', 'Pogłębione testy'));
        $this->assertNotNull(collect($facts['findings'])->firstWhere('source', 'Korelacja wersji z bazą CVE'));
    }

    public function test_a_nuclei_match_is_attributed_to_the_ip_and_never_confirmed(): void
    {
        $facts = ReportFacts::from(['nuclei_results' => [
            'representation' => 'normalized-ai-semantic-grouped-v2',
            'templates' => [['template-id' => 'exposed-panel', 'info' => ['name' => 'Exposed panel', 'severity' => 'high']]],
            'targets' => [['attributed_target' => 'https://10.0.0.5:8443/', 'fields' => ['host' => 'https://10.0.0.5:8443', 'ip' => '10.0.0.5', 'port' => '8443']]],
            'scans' => ['10.0.0.5' => ['host' => ['status' => 'ok']]],
            'findings' => [[0, 0, ['matched-at' => 'https://10.0.0.5:8443/admin']]],
        ]]);

        $finding = collect($facts['findings'])->firstWhere('source', 'Skanowanie szablonami znanych podatności');

        $this->assertSame('10.0.0.5', $finding['ip']);
        $this->assertSame(Severity::HIGH, $finding['level']);
        $this->assertFalse($finding['confirmed']);
    }

    public function test_finding_state_drops_false_positives_and_confirms_only_confirmed(): void
    {
        $facts = ReportFacts::from([
            'cve_correlations' => ['10.0.0.1' => ['findings' => [
                ['cve_id' => 'CVE-1', 'cvss_score_reported' => 9.5, 'finding_state' => 'false_positive', 'affected_service' => ['product' => 'x', 'version' => '1']],
                ['cve_id' => 'CVE-2', 'cvss_score_reported' => 9.5, 'finding_state' => 'confirmed', 'affected_service' => ['product' => 'x', 'version' => '1']],
            ]]],
            'device_security_posture' => ['10.0.0.2' => ['findings' => [
                ['severity' => 'high', 'title' => 'Fałszywy alarm', 'finding_state' => 'false_positive'],
                ['severity' => 'high', 'title' => 'Potwierdzone', 'finding_state' => 'confirmed'],
                ['severity' => 'medium', 'title' => 'Kandydat', 'finding_state' => 'candidate'],
                ['severity' => 'high', 'title' => 'Nie dotyczy', 'finding_state' => 'not_applicable'],
            ]]],
        ]);

        $titles = array_column($facts['findings'], 'title');
        $this->assertNotContains('Fałszywy alarm', $titles);
        $this->assertNotContains('Nie dotyczy', $titles);

        // The false-positive CVE is gone, so the group holds one confirmed CVE.
        $cve = collect($facts['findings'])->firstWhere('source', 'Korelacja wersji z bazą CVE');
        $this->assertStringContainsString('1 CVE', $cve['title']);
        $this->assertSame(Severity::CRITICAL, $cve['level']);
        $this->assertTrue($cve['confirmed']);

        $confirmed = collect($facts['findings'])->firstWhere('title', 'Potwierdzone');
        $this->assertTrue($confirmed['confirmed']);
        $this->assertFalse(collect($facts['findings'])->firstWhere('title', 'Kandydat')['confirmed']);
    }

    public function test_schema_3_structured_traffic_becomes_a_table(): void
    {
        $facts = ReportFacts::from(['diagnostics' => ['top_talkers_evidence' => [
            'status' => 'parsed_endpoint_rows',
            'endpoints' => [
                ['endpoint' => '192.168.0.1', 'counters' => ['packets' => 10, 'bytes' => 1000, 'tx_packets' => 6, 'tx_bytes' => 600, 'rx_packets' => 4, 'rx_bytes' => 400]],
                ['endpoint' => '10.0.0.9', 'counters' => ['packets' => 99, 'bytes' => 9_000_000, 'tx_packets' => 50, 'tx_bytes' => 5_000_000, 'rx_packets' => 49, 'rx_bytes' => 4_000_000]],
            ],
        ]]]);

        $talkers = collect($facts['diagnostics'])->firstWhere('kind', 'talkers');

        $this->assertNotNull($talkers);
        // Sorted by total bytes, busiest first.
        $this->assertSame(['10.0.0.9', '192.168.0.1'], array_column($talkers['rows'], 'address'));
        $this->assertSame(5_000_000, $talkers['rows'][0]['tx_bytes']);
    }

    public function test_schema_3_bandwidth_metrics_read_as_labelled_values(): void
    {
        $facts = ReportFacts::from(['diagnostics' => ['bandwidth_evidence' => [
            'status' => 'ok',
            'metrics' => [
                'ping' => ['value' => '35.1', 'unit' => 'ms'],
                'download' => ['value' => '91.6', 'unit' => 'Mbit/s'],
                'upload' => ['value' => '88.8', 'unit' => 'Mbit/s'],
            ],
        ]]]);

        $bandwidth = collect($facts['diagnostics'])->firstWhere('label', 'Przepustowość łącza');

        $this->assertSame('fields', $bandwidth['kind']);
        $values = collect($bandwidth['fields'])->pluck('value', 'label');
        $this->assertSame('91.6 Mbit/s', $values['Pobieranie']);
        $this->assertSame('88.8 Mbit/s', $values['Wysyłanie']);
        // A bandwidth reading is never a finding.
        $this->assertSame([], collect($bandwidth['fields'])->where('concern', true)->all());
    }

    public function test_grouped_technical_observations_are_counted_not_turned_into_findings(): void
    {
        $facts = ReportFacts::from(['nuclei_results' => [
            'representation' => 'normalized-ai-semantic-grouped-v2',
            'templates' => [['template-id' => 'secrets-patterns-pii', 'info' => ['name' => 'PII', 'severity' => 'info']]],
            'targets' => [['fields' => ['ip' => '10.0.0.5']]],
            'scans' => ['10.0.0.5' => ['host' => ['status' => 'ok']]],
            'findings' => [],
            'grouped_observations' => [
                ['extractor' => 'times', 'occurrence_count' => 7, 'distinct_location_count' => 3],
                ['extractor' => 'hex_colors', 'occurrence_count' => 2, 'distinct_location_count' => 1],
            ],
            'security_location_observations' => [
                'columns' => ['template_ref', 'target_ref', 'extractor_ref', 'location_ref', 'occurrence_count', 'matcher_status'],
                'rows' => [[0, 0, 0, 0, 4, true]],
            ],
        ]]);

        $module = $facts['exposure']['nuclei_results'];

        $this->assertSame(13, $module['observations']);
        // They are not findings and change nothing in the ranked list.
        $this->assertSame([], collect($facts['findings'])->where('source', 'Skanowanie szablonami znanych podatności')->all());
        $this->assertSame(0, $facts['severity_counts']['info']);
    }

    public function test_an_identity_that_changed_owner_mid_scan_is_set_aside_not_misattributed(): void
    {
        $facts = ReportFacts::from([
            'unattributed_identity_attempts' => [
                ['address_at_observation' => '192.168.0.50', 'confirmed_finding_owner' => null],
            ],
            // The current owner's data stays attributed to its IP as usual.
            'device_security_posture' => ['192.168.0.50' => ['findings' => [
                ['severity' => 'medium', 'title' => 'Panel po HTTP', 'finding_state' => 'confirmed'],
            ]]],
        ]);

        $gap = collect($facts['gaps'])->firstWhere('source', 'Tożsamość urządzeń');
        $this->assertNotNull($gap);
        $this->assertStringContainsString('nie przypisano', $gap['title']);

        // The current owner's own finding is still shown.
        $this->assertNotNull(collect($facts['findings'])->firstWhere('title', 'Panel po HTTP'));
    }

    public function test_an_inconclusive_nse_state_without_evidence_is_counted_not_flagged(): void
    {
        // Short evidence (<512 B on the probe) means no excerpt reaches us and
        // the script cited no CVE. It ran but determined nothing, so it is
        // counted among the deep results without becoming a finding or a gap -
        // flagging every empty inconclusive script would bury the real ones.
        $facts = ReportFacts::from(['nse_script_evidence' => ['192.168.0.9' => [
            ['script_id' => 'http-vuln-cve2017-1001000', 'state' => 'inconclusive', 'port' => 80, 'cve_ids' => [], 'evidence_sha256' => 'q'],
        ]]]);

        $this->assertSame([], collect($facts['findings'])->where('source', 'Pogłębione testy')->all());
        $this->assertSame([], collect($facts['gaps'])->where('source', 'Pogłębione testy')->all());
        $this->assertSame(1, $facts['totals']['deep_findings']);
    }

    public function test_an_inconclusive_state_with_a_cve_is_shown_but_marked_unconfirmed(): void
    {
        $facts = ReportFacts::from(['nse_script_evidence' => ['192.168.0.9' => [
            ['script_id' => 'http-phpmyadmin-dir-traversal', 'state' => 'inconclusive', 'port' => 80,
                'cve_ids' => ['CVE-2005-3299'], 'evidence_sha256' => 'r',
                'evidence_excerpt' => 'VULNERABLE: phpMyAdmin traversal CVE-2005-3299 (short)'],
        ]]]);

        $finding = collect($facts['findings'])->firstWhere('source', 'Pogłębione testy');
        $this->assertNotNull($finding);
        $this->assertFalse($finding['confirmed']);
        $this->assertNotNull($finding['note']);
    }

    /*
     * Database and web-fuzzing findings routed into deep_vulnerabilities as
     * structured entries (deepFindings reads only the NSE string entries).
     */

    public function test_database_rce_and_exposure_signals_become_findings(): void
    {
        $facts = ReportFacts::from(['deep_vulnerabilities' => [
            '192.0.2.30_databases' => [
                'postgres' => ['status' => 'ok', 'critical_rce_confirmed' => true],
                'mysql' => ['status' => 'ok', 'deep_pii_radar' => ['PESEL w crm.klienci'], 'nse_empty_password_accounts' => ['root']],
                'redis' => ['critical_no_auth' => true],
            ],
        ]]);

        $db = collect($facts['findings'])->where('source', 'Bazy danych');

        $this->assertSame(Severity::CRITICAL, $db->firstWhere('title', 'Potwierdzone zdalne wykonanie kodu na bazie danych (RCE)')['level']);
        $this->assertNotNull($db->firstWhere('title', 'Baza danych dostępna bez uwierzytelnienia'));
        $this->assertNotNull($db->firstWhere('title', 'Konto bazy danych bez hasła'));
        $pii = $db->firstWhere('title', 'Dane wrażliwe dostępne w bazie danych');
        $this->assertStringContainsString('PESEL', $pii['note']);
        $this->assertTrue($pii['confirmed']);
    }

    public function test_a_database_result_that_only_timed_out_is_not_a_finding(): void
    {
        $facts = ReportFacts::from(['deep_vulnerabilities' => [
            '192.0.2.40_databases' => ['mysql' => ['status' => 'timeout', 'test' => 'databases']],
        ]]);

        $this->assertSame([], collect($facts['findings'])->where('source', 'Bazy danych')->all());
    }

    public function test_web_fuzz_paths_surface_as_an_unconfirmed_finding(): void
    {
        $facts = ReportFacts::from(['deep_vulnerabilities' => [
            '192.0.2.40_web_fuzz' => [['discovered_paths' => [
                'http://192.0.2.40/.git/config', 'http://192.0.2.40/backup.sql', 'http://192.0.2.40/admin/',
            ]]],
        ]]);

        $finding = collect($facts['findings'])->firstWhere('source', 'Aplikacje webowe');

        $this->assertSame(Severity::MEDIUM, $finding['level']);
        $this->assertFalse($finding['confirmed']);
        $this->assertStringContainsString('backup.sql', $finding['note']);
    }

    /**
     * A server answering 200 to every favicon.ico.<ext> floods web fuzzing with
     * soft-404 noise; reporting it as a finding would overstate.
     */
    public function test_a_soft_404_wildcard_is_not_reported(): void
    {
        $paths = array_map(
            fn (string $ext): string => "http://192.0.2.40/favicon.ico.$ext",
            ['zip', 'bak', 'sql', 'php', 'txt', 'env', 'config'],
        );
        $paths[] = 'https://192.0.2.40/favicon.ico.tar.gz';

        $facts = ReportFacts::from(['deep_vulnerabilities' => [
            '192.0.2.40_web_fuzz' => [['discovered_paths' => $paths]],
        ]]);

        $this->assertSame([], collect($facts['findings'])->where('source', 'Aplikacje webowe')->all());
    }

    public function test_the_probe_headline_risk_is_carried_to_the_facts(): void
    {
        $facts = ReportFacts::from(['report_summary' => ['overall_result' => 'completed', 'highest_observed_risk' => 'high']]);

        $this->assertSame('high', $facts['scan']['highest_risk']);
        $this->assertNull(ReportFacts::from([])['scan']['highest_risk']);
    }
}
