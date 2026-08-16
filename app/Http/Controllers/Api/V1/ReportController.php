<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateDevice;
use App\Http\Requests\Api\V1\SubmitReportRequest;
use App\Models\Report;
use App\Services\ReportIntake;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function store(SubmitReportRequest $request, ReportIntake $intake): JsonResponse
    {
        $result = $intake->store(
            AuthenticateDevice::device($request),
            $request->reportId(),
            $request->report(),
            $request->ip(),
        );

        return ApiResponse::success(
            $result['stored'] ? __('api.reports.stored') : __('api.reports.already_stored'),
            Report::card($result['report']),
            $result['stored'] ? 201 : 200,
        );
    }
}
