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
}
