<?php

namespace Tests\Unit;

use App\Support\ReportSections;
use PHPUnit\Framework\TestCase;

class ReportSectionsTest extends TestCase
{
    public function test_it_decodes_only_the_sections_facts_are_built_from(): void
    {
        $json = json_encode([
            'scan_time' => '2026-08-16 13:38:12',
            'hosts' => ['192.168.0.1'],
            'routing_topology' => ['huge' => str_repeat('x', 1000)],
        ]);

        $sections = ReportSections::fromJson($json);

        $this->assertSame(['scan_time' => '2026-08-16 13:38:12', 'hosts' => ['192.168.0.1']], $sections);
    }

    public function test_it_reads_the_report_out_of_a_submission_envelope(): void
    {
        $json = json_encode(['report_id' => 'r1', 'report' => ['scan_time' => 't', 'hosts' => []]]);

        $this->assertSame(['scan_time' => 't', 'hosts' => []], ReportSections::fromJson($json, envelope: true));
        $this->assertSame([], ReportSections::fromJson('{"report_id": "r1", "report": []}', envelope: true));
    }

    public function test_an_older_nuclei_section_is_decoded_whole(): void
    {
        $json = json_encode(['nuclei_results' => ['192.168.0.1' => ['host' => ['status' => 'error']]]]);

        $this->assertSame(['192.168.0.1' => ['host' => ['status' => 'error']]], ReportSections::fromJson($json)['nuclei_results']);
    }

    /**
     * The point of the class: nuclei's findings are the part that grows with
     * the network, and they are never decoded all at once.
     */
    public function test_normalised_nuclei_findings_are_read_one_at_a_time(): void
    {
        $tuple = json_encode([0, 0, ['matched-at' => 'http://192.168.0.1/'.str_repeat('a', 300)]]);
        $json = '{"nuclei_results":{"representation":"normalized-ai-semantic-v1","templates":[{"template-id":"t"}],'
            .'"targets":[],"findings":['.implode(',', array_fill(0, 100_000, $tuple)).']}}';

        $before = memory_get_usage();
        $nuclei = ReportSections::fromJson($json)['nuclei_results'];

        $this->assertInstanceOf(\Generator::class, $nuclei['findings']);
        $this->assertSame([['template-id' => 't']], $nuclei['templates']);

        $count = 0;
        $peak = 0;

        foreach ($nuclei['findings'] as $finding) {
            $count++;
            $peak = max($peak, memory_get_usage() - $before);
        }

        $this->assertSame(100_000, $count);
        // Decoded whole, these 35 MB would take about 200 MB.
        $this->assertLessThan(2 * 1024 * 1024, $peak);
    }
}
