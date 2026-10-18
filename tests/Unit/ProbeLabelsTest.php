<?php

namespace Tests\Unit;

use App\Support\ProbeLabels;
use PHPUnit\Framework\TestCase;

class ProbeLabelsTest extends TestCase
{
    public function test_known_tokens_become_plain_polish(): void
    {
        $this->assertSame('zablokowany', ProbeLabels::term('blocked'));
        $this->assertSame('zablokowane przez zakres lub politykę badania', ProbeLabels::term('foreign_block'));
        $this->assertSame('wymaga uwagi', ProbeLabels::term('WARNING'));
        $this->assertSame('otwarty lub filtrowany', ProbeLabels::term('open|filtered'));
        $this->assertSame('Stan', ProbeLabels::term('Status'));
    }

    public function test_test_names_become_friendly_for_known_ids_and_pass_through_otherwise(): void
    {
        $this->assertSame('Dopasowanie znanych podatności', ProbeLabels::testName('vulners'));
        $this->assertSame('Ujawnienie kodu źródłowego', ProbeLabels::testName('http-litespeed-sourcecode-download'));
        $this->assertSame('jakis-nieznany-skrypt', ProbeLabels::testName('jakis-nieznany-skrypt'));
    }

    public function test_unknown_values_pass_through_unchanged(): void
    {
        // An address, a number or a version is never a token and must survive.
        $this->assertSame('192.0.2.30', ProbeLabels::term('192.0.2.30'));
        $this->assertSame('0.8 ms', ProbeLabels::term('0.8 ms'));
        $this->assertSame('dnsmasq 2.80', ProbeLabels::term('dnsmasq 2.80'));
        $this->assertNull(ProbeLabels::term(null));
    }

    public function test_evidence_humanises_only_the_state_words(): void
    {
        $raw = "VULNERABLE:\nSSL POODLE information leak\nState: LIKELY VULNERABLE\nIDs: CVE:CVE-2014-3566";
        $out = ProbeLabels::evidence($raw);

        $this->assertStringContainsString('PODATNOŚĆ:', $out);
        $this->assertStringContainsString('Stan: prawdopodobnie podatny', $out);
        $this->assertStringContainsString('Identyfikatory CVE:', $out);
        // The evidence itself (the CVE id) is left intact.
        $this->assertStringContainsString('CVE-2014-3566', $out);
        $this->assertStringNotContainsString('LIKELY VULNERABLE', $out);
    }
}
