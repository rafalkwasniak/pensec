<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Report;
use App\Services\RemediationPrompt;
use App\Services\ReportFacts;
use App\Support\Severity;
use PHPUnit\Framework\TestCase;

class RemediationPromptTest extends TestCase
{
    private function report(): Report
    {
        $report = new Report(['report_uid' => '0f3c9a1e-0000-4000-8000-000000000000']);
        $report->setRelation('device', new Device(['name' => 'sonda-1']));

        return $report;
    }

    public function test_the_answer_splits_into_blocks_by_finding_number(): void
    {
        $blocks = RemediationPrompt::split("Wstęp, który ma zniknąć.\n### KROK: 1\n1. Pierwszy.\n\n### KROK: 2\n1. Drugi.\n### KROK: 3\n");

        $this->assertSame([1 => '1. Pierwszy.', 2 => '1. Drugi.'], $blocks);
    }

    public function test_an_answer_without_markers_yields_nothing(): void
    {
        $this->assertSame([], RemediationPrompt::split('Bez bloków.'));
    }

    public function test_the_brief_lists_only_actionable_findings_with_their_device(): void
    {
        $facts = ReportFacts::from([
            'hosts' => ['192.0.2.20'],
            'nmap_structured' => ['192.0.2.20' => ['status' => 'ok', 'ports' => [
                ['protocol' => 'tcp', 'port' => 23, 'state' => 'open', 'name' => 'telnet'],
            ]]],
            'asset_inventory' => ['192.0.2.20' => ['manufacturer' => 'Visinet', 'model' => 'IP-Cam 3', 'os' => ['name' => 'Embedded Linux']]],
        ]);

        $brief = RemediationPrompt::user($facts, $this->report());

        $this->assertStringContainsString('USTERKA 1', $brief);
        $this->assertStringContainsString('192.0.2.20', $brief);
        $this->assertStringContainsString('Urządzenie: Visinet, IP-Cam 3, system Embedded Linux', $brief);
        $this->assertStringContainsString('Usługi na urządzeniu: 23/TCP telnet', $brief);
        $this->assertStringNotContainsString('USTERKA 2', $brief);

        foreach (RemediationPrompt::actionable($facts) as $finding) {
            $this->assertTrue(Severity::actionable($finding['level']));
        }
    }

    public function test_a_clean_network_gets_a_brief_with_nothing_to_fix(): void
    {
        $brief = RemediationPrompt::user(ReportFacts::from([]), $this->report());

        $this->assertStringContainsString('nie wykazało usterek', $brief);
        $this->assertStringNotContainsString('USTERKA', $brief);
    }
}
