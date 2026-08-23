<?php

namespace Tests\Unit;

use App\Services\ReportFacts;
use App\Support\Severity;
use PHPUnit\Framework\TestCase;

/**
 * Grading decides what a reader does first, so it is worth pinning down. The
 * rule under test throughout: nmap's own confidence wins. A script that names a
 * vulnerability and then fails to confirm it has not found one.
 */
class SeverityTest extends TestCase
{
    public function test_a_confirmed_vulnerability_with_a_cve_is_critical(): void
    {
        $grade = Severity::ofScript(
            "VULNERABLE:\nSSL POODLE information leak\nState: VULNERABLE\nIDs: CVE:CVE-2014-3566",
        );

        $this->assertSame(Severity::CRITICAL, $grade['level']);
        $this->assertTrue($grade['confirmed']);
        $this->assertFalse($grade['inconclusive']);
    }

    public function test_a_confirmed_vulnerability_without_a_cve_is_high_not_critical(): void
    {
        $grade = Severity::ofScript("VULNERABLE:\nSomething is off\nState: VULNERABLE");

        $this->assertSame(Severity::HIGH, $grade['level']);
    }

    /**
     * The case that matters most. phpMyAdmin prints "VULNERABLE:" and a CVE and
     * then admits `State: UNKNOWN (unable to test)`. Grading that critical would
     * put an unproven claim at the top of a client's report.
     */
    public function test_a_finding_the_script_could_not_confirm_is_demoted(): void
    {
        $grade = Severity::ofScript(
            "VULNERABLE:\nphpMyAdmin Local File Inclusion\nState: UNKNOWN (unable to test)\nIDs: CVE:CVE-2005-3299",
        );

        $this->assertSame(Severity::MEDIUM, $grade['level']);
        $this->assertFalse($grade['confirmed']);
        $this->assertTrue($grade['inconclusive']);
    }

    public function test_a_script_that_got_no_answer_is_not_a_finding_at_all(): void
    {
        foreach (['No reply from server (TIMEOUT)', 'ERROR: Script execution failed (use -d to debug)'] as $output) {
            $grade = Severity::ofScript($output);

            $this->assertSame(Severity::INFO, $grade['level'], $output);
            $this->assertTrue($grade['inconclusive'], $output);
        }
    }

    public function test_a_bare_cve_mention_is_high(): void
    {
        $this->assertSame(
            Severity::HIGH,
            Severity::ofScript('Litespeed Source Code Disclosure (CVE-2010-2333)')['level'],
        );
    }

    public function test_ordinary_script_chatter_is_only_informational(): void
    {
        $this->assertSame(Severity::INFO, Severity::ofScript('GoAhead-Webs')['level']);
    }

    public function test_the_headline_skips_nmaps_bare_marker(): void
    {
        $title = Severity::titleOf("VULNERABLE:\nSSL POODLE information leak\nState: VULNERABLE", 'ssl-poodle');

        $this->assertSame('SSL POODLE information leak', $title);
    }

    public function test_the_headline_falls_back_to_the_script_name(): void
    {
        $this->assertSame('ssl-poodle', Severity::titleOf("VULNERABLE:\n\n", 'ssl-poodle'));
    }

    public function test_cleartext_and_remote_desktop_services_outrank_databases(): void
    {
        $this->assertSame(Severity::HIGH, Severity::ofPort(23)['level']);
        $this->assertSame(Severity::HIGH, Severity::ofPort(3389)['level']);
        $this->assertSame(Severity::MEDIUM, Severity::ofPort(3306)['level']);
        $this->assertNull(Severity::ofPort(80), 'an ordinary web port is not a finding on its own');
    }

    public function test_findings_arrive_sorted_with_the_worst_first(): void
    {
        $facts = ReportFacts::from([
            'hosts' => ['10.0.0.5'],
            'nmap_results' => ['10.0.0.5' => "Host is up (0.001s latency).\n23/tcp open telnet\n"],
            'deep_vulnerabilities' => [
                '10.0.0.5' => '|_ssl-poodle: VULNERABLE: State: VULNERABLE IDs: CVE:CVE-2014-3566',
            ],
            'diagnostics' => ['physical_port_security' => ['stp_bpdu_guard_missing' => true]],
        ]);

        $levels = array_column($facts['findings'], 'level');

        $this->assertSame([Severity::CRITICAL, Severity::HIGH, Severity::MEDIUM], $levels);
        $this->assertSame(3, $facts['actionable']);
        $this->assertSame(1, $facts['severity_counts'][Severity::CRITICAL]);
    }

    public function test_a_failed_check_becomes_a_gap_and_never_a_finding(): void
    {
        $facts = ReportFacts::from([
            'nuclei_results' => ['10.0.0.5' => ['host' => ['status' => 'error', 'error' => 'no templates']]],
            'deep_vulnerabilities' => ['10.0.0.5' => '|_ssl-ccs-injection: No reply from server (TIMEOUT)'],
            'diagnostics' => ['wireless_security' => ['monitor_mode_enabled' => false]],
        ]);

        $this->assertSame([], $facts['findings']);
        $this->assertSame(0, $facts['actionable']);
        $this->assertCount(8, $facts['gaps'], 'one per failed check plus the modules that sent nothing');

        // Coverage holes must never buy the congratulatory ending.
        $this->assertSame('repair', $facts['plan']);
    }

    public function test_a_clean_scan_with_full_coverage_earns_the_maintenance_plan(): void
    {
        $facts = ReportFacts::from([
            'hosts' => ['10.0.0.5'],
            'nmap_results' => ['10.0.0.5' => "Host is up (0.001s latency).\n80/tcp open http\n"],
            'smb_null_sessions' => [],
            'broadcast_poisoning_risks' => [],
            'nuclei_results' => [],
            'default_credentials' => [],
            'ldap_leaks' => [],
            'infrastructure_risks' => [],
            'diagnostics' => ['ipv6_spoofing' => ['ipv6_spoofing_vulnerable' => false]],
        ]);

        $this->assertSame([], $facts['findings']);
        $this->assertSame([], $facts['gaps']);
        $this->assertSame('maintain', $facts['plan']);
    }

    public function test_a_reassuring_reading_never_enters_the_ranked_list(): void
    {
        $facts = ReportFacts::from([
            'diagnostics' => ['mitm_vulnerability' => ['gratuitous_arp_blocked' => true]],
        ]);

        $this->assertSame([], $facts['findings']);

        // The exposure checks are absent from this document, so coverage is
        // incomplete and the plan stays "repair" - the reassuring ARP reading
        // does not buy a clean bill of health on its own.
        $this->assertSame('repair', $facts['plan']);
        $this->assertNotSame([], $facts['gaps']);
    }
}
