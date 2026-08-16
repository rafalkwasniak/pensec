<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceReportSizeLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $limit = config('pensec.reports.max_payload_bytes');

        // Content-Length is checked first because it costs nothing; the body is
        // measured afterwards, since the header is client-supplied and a chunked
        // request carries none at all.
        $declared = $request->header('Content-Length');

        if (($declared !== null && (int) $declared > $limit) || strlen($request->getContent()) > $limit) {
            return ApiResponse::error(
                ApiErrorCode::PayloadTooLarge,
                __('api.errors.payload_too_large'),
                413,
            );
        }

        return $next($request);
    }
}
