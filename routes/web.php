<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintController;

// Print routes (must be before the SPA catch-all)
Route::prefix('print')->group(function () {
    Route::get('/discharge-summary/{id}', [PrintController::class, 'dischargeSummary'])->name('print.discharge-summary');
    Route::get('/ipd-case-sheet/{id}', [PrintController::class, 'ipdCaseSheet'])->name('print.ipd-case-sheet');
    Route::get('/advance-receipt/{id}', [PrintController::class, 'advanceReceipt'])->name('print.advance-receipt');
});

// Serve Vue SPA for all frontend routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
