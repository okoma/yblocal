<?php

namespace App\Filament\Customer\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Register extends BaseRegister
{
    protected static string $view = 'filament.customer.auth.register';

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Full name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('Email address')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(User::class, 'email'),

            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->required()
                ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                ->helperText('Use at least 8 characters with upper & lower case letters, numbers, and a symbol.')
                ->revealable(),

            Forms\Components\TextInput::make('passwordConfirmation')
                ->label('Confirm password')
                ->password()
                ->same('password')
                ->required()
                ->revealable(),
        ];
    }

    protected function handleRegistration(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::CUSTOMER,
        ]);
    }
}

