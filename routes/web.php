<?php

use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\CaseFileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Case management routes
Route::middleware(['auth', 'verified', \App\Http\Middleware\LogCaseRequests::class])->group(function () {
    Route::resource('cases', CaseFileController::class)->parameters(['case' => 'caseReference']);
    Route::post('cases/{case}/test-database', [CaseFileController::class, 'testDatabase'])
        ->name('cases.test-database');
});

// Fallback route to catch case 404s and log them
Route::get('cases/{any}', function ($any) {
    logger()->error('Fallback route caught case 404', [
        'requested_id' => $any,
        'url' => request()->url(),
        'method' => request()->method(),
        'user_id' => auth()->id(),
        'referer' => request()->header('referer'),
    ]);

    abort(404, 'Case not found');
})->where('any', '.*')->middleware(['auth', 'verified'])->name('cases.fallback');

// Debug API routes (only in debug mode)
if (config('app.debug')) {
    Route::get('api/debug/database-info', [DebugController::class, 'databaseInfo'])
        ->middleware(['auth'])
        ->name('debug.database-info');
}

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
