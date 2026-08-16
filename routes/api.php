<?php

use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\DocumentationController;
use Illuminate\Support\Facades\Route;

Route::get('openapi.yaml', [DocumentationController::class, 'contract'])->name('api.contract');

Route::prefix('v1')->group(function (): void {
    Route::post('reports', [ReportController::class, 'store'])
        ->middleware(['device.auth', 'throttle:reports', 'report.size'])
        ->name('api.v1.reports.store');
});
