<?php

namespace Tests\Feature\Panel;

use App\Models\Device;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportBrowsingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_it_lists_reports_from_every_device(): void
    {
        $first = Report::factory()->create(['device_id' => Device::factory()->create(['name' => 'Sonda A'])]);
        $second = Report::factory()->create(['device_id' => Device::factory()->create(['name' => 'Sonda B'])]);

        $this->get('/panel/reports')
            ->assertOk()
            ->assertSee('Sonda A')
            ->assertSee('Sonda B')
            ->assertSee($first->report_uid)
            ->assertSee($second->report_uid);
    }

    public function test_it_can_show_reports_of_one_device_only(): void
    {
        $device = Device::factory()->create(['name' => 'Sonda A']);
        $mine = Report::factory()->create(['device_id' => $device->id]);
        $other = Report::factory()->create();

        $this->get("/panel/reports?device={$device->id}")
            ->assertOk()
            ->assertSee($mine->report_uid)
            ->assertDontSee($other->report_uid);
    }

    public function test_it_shows_the_system_data_of_a_report(): void
    {
        $report = Report::factory()->create([
            'device_id' => Device::factory()->create(['name' => 'Sonda magazyn']),
            'source_ip' => '192.168.0.107',
        ]);

        $this->get("/panel/reports/{$report->id}")
            ->assertOk()
            ->assertSee('Sonda magazyn')
            ->assertSee($report->report_uid)
            ->assertSee($report->payload_sha256)
            ->assertSee('192.168.0.107');
    }

    public function test_the_report_body_is_served_separately_from_the_page(): void
    {
        $report = Report::factory()->create();

        $this->get("/panel/reports/{$report->id}")
            ->assertOk()
            ->assertDontSee('scan_time');

        $this->get("/panel/reports/{$report->id}/payload")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('scan_time', '2026-08-16 13:38:12');
    }

    public function test_a_report_can_be_downloaded_as_a_file(): void
    {
        $report = Report::factory()->create();

        $response = $this->get("/panel/reports/{$report->id}/download")->assertOk();

        $this->assertSame(
            "attachment; filename=pensec-report-{$report->report_uid}.json",
            $response->headers->get('Content-Disposition'),
        );
        $this->assertSame($report->payload->payload, $response->streamedContent());
    }

    public function test_a_guest_cannot_reach_a_report(): void
    {
        $this->app['auth']->logout();

        $report = Report::factory()->create();

        $this->get("/panel/reports/{$report->id}")->assertRedirect('/panel/login');
        $this->get("/panel/reports/{$report->id}/payload")->assertRedirect('/panel/login');
        $this->get("/panel/reports/{$report->id}/download")->assertRedirect('/panel/login');
    }
}
