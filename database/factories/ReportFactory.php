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
}
