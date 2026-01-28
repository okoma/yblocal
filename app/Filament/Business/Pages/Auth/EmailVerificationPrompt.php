<?php

namespace App\Filament\Business\Pages\Auth;

use Filament\Notifications\Notification;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    protected static string $view = 'filament.business.auth.verify-email';

    // Email verification redirect removed - notice shows on dashboard instead
    // This class is kept for potential future use but redirect logic is disabled

    public function resendEmailVerification(): void
    {
        /** @var MustVerifyEmail $user */
        $user = filament()->auth()->user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        // Use Laravel's built-in VerifyEmail notification which sends actual emails
        $user->sendEmailVerificationNotification();

        Notification::make()
            ->title('Verification email has been resent.')
            ->success()
            ->send();
    }
}