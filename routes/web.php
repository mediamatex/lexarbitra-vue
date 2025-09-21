<?php

use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\CaseFileController;
use App\Models\CaseReference;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Case management routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cases', CaseFileController::class)->parameters(['case' => 'caseReference']);
    Route::post('cases/{case}/test-database', [CaseFileController::class, 'testDatabase'])
        ->name('cases.test-database');
});

// Debug API routes (only in debug mode)
if (config('app.debug')) {
    Route::get('api/debug/database-info', [DebugController::class, 'databaseInfo'])
        ->middleware(['auth'])
        ->name('debug.database-info');
}

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
