<?php

namespace App\Filament\Business\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;

class Register extends BaseRegister
{
    protected static string $view = 'filament.business.auth.register';

    protected function handleRegistration(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::BUSINESS_OWNER,
        ]);
    }
}

