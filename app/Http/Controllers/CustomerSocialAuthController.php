<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class CustomerSocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google', 'apple'], true), 404);

        // Use stateless for SPA / cross-domain friendliness
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google', 'apple'], true), 404);

        $socialUser = Socialite::driver($provider)->stateless()->user();

        // Prefer provider id match
        $user = User::query()
            ->where('oauth_provider', $provider)
            ->where('oauth_provider_id', (string) $socialUser->getId())
            ->first();

        // Fallback to email match (link accounts)
        if (!$user && $socialUser->getEmail()) {
            $user = User::query()->where('email', $socialUser->getEmail())->first();
        }

        if (!$user) {
            $user = User::create([
                'name' => $socialUser->getName() ?: ($socialUser->getNickname() ?: 'Customer'),
                'email' => $socialUser->getEmail() ?: (Str::uuid() . '@example.invalid'),
                'password' => Str::password(32),
                'role' => UserRole::CUSTOMER,
                'oauth_provider' => $provider,
                'oauth_provider_id' => (string) $socialUser->getId(),
                'email_verified_at' => now(), // social login implies verified email (provider-level)
            ]);
        } else {
            // Ensure customer role for customer-panel social login
            if (!$user->isCustomer()) {
                // Do not allow non-customer accounts to login via customer social routes
                return redirect('/customer/login')->with('error', 'This account cannot access the customer portal.');
            }

            $user->forceFill([
                'oauth_provider' => $user->oauth_provider ?: $provider,
                'oauth_provider_id' => $user->oauth_provider_id ?: (string) $socialUser->getId(),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        }

        Auth::login($user, remember: true);

        return redirect('/customer');
    }
}

