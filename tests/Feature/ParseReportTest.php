<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Jobs\ParseReport;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParseReportTest extends TestCase
{
    use RefreshDatabase;

    /** A report whose document exposes an insecure service, so it yields findings. */
    private function reportWithTelnet(): Report
    {
        return Report::factory()->withDocument([
            'scan_time' => '2026-08-16 13:38:12',
            'hosts' => ['192.0.2.20'],
            'nmap_structured' => ['192.0.2.20' => ['status' => 'ok', 'ports' => [
                ['protocol' => 'tcp', 'port' => 23, 'state' => 'open', 'name' => 'telnet'],
            ]]],
            'asset_inventory' => ['192.0.2.20' => ['manufacturer' => 'Visinet', 'model' => 'IP-Cam 3']],
        ])->create();
    }

    public function test_it_parses_a_report_into_findings_and_a_summary(): void
    {
        $report = $this->reportWithTelnet();

        (new ParseReport($report->id))->handle();
        $report->refresh();

        $this->assertSame(ReportStatus::Parsed, $report->status);
        $this->assertNotNull($report->parsed_at);
        $this->assertNull($report->parse_failure_reason);

        $this->assertGreaterThan(0, $report->findings()->count());

        // The summary is the panel header, derived from the same facts.
        $this->assertArrayHasKey('severity_counts', $report->summary);
        $this->assertArrayHasKey('totals', $report->summary);
        $this->assertArrayHasKey('plan', $report->summary);

        // Every finding carries the shape the panel renders.
        $finding = $report->findings()->first();
        $this->assertNotNull($finding->level);
        $this->assertNotSame('', $finding->title);
        $this->assertNotSame('', $finding->fingerprint);
        $this->assertIsArray($finding->cves);
    }

    public function test_a_reparse_is_idempotent_and_does_not_duplicate_findings(): void
    {
        $report = $this->reportWithTelnet();

        (new ParseReport($report->id))->handle();
        $first = $report->findings()->count();

        // Re-parsing the same document must not pile up duplicate rows.
        $report->forceFill(['status' => ReportStatus::Received])->save();
        (new ParseReport($report->id))->handle();

        $this->assertSame($first, $report->findings()->count());
    }

    public function test_a_report_whose_document_is_gone_is_marked_failed(): void
    {
        $report = $this->reportWithTelnet();
        Storage::disk('local')->delete($report->payload_path);

        (new ParseReport($report->id))->handle();
        $report->refresh();

        $this->assertSame(ReportStatus::Failed, $report->status);
        $this->assertNotNull($report->parse_failure_reason);
        $this->assertSame(0, $report->findings()->count());
    }

    public function test_an_already_parsed_report_is_left_alone(): void
    {
        $report = $this->reportWithTelnet();
        (new ParseReport($report->id))->handle();
        $parsedAt = $report->refresh()->parsed_at;

        // A second run on an already-parsed report is a no-op (re-parse resets first).
        (new ParseReport($report->id))->handle();

        $this->assertSame(ReportStatus::Parsed, $report->refresh()->status);
        $this->assertEquals($parsedAt, $report->parsed_at);
    }
}
