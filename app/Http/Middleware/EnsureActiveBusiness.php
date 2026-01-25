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

        $id = $this->activeBusiness->getActiveBusinessId();
        if ($id !== null && $this->activeBusiness->isValid($id)) {
            return $next($request);
        }

        $selectable = $this->activeBusiness->getSelectableBusinesses();
        if ($selectable->isEmpty()) {
            return $next($request);
        }

        return redirect()->route('filament.business.pages.select-business');
    }
}
