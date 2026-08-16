<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ReportStatus;
use App\Models\Device;
use App\Models\Report;
use App\Models\ReportPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spectator\Spectator;
use Tests\TestCase;

class SubmitReportTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

    private const REPORT_ID = '0f1d4e9c-6a2b-4f7e-9d31-5c8ba0f2e7a4';

    protected function setUp(): void
    {
        parent::setUp();

        Spectator::using('openapi.yaml');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $reportId = self::REPORT_ID): array
    {
        return [
            'report_id' => $reportId,
            'report' => [
                'scan_time' => '2026-08-16 13:38:12',
                'orchestrator_ip' => '192.168.0.107',
                'discovered_hosts_count' => 1,
                'hosts' => ['192.168.0.1'],
                'nuclei_results' => [],
            ],
        ];
    }

    private function activeDevice(): Device
    {
        return Device::factory()->withToken(self::TOKEN)->create();
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function submit(?array $payload = null, ?string $token = self::TOKEN): TestResponse
    {
        $headers = $token === null ? [] : ['Authorization' => 'Bearer '.$token];

        return $this->postJson('/api/v1/reports', $payload ?? $this->payload(), $headers);
    }

    public function test_it_stores_a_submitted_report(): void
    {
        $device = $this->activeDevice();

        $response = $this->submit();

        $response->assertValidRequest()
            ->assertValidResponse(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_id', self::REPORT_ID)
            ->assertJsonPath('data.status', ReportStatus::Received->value);

        $report = Report::sole();

        $this->assertSame($device->id, $report->device_id);
        $this->assertSame(self::REPORT_ID, $report->report_uid);
        $this->assertSame(ReportStatus::Received, $report->status);
    }

    public function test_it_stores_the_raw_document_alongside_its_checksum(): void
    {
        $this->activeDevice();

        $this->submit()->assertValidResponse(201);

        $report = Report::sole();
        $stored = ReportPayload::sole()->payload;

        $this->assertSame($this->payload()['report'], json_decode($stored, true));
        $this->assertSame(strlen($stored), $report->payload_bytes);
        $this->assertSame(hash('sha256', $stored), $report->payload_sha256);
    }

    public function test_it_records_when_the_device_last_reported(): void
    {
        $device = $this->activeDevice();

        $this->assertNull($device->last_seen_at);

        $this->submit()->assertValidResponse(201);

        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_a_repeated_report_id_is_accepted_without_storing_it_twice(): void
    {
        $this->activeDevice();

        $this->submit()->assertValidResponse(201);

        $second = $this->submit();

        $second->assertValidRequest()
            ->assertValidResponse(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_id', self::REPORT_ID);

        $this->assertSame(1, Report::count());
        $this->assertSame(1, ReportPayload::count());
    }

    public function test_a_repeated_report_id_keeps_the_document_stored_first(): void
    {
        $this->activeDevice();

        $this->submit()->assertValidResponse(201);
        $first = ReportPayload::sole()->payload;

        $changed = $this->payload();
        $changed['report']['discovered_hosts_count'] = 99;

        $this->submit($changed)->assertValidResponse(200);

        $this->assertSame($first, ReportPayload::sole()->payload);
    }

    public function test_a_second_run_of_the_same_device_is_a_separate_report(): void
    {
        $this->activeDevice();

        $this->submit()->assertValidResponse(201);
        $this->submit($this->payload('7c9e6679-7425-40de-944b-e07fc1f90ae7'))->assertValidResponse(201);

        $this->assertSame(2, Report::count());
    }

    public function test_it_rejects_a_request_without_a_token(): void
    {
        $this->activeDevice();

        $this->submit(token: null)
            ->assertValidResponse(401)
            ->assertJsonPath('code', 'device_token_missing');

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_an_unknown_token(): void
    {
        $this->activeDevice();

        $this->submit(token: str_repeat('f', 64))
            ->assertValidResponse(401)
            ->assertJsonPath('code', 'device_token_invalid');

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_disabled_device(): void
    {
        Device::factory()->withToken(self::TOKEN)->disabled()->create();

        $this->submit()
            ->assertValidResponse(403)
            ->assertJsonPath('code', 'device_disabled');

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_report_id_that_is_not_a_uuid(): void
    {
        $this->activeDevice();

        $this->submit($this->payload('not-a-uuid'))
            ->assertValidResponse(422)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['report_id']]);

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_missing_report(): void
    {
        $this->activeDevice();

        $this->submit(['report_id' => self::REPORT_ID])
            ->assertValidResponse(422)
            ->assertJsonPath('code', 'validation_failed');

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_report_that_is_a_json_list(): void
    {
        $this->activeDevice();

        $this->submit(['report_id' => self::REPORT_ID, 'report' => ['first', 'second']])
            ->assertValidResponse(422)
            ->assertJsonPath('code', 'validation_failed');

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_report_larger_than_the_configured_limit(): void
    {
        config(['pensec.reports.max_payload_bytes' => 1024]);

        $this->activeDevice();

        $oversized = $this->payload();
        $oversized['report']['filler'] = str_repeat('x', 2048);

        $this->submit($oversized)
            ->assertValidResponse(413)
            ->assertJsonPath('code', 'payload_too_large');

        $this->assertSame(0, Report::count());
    }

    public function test_it_throttles_a_device_that_submits_too_often(): void
    {
        config(['pensec.reports.rate_limit_per_minute' => 2]);

        $this->activeDevice();

        $this->submit($this->payload('11111111-1111-4111-8111-111111111111'))->assertValidResponse(201);
        $this->submit($this->payload('22222222-2222-4222-8222-222222222222'))->assertValidResponse(201);

        $this->submit($this->payload('33333333-3333-4333-8333-333333333333'))
            ->assertValidResponse(429)
            ->assertJsonPath('code', 'rate_limit_exceeded');

        $this->assertSame(2, Report::count());
    }
}
