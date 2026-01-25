<?php

namespace App\Http\Middleware;

use App\Services\ActiveBusiness;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveBusiness
{
    public function __construct(
        protected ActiveBusiness $activeBusiness
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Skip for unauthenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        // ✅ FIX 1: Don't interfere with Livewire component updates
        if ($request->header('X-Livewire')) {
            return $next($request);
        }

        // Allow business selector page
        if ($request->routeIs('filament.business.pages.select-business')) {
            return $next($request);
        }

        // Allow business creation
        if ($request->routeIs('filament.business.resources.businesses.create')) {
            return $next($request);
        }

        // Allow profile pages
        if ($request->routeIs([
            'filament.business.pages.profile-settings',
            'filament.business.pages.account-preferences',
        ])) {
            return $next($request);
        }

        $id = $this->activeBusiness->getActiveBusinessId();
        
        // If active business is set and valid, proceed
        if ($id !== null && $this->activeBusiness->isValid($id)) {
            return $next($request);
        }

        // No active business set - redirect to select page
        // User must explicitly choose a business (no auto-select to preserve SPA)
        if ($request->isMethod('GET') && !$request->ajax()) {
            return redirect()->route('filament.business.pages.select-business');
        }
        
        // For AJAX/POST, proceed (Livewire will handle)
        return $next($request);
    }
}