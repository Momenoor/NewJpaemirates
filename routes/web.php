<?php

use App\Http\Controllers\IncentiveCalculationPrintController;
use App\Http\Controllers\MatterReceivedNotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('attachments/{attachment}/download', function (\App\Models\Attachment $attachment) {
    abort_unless(auth()->user()->can('view', $attachment->matter), 403);

    return response()->download(
        Storage::disk('public')->path($attachment->path),
        $attachment->name  // original filename from DB
    );
})->name('attachment.download')->middleware('auth');

Route::get('incentive/calculations/{calculation}/print', IncentiveCalculationPrintController::class)
    ->name('incentive.calculation.print')
    ->middleware(['auth']);

Route::prefix('matter/{matter}/received-date')->group(function () {
    // Accept link from email (GET — no auth required)
    Route::get('accept', [MatterReceivedNotificationController::class, 'accept'])
        ->name('matter.received.accept')
        ->middleware('signed');

    // Dispute link from email — shows form (GET — no auth required)
    Route::get('dispute', [MatterReceivedNotificationController::class, 'disputeForm'])
        ->name('matter.received.dispute')
        ->middleware('signed');

    // Dispute form submission (POST — no auth required)
    Route::post('dispute', [MatterReceivedNotificationController::class, 'disputeSubmit'])
        ->name('matter.received.dispute.submit');
});
