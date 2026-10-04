<?php

namespace Database\Factories;

use App\Enums\PayloadFormat;
use App\Enums\ReportStatus;
use App\Models\Device;
use App\Models\Report;
use App\Support\ScanTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    private const DOCUMENT = ['scan_time' => '2026-08-16 13:38:12', 'hosts' => ['192.168.0.1']];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $document = json_encode(self::DOCUMENT);
        $uid = fake()->uuid();

        return [
            'device_id' => Device::factory(),
            'report_uid' => $uid,
            'status' => ReportStatus::Received,
            'received_at' => now(),
            'scanned_at' => ScanTime::parse('2026-08-16 13:38:12'),
            'payload_bytes' => strlen($document),
            'payload_sha256' => hash('sha256', $document),
            'payload_path' => Report::payloadPathFor($uid),
            'payload_format' => PayloadFormat::Report,
            'source_ip' => fake()->ipv4(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Report $report): void {
            if (! Storage::disk('local')->exists($report->payload_path)) {
                Storage::disk('local')->put($report->payload_path, gzencode(json_encode(self::DOCUMENT)));
            }
        });
    }

    /**
     * Replaces the stored document, keeping the size, checksum and scan date
     * honest so a test can never end up asserting against a report that
     * describes itself wrongly.
     *
     * @param  array<string, mixed>  $document
     */
    public function withDocument(array $document): static
    {
        $encoded = json_encode($document);

        return $this->state([
            'payload_bytes' => strlen($encoded),
            'payload_sha256' => hash('sha256', $encoded),
            'scanned_at' => ScanTime::parse($document['scan_time'] ?? null),
        ])->afterCreating(function (Report $report) use ($encoded): void {
            Storage::disk('local')->put($report->payload_path, gzencode($encoded));
        });
    }
}
