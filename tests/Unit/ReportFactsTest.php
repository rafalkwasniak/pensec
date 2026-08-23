<?php

namespace Tests\Unit;

use App\Services\ReportFacts;
use App\Support\NmapOutput;
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
}
