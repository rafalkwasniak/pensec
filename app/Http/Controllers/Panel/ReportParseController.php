<?php

namespace App\Http\Controllers\Panel;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ParseReport;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * The parse buttons for a report, end to end.
 *
 * Every new report is parsed once at intake; this is the manual path for the
 * rest: a report that has not been parsed yet, one whose parse failed, and a
 * deliberate re-parse after ReportFacts has learned to read a report better.
 * Like the narrative buttons, these only queue the work and return at once.
 */
class ReportParseController extends Controller
{
    /** Queues a parse for a report that is not parsed yet (or whose parse failed). */
    public function store(Report $report): RedirectResponse
    {
        if ($report->status === ReportStatus::Parsed) {
            return back()->with('status', 'Ten raport jest już przeanalizowany.');
        }

        if ($report->status === ReportStatus::Parsing) {
            return back()->with('status', 'Analiza już trwa.');
        }

        $report->forceFill(['status' => ReportStatus::Received, 'parse_failure_reason' => null])->save();

        ParseReport::dispatch($report->id);

        return back()->with('status', 'Raport trafił do kolejki analizy. Strona odświeży się sama.');
    }

    /** Throws away the derived findings and summary and queues a fresh pass. */
    public function reparse(Report $report): RedirectResponse
    {
        $report->findings()->delete();

        $report->forceFill([
            'status' => ReportStatus::Received,
            'summary' => null,
            'parsed_at' => null,
            'parse_failure_reason' => null,
        ])->save();

        ParseReport::dispatch($report->id);

        return back()->with('status', 'Raport zostanie przeanalizowany od nowa.');
    }

    /** What the page polls while a parse runs. */
    public function status(Report $report): JsonResponse
    {
        return response()->json([
            'status' => $report->status->value,
            'label' => $report->status->label(),
            'in_progress' => $report->status->inProgress(),
            'parsed' => $report->status === ReportStatus::Parsed,
            'failure_reason' => $report->parse_failure_reason,
        ]);
    }
}
