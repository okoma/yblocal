<?php

namespace App\Filament\Business\Pages\Auth;

use Filament\Notifications\Notification;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    protected static string $view = 'filament.business.auth.verify-email';

    public function mount(): void
    {
        parent::mount();

        /** @var MustVerifyEmail $user */
        $user = filament()->auth()->user();

        // If user has no businesses yet, redirect to create business first
        // They can verify email after creating their business
        // Check session flag to prevent redirect loops
        if ($user && $user->businesses()->count() === 0 && !session()->get('showing_email_verification')) {
            // Set session flag to prevent redirect loop
            session()->put('showing_email_verification', true);
            session()->put('allow_unverified_business_creation', true);
            
            // Redirect to create business page
            $this->redirect(\App\Filament\Business\Resources\BusinessResource::getUrl('create'), navigate: false);
            return;
        }
        
        // Clear the flag if user has businesses (they've completed registration flow)
        if ($user && $user->businesses()->count() > 0) {
            session()->forget('showing_email_verification');
            session()->forget('allow_unverified_business_creation');
        }
    }

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