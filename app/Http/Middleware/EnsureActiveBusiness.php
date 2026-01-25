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

        // No active business set - auto-select first business (only once per session)
        $selectable = $this->activeBusiness->getSelectableBusinesses();
        
        // If no businesses at all, redirect to get started page
        if ($selectable->isEmpty()) {
            if ($request->isMethod('GET') && !$request->ajax() && !$request->header('X-Livewire')) {
                return redirect()->route('filament.business.pages.select-business');
            }
            return $next($request);
        }
        
        // Auto-select first business (only on first request, not on every SPA nav)
        // Check if we've already tried to set business this session
        if (!session()->has('_business_auto_selected')) {
            $firstBusiness = $selectable->first();
            $this->activeBusiness->setActiveBusinessId($firstBusiness->id);
            session()->put('_business_auto_selected', true);
        }
        
        // Continue to requested page - no redirect needed
        return $next($request);
    }
}