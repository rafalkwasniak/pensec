<?php

namespace App\Http\Controllers\Client;

use App\Enums\NarrativeVariant;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportFacts;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves a ready report PDF to the client who owns its device. Mirrors
 * Panel\ReportNarrativeController@show but is scoped by the device's client and
 * never writes: it reads the stored facts, or derives them without persisting
 * (Panel's facts() lazily saves - not wanted on a client download).
 */
class ReportNarrativeController extends Controller
{
    public function show(Request $request, Report $report, NarrativeVariant $variant): Response
    {
        abort_unless($report->device?->client_id === $request->user('client')->id, 404);

        $report->loadMissing('device', 'narratives');
        $narrative = $report->narrative($variant);

        abort_unless($narrative->isReady(), 404);

        $narrative->setRelation('report', $report);
        $facts = $narrative->facts ?? ReportFacts::forReport($report);

        $pdf = Pdf::loadView($variant->isRemediation() ? 'pdf.remediation' : 'pdf.report', [
            'report' => $report,
            'variant' => $variant,
            'narrative' => $narrative,
            'facts' => $facts,
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true);

        return $pdf->download(sprintf(
            'pensec-raport-%s-%s.pdf',
            $variant->slug(),
            substr($report->report_uid, 0, 8),
        ));
    }
}
