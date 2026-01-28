<?php

namespace App\Filament\Customer\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected static string $view = 'filament.customer.auth.password-reset-request';
}

