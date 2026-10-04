<?php

namespace Tests\Feature\Api\V1;

use App\Enums\PayloadFormat;
use App\Enums\ReportStatus;
use App\Models\Device;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        return $this->send(json_encode($payload ?? $this->payload()), token: $token);
    }

    /**
     * Sends a body exactly as given, the way a probe does - bytes, not an array
     * a test client re-encodes on its way out.
     */
    private function send(string $body, ?string $encoding = null, ?string $token = self::TOKEN): TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        if ($encoding !== null) {
            $server['HTTP_CONTENT_ENCODING'] = $encoding;
        }

        return $this->call('POST', '/api/v1/reports', [], [], [], $server, $body);
    }

    private function storedDocument(?Report $report = null): string
    {
        return gzdecode(Storage::disk('local')->get(($report ?? Report::sole())->payload_path));
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

    public function test_it_stores_the_body_exactly_as_sent_alongside_its_checksum(): void
    {
        $this->activeDevice();

        // Whitespace and escaping a re-encoding would normalise away.
        $body = "{\n  \"report_id\": \"".self::REPORT_ID."\",\n  \"report\": {\"scan_time\": \"2026-08-16 13:38:12\", \"path\": \"\\/opt\\/rpi\"}\n}\n";

        $this->send($body)->assertValidResponse(201)
            ->assertJsonPath('data.payload_bytes', strlen($body))
            ->assertJsonPath('data.payload_sha256', hash('sha256', $body));

        $report = Report::sole();

        $this->assertSame($body, $this->storedDocument());
        $this->assertSame(PayloadFormat::Submission, $report->payload_format);
        $this->assertSame(['scan_time' => '2026-08-16 13:38:12', 'path' => '/opt/rpi'], $report->document());
    }

    public function test_it_accepts_a_gzip_compressed_body(): void
    {
        $this->activeDevice();

        $body = json_encode($this->payload());

        $this->send(gzencode($body), 'gzip')
            ->assertValidResponse(201)
            ->assertJsonPath('data.payload_bytes', strlen($body))
            ->assertJsonPath('data.payload_sha256', hash('sha256', $body));

        // Compression is transport only: what is kept is the uncompressed body.
        $this->assertSame($body, $this->storedDocument());
        $this->assertSame('2026-08-16 11:38:12', Report::sole()->scanned_at->toDateTimeString());
    }

    public function test_an_identity_encoding_is_a_plain_body(): void
    {
        $this->activeDevice();

        $this->send(json_encode($this->payload()), 'identity')->assertValidResponse(201);
    }

    public function test_it_refuses_an_encoding_other_than_gzip(): void
    {
        $this->activeDevice();

        $this->send(json_encode($this->payload()), 'br')
            ->assertValidResponse(415)
            ->assertJsonPath('code', 'unsupported_encoding');

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_body_that_does_not_decompress(): void
    {
        $this->activeDevice();

        $this->send('this is not gzip', 'gzip')
            ->assertValidResponse(422)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['body']]);

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_gzip_stream_cut_off_mid_way(): void
    {
        $this->activeDevice();

        $whole = gzencode(json_encode($this->payload()).str_repeat(' ', 4096));

        $this->send(substr($whole, 0, intdiv(strlen($whole), 2)), 'gzip')
            ->assertValidResponse(422)
            ->assertJsonStructure(['errors' => ['body']]);

        $this->assertSame(0, Report::count());
    }

    public function test_a_gzip_body_of_several_members_is_read_whole(): void
    {
        $this->activeDevice();

        $body = json_encode($this->payload());
        [$head, $tail] = [substr($body, 0, 40), substr($body, 40)];

        $this->send(gzencode($head).gzencode($tail), 'gzip')
            ->assertValidResponse(201)
            ->assertJsonPath('data.payload_bytes', strlen($body));

        $this->assertSame($body, $this->storedDocument());
    }

    public function test_bytes_after_the_end_of_the_gzip_stream_are_refused(): void
    {
        $this->activeDevice();

        $this->send(gzencode(json_encode($this->payload())).'garbage', 'gzip')
            ->assertValidResponse(422)
            ->assertJsonStructure(['errors' => ['body']]);

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_body_that_is_not_json(): void
    {
        $this->activeDevice();

        $this->send('{"report_id": "'.self::REPORT_ID.'", "report": {')
            ->assertValidResponse(422)
            ->assertJsonStructure(['errors' => ['body']]);

        $this->assertSame(0, Report::count());
    }

    public function test_it_rejects_a_body_that_is_a_json_list(): void
    {
        $this->activeDevice();

        $this->send('[{"report_id": "'.self::REPORT_ID.'"}]')
            ->assertValidResponse(422)
            ->assertJsonStructure(['errors' => ['body']]);

        $this->assertSame(0, Report::count());
    }

    public function test_it_records_when_the_scan_ran(): void
    {
        $this->activeDevice();

        $this->submit()->assertValidResponse(201);

        // 13:38:12 on the probe's own clock, kept as UTC so it can be compared
        // with received_at without anyone having to remember the offset.
        $this->assertSame('2026-08-16 11:38:12', Report::sole()->scanned_at->toDateTimeString());
    }

    public function test_a_report_without_a_readable_scan_time_is_still_stored(): void
    {
        $this->activeDevice();

        $payload = $this->payload();
        $payload['report']['scan_time'] = 'kiedys';

        $this->submit($payload)->assertValidRequest()->assertValidResponse(201);

        $report = Report::sole();

        $this->assertNull($report->scanned_at);
        $this->assertNull($report->deliverySeconds());
        $this->assertSame($payload['report'], $report->document());
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
        $this->assertSame([Report::sole()->payload_path], Storage::disk('local')->allFiles());
    }

    public function test_a_repeated_report_id_keeps_the_document_stored_first(): void
    {
        $this->activeDevice();

        $first = $this->submit()->assertValidResponse(201)->json('data');
        $stored = $this->storedDocument();

        $changed = $this->payload();
        $changed['report']['discovered_hosts_count'] = 99;

        // Different content under the same id is still a delivered run: the
        // probe gets 200 and the checksum of what is on file, not an error it
        // would retry for ever.
        $this->submit($changed)
            ->assertValidResponse(200)
            ->assertJsonPath('data.payload_sha256', $first['payload_sha256']);

        $this->assertSame($stored, $this->storedDocument());
    }

    /**
     * A report whose file could not be put in place must not be acknowledged:
     * the probe deletes what it was told is stored.
     */
    public function test_a_document_that_cannot_be_stored_is_not_acknowledged(): void
    {
        $this->activeDevice();

        // A directory where the file has to go makes the move fail.
        Storage::disk('local')->makeDirectory(Report::payloadPathFor(self::REPORT_ID).'/blocked');

        $this->submit()
            ->assertValidResponse(500)
            ->assertJsonPath('code', 'server_error');

        $this->assertSame(0, Report::count());
        $this->assertSame([], Storage::disk('local')->files('reports/incoming'));
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

    public function test_it_rejects_a_report_id_that_is_not_a_string(): void
    {
        $this->activeDevice();

        $this->send('{"report_id": 12345, "report": {}}')
            ->assertValidResponse(422)
            ->assertJsonStructure(['errors' => ['report_id']]);

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

    public function test_a_body_beyond_what_php_accepts_still_answers_in_the_envelope(): void
    {
        $this->activeDevice();

        $this->call('POST', '/api/v1/reports', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => (string) (2 * 1024 * 1024 * 1024),
            'HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN,
            'HTTP_ACCEPT' => 'application/json',
        ], '{}')
            ->assertValidResponse(413)
            ->assertJsonPath('code', 'payload_too_large');
    }

    public function test_the_limit_applies_to_the_uncompressed_document(): void
    {
        config(['pensec.reports.max_payload_bytes' => 64 * 1024]);

        $this->activeDevice();

        // A megabyte of one character gzips to about a kilobyte: small on the
        // wire, sixteen times the limit once inflated.
        $bomb = $this->payload();
        $bomb['report']['filler'] = str_repeat('x', 1024 * 1024);
        $compressed = gzencode(json_encode($bomb), 9);

        $this->assertLessThan(64 * 1024, strlen($compressed));

        $this->send($compressed, 'gzip')
            ->assertValidResponse(413)
            ->assertJsonPath('code', 'payload_too_large');

        $this->assertSame(0, Report::count());
    }

    /**
     * The reason this endpoint was rebuilt. A whole json_decode costs about 5.6
     * times the document, and the old path decoded it twice: a 40 MB report
     * needed well over 500 MB and died at memory_limit. Read as a stream and
     * never decoded, it costs the body plus a small, fixed overhead.
     */
    public function test_a_large_report_is_stored_without_decoding_it(): void
    {
        $this->activeDevice();

        $finding = ['template-id' => 'http-missing-security-headers', 'host' => '192.168.0.1', 'matched-at' => 'http://192.168.0.1:80', 'info' => ['severity' => 'info', 'name' => 'HTTP Missing Security Headers']];
        $findings = '['.implode(',', array_fill(0, 240_000, json_encode($finding))).']';
        $body = '{"report_id":"'.self::REPORT_ID.'","report":{"scan_time":"2026-08-16 13:38:12","nuclei_results":{"findings":'.$findings.'}}}';
        unset($findings);

        $this->assertGreaterThan(35 * 1024 * 1024, strlen($body));

        $compressed = gzencode($body, 1);
        $sha256 = hash('sha256', $body);
        $bytes = strlen($body);
        unset($body);

        $before = memory_get_usage();
        memory_reset_peak_usage();

        $this->send($compressed, 'gzip')
            ->assertValidResponse(201)
            ->assertJsonPath('data.payload_bytes', $bytes)
            ->assertJsonPath('data.payload_sha256', $sha256);

        $this->assertLessThan(2 * $bytes, memory_get_peak_usage() - $before);
        $this->assertSame('2026-08-16 11:38:12', Report::sole()->scanned_at->toDateTimeString());
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
