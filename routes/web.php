<?php

use App\Http\Controllers\Panel\AccountController;
use App\Http\Controllers\Panel\DeviceController;
use App\Http\Controllers\Panel\DeviceTokenController;
use App\Http\Controllers\Panel\PasswordController;
use App\Http\Controllers\Panel\ReportController;
use App\Http\Controllers\Panel\SessionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::prefix('panel')->name('panel.')->middleware('panel.locale')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [SessionController::class, 'create'])->name('login');
        Route::post('login', [SessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

        Route::redirect('/', '/panel/devices')->name('home');

        Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::get('devices/new', [DeviceController::class, 'create'])->name('devices.create');
        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::get('devices/{device}', [DeviceController::class, 'edit'])->name('devices.edit');
        Route::post('devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::post('devices/{device}/delete', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::post('devices/{device}/token', [DeviceTokenController::class, 'store'])->name('devices.token');

        Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
        Route::post('account', [AccountController::class, 'update'])->name('account.update');
        Route::post('account/password', [PasswordController::class, 'update'])->name('account.password');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{report}/payload', [ReportController::class, 'payload'])->name('reports.payload');
        Route::get('reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    });
});
