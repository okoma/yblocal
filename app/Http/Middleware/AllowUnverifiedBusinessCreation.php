<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allow unverified users to access business creation page
 * This prevents redirect loops when new users register and need to create their first business
 */
class AllowUnverifiedBusinessCreation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // If user is unverified and has the session flag, allow access to create business
        if ($user && !$user->hasVerifiedEmail() && session()->get('allow_unverified_business_creation')) {
            // Check if this is the create business route or related routes
            if ($request->routeIs([
                'filament.business.resources.businesses.create',
                'filament.business.resources.businesses.*',
            ])) {
                // Allow the request to proceed
                return $next($request);
            }
        }

        return $next($request);
    }
}
