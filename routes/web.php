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

// Payment Webhook Routes (Public, but verified via signatures)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/paystack', [App\Http\Controllers\PaystackWebhookController::class, 'handle'])
        ->name('paystack');
    
    Route::post('/flutterwave', [App\Http\Controllers\FlutterwaveWebhookController::class, 'handle'])
        ->name('flutterwave');
});

// Payment Callback Routes (Public - user redirects after payment)
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/paystack/callback', [App\Http\Controllers\PaymentCallbackController::class, 'paystackCallback'])
        ->name('paystack.callback');
    
    Route::get('/flutterwave/callback', [App\Http\Controllers\PaymentCallbackController::class, 'flutterwaveCallback'])
        ->name('flutterwave.callback');
});