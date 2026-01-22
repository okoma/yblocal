<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManagerInvitationController;

//welcome
Route::get('/', function () {
    return view('welcome');
});


// Manager Invitation Routes (Public)
Route::prefix('manager/invitation')->name('manager.invitation.')->group(function () {
    Route::get('/{token}', [ManagerInvitationController::class, 'show'])
        ->name('accept');
    
    Route::post('/{token}/accept', [ManagerInvitationController::class, 'accept'])
        ->name('accept.submit');
    
    Route::post('/{token}/decline', [ManagerInvitationController::class, 'decline'])
        ->name('decline');
});

// Payment Webhooks (Server-to-Server notifications)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/paystack', [\App\Http\Controllers\PaymentController::class, 'paystackWebhook'])->name('paystack');
    Route::post('/flutterwave', [\App\Http\Controllers\PaymentController::class, 'flutterwaveWebhook'])->name('flutterwave');
});

// Payment Callbacks (User redirects after payment)
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/paystack/callback', [\App\Http\Controllers\PaymentController::class, 'paystackCallback'])->name('paystack.callback');
    Route::get('/flutterwave/callback', [\App\Http\Controllers\PaymentController::class, 'flutterwaveCallback'])->name('flutterwave.callback');
});