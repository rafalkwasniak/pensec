<?php

namespace App\Services;

use App\Enums\PayloadFormat;
use App\Enums\ReportStatus;
use App\Models\Device;
use App\Models\Report;
use App\Support\ScanTime;
use App\Support\UploadedReport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ReportIntake
{
    /**
     * Stores one scan run. Idempotent on the report id: the first submission
     * wins, later ones return what is already stored - whatever they carry.
     *
     * The document is written as gzip to a scratch name first and renamed into
     * place only once its row exists, so a file under a report's name always
     * belongs to that report.
     *
     * @return array{report: Report, stored: bool}
     */
    public function store(Device $device, string $reportUid, UploadedReport $upload, ?string $sourceIp): array
    {
        $existing = Report::where('report_uid', $reportUid)->first();

        if ($existing !== null) {
            return ['report' => $existing, 'stored' => false];
        }

        $disk = Storage::disk('local');
        $scratch = 'reports/incoming/'.Str::uuid().'.json.gz';
        $disk->makeDirectory('reports/incoming');

        // Through the zlib wrapper rather than gzencode(), which would hold a
        // compressed copy of the whole document in memory as well. A short
        // write - a full disk - would otherwise store a truncated file under a
        // checksum that describes the whole one.
        $written = file_put_contents('compress.zlib://'.$disk->path($scratch), $upload->document);

        if ($written !== $upload->bytes()) {
            $disk->delete($scratch);

            throw new RuntimeException("Could not write the document of report {$reportUid} to {$scratch}.");
        }

        try {
            $report = DB::transaction(function () use ($device, $reportUid, $upload, $sourceIp, $disk, $scratch): Report {
                $report = Report::create([
                    'device_id' => $device->id,
                    'report_uid' => $reportUid,
                    'status' => ReportStatus::Received,
                    'received_at' => now(),
                    'scanned_at' => ScanTime::parse($upload->scanTime),
                    'payload_bytes' => $upload->bytes(),
                    'payload_sha256' => $upload->sha256,
                    'payload_path' => Report::payloadPathFor($reportUid),
                    'payload_format' => PayloadFormat::Submission,
                    'source_ip' => $sourceIp,
                ]);

                // The local disk does not throw; a failed move must, or the row
                // commits pointing at nothing and the probe is told "stored".
                if (! $disk->move($scratch, $report->payload_path)) {
                    throw new RuntimeException("Could not move the document of report {$reportUid} into {$report->payload_path}.");
                }

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
        } finally {
            // Gone already when the report was stored; left behind otherwise.
            $disk->delete($scratch);
        }

        return ['report' => $report, 'stored' => true];
    }
}
