<?php

namespace Tests\Feature\Panel;

use App\Enums\ReportStatus;
use App\Jobs\ParseReport;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReportParseTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function parsedReport(): Report
    {
        $report = Report::factory()->withDocument([
            'scan_time' => '2026-08-16 13:38:12',
            'hosts' => ['192.0.2.20'],
            'nmap_structured' => ['192.0.2.20' => ['status' => 'ok', 'ports' => [
                ['protocol' => 'tcp', 'port' => 23, 'state' => 'open', 'name' => 'telnet'],
            ]]],
        ])->create();

        (new ParseReport($report->id))->handle();

        return $report->refresh();
    }

    public function test_the_report_page_shows_the_analysis_and_its_findings(): void
    {
        $this->signIn();
        $report = $this->parsedReport();

        $this->get("/panel/reports/{$report->id}")
            ->assertOk()
            ->assertSee('Analiza badania')
            ->assertSee('192.0.2.20');
    }

    public function test_findings_can_be_filtered_by_level(): void
    {
        $this->signIn();
        $report = $this->parsedReport();

        // A level that no finding carries hides them all; the empty-filter note shows.
        $this->get("/panel/reports/{$report->id}?level=info")
            ->assertOk()
            ->assertSee('nie pasuje do filtra');
    }

    public function test_the_parse_status_endpoint_answers_json(): void
    {
        $this->signIn();
        $report = $this->parsedReport();

        $this->getJson("/panel/reports/{$report->id}/parse/status")
            ->assertOk()
            ->assertJson([
                'status' => ReportStatus::Parsed->value,
                'in_progress' => false,
                'parsed' => true,
            ]);
    }

    public function test_reparse_clears_findings_and_queues_a_fresh_pass(): void
    {
        $this->signIn();
        $report = $this->parsedReport();
        $this->assertGreaterThan(0, $report->findings()->count());

        Queue::fake();

        $this->post("/panel/reports/{$report->id}/parse/reparse")->assertRedirect();

        Queue::assertPushed(ParseReport::class);
        $report->refresh();
        $this->assertSame(ReportStatus::Received, $report->status);
        $this->assertSame(0, $report->findings()->count());
    }

    public function test_a_guest_cannot_reach_the_parse_actions(): void
    {
        $report = Report::factory()->create();

        $this->post("/panel/reports/{$report->id}/parse")->assertRedirect('/panel/login');
        $this->get("/panel/reports/{$report->id}/parse/status")->assertRedirect('/panel/login');
    }
}
