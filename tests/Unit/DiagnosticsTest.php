<?php

namespace Tests\Unit;

use App\Support\Diagnostics;
use PHPUnit\Framework\TestCase;

class DiagnosticsTest extends TestCase
{
    public function test_internal_bookkeeping_diagnostics_are_dropped(): void
    {
        $described = Diagnostics::describe('fatal_error', 'Audyt zatrzymany przez operatora.');

        $this->assertSame([], $described['fields']);
        $this->assertSame('', $described['text']);
        $this->assertNull($described['error']);
    }

    public function test_known_probe_fields_get_polish_labels_and_are_kept(): void
    {
        $described = Diagnostics::describe('rogue_dhcp', [
            'dhcp_servers' => '192.168.0.1',
            'status' => 'ok',
        ]);

        $labels = array_column($described['fields'], 'label');
        $this->assertContains('Serwery DHCP', $labels);
        $this->assertContains('Stan', $labels);

        foreach ($described['fields'] as $field) {
            $this->assertTrue($field['known']);
        }
    }

    public function test_unknown_fields_are_flagged_so_the_manager_report_can_hide_them(): void
    {
        $described = Diagnostics::describe('rogue_dhcp', ['dziwne_pole_sondy' => 'x']);

        $this->assertSame('Dziwne pole sondy', $described['fields'][0]['label']);
        $this->assertFalse($described['fields'][0]['known']);
    }

    public function test_internal_debug_fields_are_hidden_entirely(): void
    {
        $described = Diagnostics::describe('ipv6_spoofing', [
            'error_sha256' => 'f711106325b6e623',
            'error_bytes' => 515,
            'error_redacted' => true,
        ]);

        $this->assertSame([], $described['fields']);
    }

    public function test_vlan_check_is_reduced_to_its_assessment_sentence(): void
    {
        $described = Diagnostics::describe('vlan_hopping', [[
            'target' => '192.168.0.1',
            'interface' => 'eth0',
            'status' => 'SECURE',
            'severity' => 'INFO',
            'confidence' => 'medium',
            'assessment' => 'Brak odpowiedzi na ruch tagowany przy potwierdzonej odpowiedzi kontrolnej.',
        ]]);

        $this->assertCount(1, $described['fields']);
        $this->assertSame('Brak odpowiedzi na ruch tagowany przy potwierdzonej odpowiedzi kontrolnej.', $described['fields'][0]['value']);
        $this->assertFalse($described['fields'][0]['concern']);
        // The internal key:value dump must be gone.
        $this->assertStringNotContainsString('Interface:', $described['fields'][0]['value']);
        $this->assertStringNotContainsString('Confidence:', $described['fields'][0]['value']);
    }
}
