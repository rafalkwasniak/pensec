<?php

namespace Tests\Feature\Panel;

use App\Models\Device;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    /**
     * The whole point of the column: a report that sat on the probe for days
     * arrives with today's date at the top of the list, and without the scan
     * date beside it there is no way to tell that from a fresh badanie.
     */
    public function test_the_list_says_when_a_scan_ran_and_how_long_it_waited(): void
    {
        Report::factory()->create([
            'scanned_at' => '2026-08-19 16:11:00',
            'received_at' => '2026-08-23 13:43:35',
        ]);

        $this->get('/panel/reports')
            ->assertOk()
            ->assertSee('2026-08-19 16:11')
            ->assertSee('2026-08-23 13:43')
            ->assertSee('+3 d 21 h');
    }

    public function test_a_report_whose_probe_never_said_when_it_scanned_still_lists(): void
    {
        $report = Report::factory()->create(['scanned_at' => null]);

        $this->get('/panel/reports')
            ->assertOk()
            ->assertSee($report->report_uid)
            ->assertSee('nie podano');

        $this->get("/panel/reports/{$report->id}")
            ->assertOk()
            ->assertSee('sonda nie podała')
            ->assertSee('nie do ustalenia');
    }

    public function test_the_report_page_shows_how_long_the_document_waited(): void
    {
        $report = Report::factory()->create([
            'scanned_at' => '2026-08-26 07:06:00',
            'received_at' => '2026-08-26 19:23:13',
        ]);

        $this->get("/panel/reports/{$report->id}")
            ->assertOk()
            ->assertSee('2026-08-26 07:06:00 UTC')
            ->assertSee('+12 h 17 min');
    }

    public function test_the_report_body_is_served_separately_from_the_page(): void
    {
        $report = Report::factory()->create();

        $this->get("/panel/reports/{$report->id}")
            ->assertOk()
            ->assertDontSee('scan_time');

        $response = $this->get("/panel/reports/{$report->id}/payload")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Encoding', 'gzip');

        // Served compressed as stored; the browser does the inflating.
        $document = json_decode(gzdecode(file_get_contents($response->baseResponse->getFile()->getPathname())), true);

        $this->assertSame('2026-08-16 13:38:12', $document['scan_time']);
    }

    public function test_a_document_too_large_to_show_is_offered_as_a_file_instead(): void
    {
        config(['pensec.reports.preview_max_bytes' => 10]);

        $report = Report::factory()->create();

        $this->get("/panel/reports/{$report->id}")
            ->assertOk()
            ->assertDontSee('Pokaż treść')
            ->assertSee('za dużo, żeby');

        $this->get("/panel/reports/{$report->id}/payload")->assertStatus(413);
        $this->get("/panel/reports/{$report->id}/download")->assertOk();
    }

    public function test_a_report_can_be_downloaded_as_a_file(): void
    {
        $report = Report::factory()->create();

        $response = $this->get("/panel/reports/{$report->id}/download")->assertOk();

        $this->assertSame(
            "attachment; filename=pensec-report-{$report->report_uid}.json",
            $response->headers->get('Content-Disposition'),
        );
        $this->assertSame(
            gzdecode(Storage::disk('local')->get($report->payload_path)),
            $response->streamedContent(),
        );
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
