<?php

namespace App\Filament\Customer\Pages\Auth;

use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;

class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    protected static string $view = 'filament.customer.auth.verify-email';
}

