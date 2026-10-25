<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A read-only view of one report for the client who owns its device. The
 * findings query mirrors Panel\ReportController@show; the view reuses the shared
 * read-only analysis partial and drops every admin control.
 */
class ReportController extends Controller
{
    public function show(Request $request, Report $report): View
    {
        $this->authorizeReport($request, $report);

        $report->loadMissing('device', 'narratives');

        $level = $request->string('level')->toString() ?: null;
        $source = $request->string('source')->toString() ?: null;
        $search = $request->string('q')->toString() ?: null;

        $findings = $report->findings()
            ->when($level, fn ($query) => $query->where('level', $level))
            ->when($source, fn ($query) => $query->where('source', $source))
            ->when($search, fn ($query) => $query->where(
                fn ($inner) => $inner->where('title', 'like', '%'.$search.'%')->orWhere('ip', 'like', '%'.$search.'%'),
            ))
            ->orderByRaw("CASE level WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('client.report', [
            'report' => $report,
            'findings' => $findings,
            'findingLevels' => $report->findings()->distinct()->orderBy('level')->pluck('level'),
            'findingSources' => $report->findings()->distinct()->orderBy('source')->pluck('source'),
            'filterLevel' => $level,
            'filterSource' => $source,
            'filterQuery' => $search,
        ]);
    }

    /** A client may only ever reach a report produced by one of their own devices. */
    private function authorizeReport(Request $request, Report $report): void
    {
        abort_unless($report->device?->client_id === $request->user('client')->id, 404);
    }
}
