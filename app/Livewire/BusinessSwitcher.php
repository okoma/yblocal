<?php

namespace App\Livewire;

use App\Services\ActiveBusiness;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BusinessSwitcher extends Component
{
    public function getActiveBusinessProperty(): ?object
    {
        $active = app(ActiveBusiness::class);
        $b = $active->getActiveBusiness();
        return $b ? (object) ['id' => $b->id, 'name' => $b->business_name] : null;
    }

    public function getBusinessesProperty(): \Illuminate\Support\Collection
    {
        return app(ActiveBusiness::class)->getSelectableBusinesses();
    }

    public function switchTo(int $id): void
    {
        $active = app(ActiveBusiness::class);
        if (!$active->isValid($id)) {
            return;
        }
        $active->setActiveBusinessId($id);
        
        // Force full page reload to dashboard with new business context
        // After this reload, all subsequent nav will use Filament SPA
        $this->js('window.location.href = "' . route('filament.business.pages.dashboard') . '"');
    }

    public function render(): View
    {
        return view('livewire.business-switcher');
    }
}