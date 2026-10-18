<?php

namespace App\Jobs;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportFinding;
use App\Services\ReportFacts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Derives the findings and a summary from a stored report and persists them.
 *
 * Runs on the queue because the document can be hundreds of megabytes and the
 * read, though bounded, is not a thing to do inside the submit request. It is
 * dispatched once, at intake, for every newly stored report, and again by hand
 * from the panel when a report is re-parsed.
 *
 * Idempotent: a report already parsed is left alone (re-parsing clears it
 * first). A parse that cannot finish leaves the report `failed` with a readable
 * reason and never touches the stored document, which stays the source of truth.
 */
class ParseReport implements ShouldQueue
{
    use Queueable;

    /** One attempt: the document does not change, so a retry would only repeat a failure. */
    public int $tries = 1;

    /** Comfortably inside the worker timeout (330) for even a large report. */
    public int $timeout = 300;

    public function __construct(public readonly int $reportId) {}

    public function handle(): void
    {
        $report = Report::find($this->reportId);

        if ($report === null) {
            Log::warning('Parse job for a report that is gone', ['report_id' => $this->reportId]);

            return;
        }

        // Already done. Re-parsing is a deliberate act that resets the row first.
        if ($report->status === ReportStatus::Parsed) {
            return;
        }

        $report->forceFill(['status' => ReportStatus::Parsing, 'parse_failure_reason' => null])->save();

        try {
            // Bounded reading: never Report::document() here - a real report
            // does not fit in memory decoded whole.
            $facts = ReportFacts::forReport($report);
        } catch (Throwable $exception) {
            $this->fail($report, 'Nie udało się odczytać dokumentu: '.$exception->getMessage());

            return;
        }

        // Collapse findings that carry the same identity, so the unique index
        // on (report_id, fingerprint) never rejects an insert; the list from
        // ReportFacts is positional and may repeat an identical finding.
        $rows = [];

        foreach ($facts['findings'] as $finding) {
            $fingerprint = ReportFinding::fingerprintFor($finding);

            $rows[$fingerprint] ??= [
                'report_id' => $report->id,
                'level' => $finding['level'],
                'title' => $finding['title'],
                'ip' => $finding['ip'],
                'where' => $finding['where'],
                'source' => $finding['source'],
                'cves' => json_encode($finding['cves']),
                'confirmed' => $finding['confirmed'],
                'note' => $finding['note'],
                'fingerprint' => $fingerprint,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $summary = [
            'severity_counts' => $facts['severity_counts'],
            'totals' => $facts['totals'],
            'plan' => $facts['plan'],
            'actionable' => $facts['actionable'],
            'personal_data' => $facts['personal_data'],
            'scan' => $facts['scan'],
            'gaps' => $facts['gaps'],
        ];

        DB::transaction(function () use ($report, $rows, $summary): void {
            $report->findings()->delete();

            foreach (array_chunk(array_values($rows), 500) as $chunk) {
                ReportFinding::insert($chunk);
            }

            $report->forceFill([
                'status' => ReportStatus::Parsed,
                'summary' => $summary,
                'parsed_at' => now(),
                'parse_failure_reason' => null,
            ])->save();
        });
    }

    /** An unhandled throw still has to leave a status a person can read. */
    public function failed(?Throwable $exception): void
    {
        Report::where('id', $this->reportId)
            ->where('status', ReportStatus::Parsing)
            ->update([
                'status' => ReportStatus::Failed,
                'parse_failure_reason' => 'Zadanie przerwane: '.($exception?->getMessage() ?? 'nieznany powód'),
            ]);
    }

    private function fail(Report $report, string $reason): void
    {
        Log::warning('Report parsing failed', ['report_id' => $this->reportId, 'reason' => $reason]);

        $report->forceFill([
            'status' => ReportStatus::Failed,
            'parse_failure_reason' => $reason,
        ])->save();
    }
}
