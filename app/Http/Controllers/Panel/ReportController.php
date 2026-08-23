<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $deviceId = $request->integer('device') ?: null;

        $reports = Report::with('device')
            ->when($deviceId, fn ($query) => $query->where('device_id', $deviceId))
            ->latest('received_at')
            ->paginate(25)
            ->withQueryString();

        return view('panel.reports.index', [
            'reports' => $reports,
            'devices' => Device::orderBy('name')->get(),
            'selectedDevice' => $deviceId,
        ]);
    }

    public function show(Report $report): View
    {
        return view('panel.reports.show', ['report' => $report->load('device', 'narratives')]);
    }

    /**
     * Serves the stored document on its own, so opening a report in the panel
     * never has to carry megabytes of scan output with the page.
     */
    public function payload(Report $report): Response
    {
        return response($report->payload->payload, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function download(Report $report): StreamedResponse
    {
        $document = $report->payload->payload;

        return response()->streamDownload(
            fn () => print ($document),
            "pensec-report-{$report->report_uid}.json",
            ['Content-Type' => 'application/json'],
        );
    }
}
