<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Device;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $document = json_encode(['scan_time' => '2026-08-16 13:38:12', 'hosts' => ['192.168.0.1']]);

        return [
            'device_id' => Device::factory(),
            'report_uid' => fake()->uuid(),
            'status' => ReportStatus::Received,
            'received_at' => now(),
            'payload_bytes' => strlen($document),
            'payload_sha256' => hash('sha256', $document),
            'source_ip' => fake()->ipv4(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Report $report): void {
            $document = json_encode(['scan_time' => '2026-08-16 13:38:12', 'hosts' => ['192.168.0.1']]);

            $report->payload()->create(['payload' => $document]);
        });
    }

    /**
     * Replaces the stored document, keeping the size and checksum honest so a
     * test can never end up asserting against a report that describes itself
     * wrongly.
     *
     * @param  array<string, mixed>  $document
     */
    public function withDocument(array $document): static
    {
        $encoded = json_encode($document);

        return $this->state([
            'payload_bytes' => strlen($encoded),
            'payload_sha256' => hash('sha256', $encoded),
        ])->afterCreating(function (Report $report) use ($encoded): void {
            $report->payload()->update(['payload' => $encoded]);
            $report->unsetRelation('payload');
        });
    }
}
