<?php

namespace App\Livewire;

use App\Services\ActiveBusiness;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BusinessSwitcher extends Component
{
    protected $listeners = ['business-switched' => '$refresh'];
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
        $this->dispatch('business-switched');
    }

    public function render(): View
    {
        return view('livewire.business-switcher');
    }
}
