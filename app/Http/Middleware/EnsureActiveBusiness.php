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
        if (!auth()->check()) {
            return $next($request);
        }

        if ($request->routeIs('filament.business.pages.select-business')) {
            return $next($request);
        }

        // Allow create so "Add New Business" from select-business works
        if ($request->routeIs('filament.business.resources.businesses.create')) {
            return $next($request);
        }

        // Allow profile settings pages when user has no businesses
        if ($request->routeIs([
            'filament.business.pages.profile-settings',
            'filament.business.pages.account-preferences',
        ])) {
            return $next($request);
        }

        $id = $this->activeBusiness->getActiveBusinessId();
        if ($id !== null && $this->activeBusiness->isValid($id)) {
            return $next($request);
        }

        $selectable = $this->activeBusiness->getSelectableBusinesses();
        // If no businesses, force redirect to Get Started (select-business page)
        if ($selectable->isEmpty()) {
            return redirect()->route('filament.business.pages.select-business');
        }

        return redirect()->route('filament.business.pages.select-business');
    }
}
