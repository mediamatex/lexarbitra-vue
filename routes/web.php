<?php

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
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cases', CaseFileController::class);
    Route::post('cases/{caseFile}/test-database', [CaseFileController::class, 'testDatabase'])
        ->name('cases.test-database');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
