<?php

// ============================================
// routes/console.php
// Laravel 12 scheduler configuration
// ============================================
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;
use Filament\Notifications\Notification;

// Auto-renewal: Check twice daily (6 AM and 6 PM)
// This attempts to auto-renew subscriptions expiring within 3 days
Schedule::command('subscriptions:check-expired --auto-renew')
    ->twiceDaily(6, 18)
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('Auto-renewal check completed successfully', [
            'scheduled_at' => now()->toDateTimeString(),
        ]);
    })
    ->onFailure(function () {
        Log::error('Auto-renewal check failed', [
            'scheduled_at' => now()->toDateTimeString(),
        ]);
    });

// Expiration cleanup: Check daily at midnight
// This marks expired subscriptions and removes premium access
Schedule::command('subscriptions:check-expired')
    ->daily()
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('Subscription expiration check completed successfully', [
            'scheduled_at' => now()->toDateTimeString(),
        ]);
    })
    ->onFailure(function () {
        Log::error('Subscription expiration check failed', [
            'scheduled_at' => now()->toDateTimeString(),
        ]);
    });

// Send expiring subscription notifications (using notification classes)
Schedule::command('notifications:send-expiring')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->onSuccess(function () {
        Log::info('Expiring notifications sent successfully', [
            'scheduled_at' => now()->toDateTimeString(),
        ]);
    })
    ->onFailure(function () {
        Log::error('Failed to send expiring notifications', [
            'scheduled_at' => now()->toDateTimeString(),
        ]);
    });

// Optional: Send expiration reminders 7 days before
Schedule::call(function () {
    $subscriptions = Subscription::where('status', 'active')
        ->whereBetween('ends_at', [now()->addDays(7), now()->addDays(8)])
        ->with(['user', 'plan'])
        ->get();

    foreach ($subscriptions as $subscription) {
        Notification::make()
            ->warning()
            ->title('Subscription Expiring Soon')
            ->body("Your {$subscription->plan->name} subscription expires in 7 days on " .
                   $subscription->ends_at->format('M j, Y') . ". " .
                   ($subscription->auto_renew 
                       ? "Don't worry, we'll automatically renew it if you have sufficient wallet balance." 
                       : "Please renew to avoid service interruption."))
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(\App\Filament\Business\Resources\SubscriptionResource::getUrl('view', ['record' => $subscription->id], panel: 'business'))
            ])
            ->sendToDatabase($subscription->user);
    }

    Log::info('Expiration reminders sent', [
        'count' => $subscriptions->count(),
        'scheduled_at' => now()->toDateTimeString(),
    ]);
})->daily()->at('09:00')->name('send-expiration-reminders');

// Optional: Send 3-day reminder
Schedule::call(function () {
    $subscriptions = Subscription::where('status', 'active')
        ->whereBetween('ends_at', [now()->addDays(3), now()->addDays(4)])
        ->where('auto_renew', false) // Only for non-auto-renewing subscriptions
        ->with(['user', 'plan'])
        ->get();

    foreach ($subscriptions as $subscription) {
        Notification::make()
            ->danger()
            ->title('Subscription Expiring in 3 Days!')
            ->body("Your {$subscription->plan->name} subscription expires in 3 days on " .
                   $subscription->ends_at->format('M j, Y') . ". " .
                   "Please renew now to avoid service interruption.")
            ->actions([
                \Filament\Notifications\Actions\Action::make('renew_now')
                    ->button()
                    ->color('danger')
                    ->url(\App\Filament\Business\Resources\SubscriptionResource::getUrl('view', ['record' => $subscription->id], panel: 'business'))
            ])
            ->sendToDatabase($subscription->user);
    }

    Log::info('3-day expiration warnings sent', [
        'count' => $subscriptions->count(),
        'scheduled_at' => now()->toDateTimeString(),
    ]);
})->daily()->at('10:00')->name('send-3day-warnings');

// Optional: Send 1-day final warning
Schedule::call(function () {
    $subscriptions = Subscription::where('status', 'active')
        ->whereBetween('ends_at', [now()->addDay(), now()->addDays(2)])
        ->where('auto_renew', false)
        ->with(['user', 'plan'])
        ->get();

    foreach ($subscriptions as $subscription) {
        Notification::make()
            ->danger()
            ->title('⚠️ Subscription Expiring Tomorrow!')
            ->body("Your {$subscription->plan->name} subscription expires TOMORROW on " .
                   $subscription->ends_at->format('M j, Y') . ". " .
                   "Renew now to keep your premium features!")
            ->actions([
                \Filament\Notifications\Actions\Action::make('renew_now')
                    ->button()
                    ->color('danger')
                    ->url(\App\Filament\Business\Resources\SubscriptionResource::getUrl('view', ['record' => $subscription->id], panel: 'business'))
            ])
            ->persistent()
            ->sendToDatabase($subscription->user);
    }

    Log::info('Final expiration warnings sent', [
        'count' => $subscriptions->count(),
        'scheduled_at' => now()->toDateTimeString(),
    ]);
})->daily()->at('11:00')->name('send-final-warnings');

// ============================================
// Optional: Database cleanup tasks
// ============================================

// Clean up old failed jobs (older than 48 hours)
Schedule::command('queue:prune-failed --hours=48')
    ->daily()
    ->at('03:00')
    ->withoutOverlapping();

// Clean up old notifications (older than 30 days)
Schedule::call(function () {
    $deleted = \Illuminate\Notifications\DatabaseNotification::where('created_at', '<', now()->subDays(30))
        ->delete();

    Log::info('Old notifications cleaned up', [
        'deleted_count' => $deleted,
        'scheduled_at' => now()->toDateTimeString(),
    ]);
})->weekly()->sundays()->at('02:00')->name('cleanup-old-notifications');

// Clean up failed transactions (older than 90 days)
Schedule::call(function () {
    $deleted = \App\Models\Transaction::where('status', 'failed')
        ->where('created_at', '<', now()->subDays(90))
        ->delete();

    Log::info('Old failed transactions cleaned up', [
        'deleted_count' => $deleted,
        'scheduled_at' => now()->toDateTimeString(),
    ]);
})->monthly()->name('cleanup-failed-transactions');

// Check and mark expired quote requests
Schedule::command('quotes:check-expired')
    ->daily()
    ->at('01:00')
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('Quote request expiration check completed successfully', [
            'scheduled_at' => now()->toDateTimeString(),
        ]);
    })
    ->onFailure(function () {
        Log::error('Quote request expiration check failed', [
            'scheduled_at' => now()->toDateTimeString(),
        ]);
    });


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
