<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
            'lagWarningSeconds' => $this->lagWarningSeconds(),
        ]);
    }

    public function show(Report $report): View
    {
        return view('panel.reports.show', [
            'report' => $report->load('device', 'narratives'),
            'lagWarningSeconds' => $this->lagWarningSeconds(),
            'previewable' => $report->payload_bytes <= config('pensec.reports.preview_max_bytes'),
        ]);
    }

    /**
     * Serves the stored document on its own, so opening a report in the panel
     * never has to carry megabytes of scan output with the page.
     */
    public function payload(Report $report): BinaryFileResponse
    {
        abort_if($report->payload_bytes > config('pensec.reports.preview_max_bytes'), 413);

        // The gzip file goes out as it is stored and the browser inflates it, so
        // the server never decompresses a document just to show it.
        return response()->file($report->payloadFile(), [
            'Content-Type' => 'application/json',
            'Content-Encoding' => 'gzip',
        ]);
    }

    /**
     * The gap between a scan and its arrival that the panel starts calling
     * out. Read here rather than in the views so both pages draw the line in
     * the same place.
     */
    private function lagWarningSeconds(): int
    {
        return (int) config('pensec.reports.delivery_lag_warning_minutes') * 60;
    }

    public function download(Report $report): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($report): void {
                $stream = $report->payloadStream();
                fpassthru($stream);
                fclose($stream);
            },
            "pensec-report-{$report->report_uid}.json",
            ['Content-Type' => 'application/json'],
        );
    }
}
