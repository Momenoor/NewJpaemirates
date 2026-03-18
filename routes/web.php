<?php

use App\Http\Controllers\IncentiveCalculationPrintController;
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
