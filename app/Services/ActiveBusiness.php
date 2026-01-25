<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ActiveBusiness
{
    public const COOKIE_NAME = 'active_business_id';
    public const COOKIE_LIFETIME = 60 * 24 * 30; // 30 days in minutes

    public function getActiveBusinessId(): ?int
    {
        $id = Cookie::get(self::COOKIE_NAME) ?? request()->cookie(self::COOKIE_NAME);
        return $id ? (int) $id : null;
    }

    public function setActiveBusinessId(int $id): void
    {
        if (!$this->isValid($id)) {
            return;
        }
        
        // Queue cookie to be sent with next response
        Cookie::queue(
            self::COOKIE_NAME,
            $id,
            self::COOKIE_LIFETIME,
            '/',
            null,
            true, // secure (HTTPS only in production)
            false, // httpOnly = false so JS can read if needed
            false,
            'lax' // SameSite
        );
    }

    public function getActiveBusiness(): ?Business
    {
        $id = $this->getActiveBusinessId();
        if (!$id) {
            return null;
        }
        $business = Business::find($id);
        return $business && $this->isValid($id) ? $business : null;
    }

    /**
     * Businesses the user can select (owned + managed).
     */
    public function getSelectableBusinesses(): Collection
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }
        
        // Explicitly specify table name to avoid ambiguous column error
        $ownedIds = $user->businesses()->pluck('businesses.id');
        $managedIds = $user->managedBusinesses()->pluck('businesses.id');
        
        $ids = $ownedIds->merge($managedIds)->unique()->values();
        
        $businesses = Business::whereIn('id', $ids)->orderBy('business_name')->get();
        
        return $businesses->map(fn (Business $b) => (object) [
            'id' => $b->id, 
            'name' => $b->business_name
        ]);
    }

    public function isValid(?int $id): bool
    {
        if (!$id || !Auth::check()) {
            return false;
        }
        $ids = $this->getSelectableBusinesses()->pluck('id')->toArray();
        return in_array($id, $ids, true);
    }

    public function clear(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }
}