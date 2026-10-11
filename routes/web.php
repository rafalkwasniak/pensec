<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\Panel\AccountController;
use App\Http\Controllers\Panel\AdministratorController;
use App\Http\Controllers\Panel\DeviceController;
use App\Http\Controllers\Panel\DeviceTokenController;
use App\Http\Controllers\Panel\PasswordController;
use App\Http\Controllers\Panel\ReportController;
use App\Http\Controllers\Panel\ReportNarrativeController;
use App\Http\Controllers\Panel\SessionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/tests', 'tests.tests')->name('tests');
Route::view('/contact', 'contact.contact')->name('contact');

Route::view('/course', 'course.landing')->name('course');
Route::view('/course/ksiazka', 'course.book')->name('course.book');
Route::get('/course/start', [CourseController::class, 'start'])->name('course.start');

Route::get('/course/exam', [ExamController::class, 'show'])->name('course.exam');
Route::post('/course/exam', [ExamController::class, 'submit'])
    ->middleware('throttle:'.config('pensec.course.exam_rate_limit_per_minute').',1')
    ->name('course.exam.submit');

// This URL is both the certificate and its verification: it binds to the
// public_id, so an id that was never issued is a 404. Registered before the
// {slug} lesson route, which would otherwise swallow "certificate/<id>".
Route::get('/course/certificate/{certificate}', [ExamController::class, 'certificate'])
    ->name('course.certificate');
Route::get('/course/certificate/{certificate}/pdf', [ExamController::class, 'certificatePdf'])
    ->name('course.certificate.pdf');

Route::post('/course/checkpoint/{chapter}', [CourseController::class, 'checkpoint'])
    ->where('chapter', '[0-9A-Za-z_-]+')
    ->middleware('throttle:'.config('pensec.course.exam_rate_limit_per_minute').',1')
    ->name('course.checkpoint');

// The slug is always a single chapter/lesson pair. The pattern admits no
// extra slash and no dot, so nothing but that shape reaches the controller,
// which then serves only lessons listed in the course manifest.
Route::get('/course/{slug}', [CourseController::class, 'show'])
    ->where('slug', '[0-9A-Za-z_-]+/[0-9A-Za-z_-]+')
    ->name('course.show');

Route::get('docs/{slug}.html', [DocumentationController::class, 'page'])
    ->where('slug', '[A-Za-z0-9_-]+')
    ->name('docs.page');

Route::prefix('panel')->name('panel.')->middleware('panel.locale')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [SessionController::class, 'create'])->name('login');
        Route::post('login', [SessionController::class, 'store'])->name('login.store');
    });

    // auth.session is what makes Auth::logoutOtherDevices() mean anything: it
    // signs a session out once the password it was opened with has changed.
    Route::middleware(['auth', 'auth.session'])->group(function (): void {
        Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

        Route::redirect('/', '/panel/devices')->name('home');

        Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::get('devices/new', [DeviceController::class, 'create'])->name('devices.create');
        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::get('devices/{device}', [DeviceController::class, 'edit'])->name('devices.edit');
        Route::post('devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::post('devices/{device}/delete', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::post('devices/{device}/token', [DeviceTokenController::class, 'store'])->name('devices.token');

        Route::get('administrators', [AdministratorController::class, 'index'])->name('administrators.index');
        Route::get('administrators/new', [AdministratorController::class, 'create'])->name('administrators.create');
        Route::post('administrators', [AdministratorController::class, 'store'])->name('administrators.store');
        Route::post('administrators/{administrator}/delete', [AdministratorController::class, 'destroy'])->name('administrators.destroy');

        Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
        Route::post('account', [AccountController::class, 'update'])->name('account.update');
        Route::post('account/password', [PasswordController::class, 'update'])->name('account.password');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{report}/payload', [ReportController::class, 'payload'])->name('reports.payload');
        Route::get('reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');

        // {variant} binds to the NarrativeVariant enum, so an unknown value is
        // a 404 before the controller runs.
        Route::post('reports/{report}/narrative/{variant}', [ReportNarrativeController::class, 'store'])
            ->name('reports.narrative.store');
        Route::post('reports/{report}/narrative/{variant}/regenerate', [ReportNarrativeController::class, 'regenerate'])
            ->name('reports.narrative.regenerate');
        Route::get('reports/{report}/narrative/{variant}/status', [ReportNarrativeController::class, 'status'])
            ->name('reports.narrative.status');
        Route::get('reports/{report}/narrative/{variant}/pdf', [ReportNarrativeController::class, 'show'])
            ->name('reports.narrative.pdf');
    });
});
