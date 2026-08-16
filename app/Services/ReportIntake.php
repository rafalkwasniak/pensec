<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\Device;
use App\Models\Report;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReportIntake
{
    /**
     * Stores one scan run. Idempotent on the report id: the first submission
     * wins, later ones return what is already stored.
     *
     * @param  array<string, mixed>  $payload
     * @return array{report: Report, stored: bool}
     */
    public function store(Device $device, string $reportUid, array $payload, ?string $sourceIp): array
    {
        $existing = Report::where('report_uid', $reportUid)->first();

        if ($existing !== null) {
            return ['report' => $existing, 'stored' => false];
        }

        // Re-encoding is what makes the stored document canonical: the device
        // may send any whitespace or escaping, and everything downstream
        // compares checksums of what we stored.
        $document = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $report = DB::transaction(function () use ($device, $reportUid, $document, $sourceIp): Report {
                $report = Report::create([
                    'device_id' => $device->id,
                    'report_uid' => $reportUid,
                    'status' => ReportStatus::Received,
                    'received_at' => now(),
                    'payload_bytes' => strlen($document),
                    'payload_sha256' => hash('sha256', $document),
                    'source_ip' => $sourceIp,
                ]);

                $report->payload()->create(['payload' => $document]);

                return $report;
            });
        } catch (QueryException $exception) {
            // Two submissions of the same run raced past the lookup above. The
            // unique index settled it; whoever lost reads the winner's row.
            $winner = Report::where('report_uid', $reportUid)->first();

            if ($winner === null) {
                throw $exception;
            }

            return ['report' => $winner, 'stored' => false];
        }

        return ['report' => $report, 'stored' => true];
    }
}
