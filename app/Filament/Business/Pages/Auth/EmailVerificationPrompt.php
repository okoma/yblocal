<?php

namespace App\Filament\Business\Pages\Auth;

use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    protected static string $view = 'filament.business.auth.verify-email';

    public function resendEmailVerification(): void
    {
        /** @var MustVerifyEmail $user */
        $user = filament()->auth()->user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $notification = new VerifyEmail();
        $notification->url = filament()->getVerifyEmailUrl($user);
        
        $user->notify($notification);

        Notification::make()
            ->title('Verification email has been resent.')
            ->success()
            ->send();
    }
}