<?php

namespace App\Http\Controllers\Panel;

use App\Enums\NarrativeStatus;
use App\Enums\NarrativeVariant;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportNarrative;
use App\Models\Report;
use App\Models\ReportNarrative;
use App\Services\ReportFacts;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The two report buttons, end to end.
 *
 * Writing and rendering are separate on purpose. `store` only queues the work
 * and returns immediately; `show` renders a PDF from what is already on file
 * and never waits on a model. That split is why a click cannot hang the panel,
 * and why a second download costs nothing.
 */
class ReportNarrativeController extends Controller
{
    /** Queues generation, or does nothing if the text is already on file. */
    public function store(Report $report, NarrativeVariant $variant): RedirectResponse
    {
        $narrative = ReportNarrative::firstOrCreate(
            ['report_id' => $report->id, 'variant' => $variant],
            ['status' => NarrativeStatus::Pending],
        );

        if ($narrative->isReady()) {
            return back()->with('status', 'Ten raport jest już gotowy do pobrania.');
        }

        if ($narrative->status->inProgress() && ! $narrative->wasRecentlyCreated) {
            return back()->with('status', 'Ten raport już się generuje.');
        }

        $narrative->fill(['status' => NarrativeStatus::Pending, 'failure_reason' => null])->save();

        GenerateReportNarrative::dispatch($report->id, $variant);

        return back()->with('status', 'Raport trafił do kolejki. Damy znać, gdy będzie gotowy.');
    }

    /** Throws away what is on file and queues a fresh pass. */
    public function regenerate(Report $report, NarrativeVariant $variant): RedirectResponse
    {
        ReportNarrative::updateOrCreate(
            ['report_id' => $report->id, 'variant' => $variant],
            ['status' => NarrativeStatus::Pending, 'content' => null, 'failure_reason' => null, 'generated_at' => null],
        );

        GenerateReportNarrative::dispatch($report->id, $variant);

        return back()->with('status', 'Raport zostanie wygenerowany od nowa.');
    }

    /** What the page polls while a job runs. */
    public function status(Report $report, NarrativeVariant $variant): JsonResponse
    {
        $narrative = $report->narrative($variant);

        return response()->json([
            'status' => $narrative->status->value,
            'label' => $narrative->status->label(),
            'in_progress' => $narrative->status->inProgress(),
            'ready' => $narrative->isReady(),
            'failure_reason' => $narrative->failure_reason,
        ]);
    }

    public function show(Report $report, NarrativeVariant $variant): Response
    {
        $narrative = $report->narrative($variant);

        abort_unless($narrative->isReady(), 404);

        $report->loadMissing('device', 'payload');

        $pdf = Pdf::loadView('pdf.report', [
            'report' => $report,
            'variant' => $variant,
            'narrative' => $narrative,
            'facts' => ReportFacts::from($report->document()),
        ])->setPaper('a4');

        return $pdf->download(sprintf(
            'pensec-raport-%s-%s.pdf',
            $variant->slug(),
            substr($report->report_uid, 0, 8),
        ));
    }
}
