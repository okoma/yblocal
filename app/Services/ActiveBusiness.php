<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ActiveBusiness
{
    public const SESSION_KEY = 'active_business_id';

    public function getActiveBusinessId(): ?int
    {
        $id = Session::get(self::SESSION_KEY);
        return $id ? (int) $id : null;
    }

    public function setActiveBusinessId(int $id): void
    {
        if (!$this->isValid($id)) {
            return;
        }
        Session::put(self::SESSION_KEY, $id);
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
            'name' => $b->business_name,
            'status' => $b->status,
            'is_claimed' => $b->is_claimed,
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
        Session::forget(self::SESSION_KEY);
    }
}