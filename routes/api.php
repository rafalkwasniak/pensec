<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('reports', [ReportController::class, 'store'])
        ->middleware(['device.auth', 'throttle:reports', 'report.size'])
        ->name('api.v1.reports.store');
});
