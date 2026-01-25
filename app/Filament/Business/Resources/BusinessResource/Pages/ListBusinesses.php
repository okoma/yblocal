<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/Pages/ListBusinesses.php
// Redirects to view(active business) or select-business. List not used; dropdown = business list.
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\Pages;

use App\Filament\Business\Resources\BusinessResource;
use App\Services\ActiveBusiness;
use Filament\Resources\Pages\ListRecords;

class ListBusinesses extends ListRecords
{
    protected static string $resource = BusinessResource::class;

    public function mount(): void
    {
        $active = app(ActiveBusiness::class);
        $id = $active->getActiveBusinessId();
        if ($id && $active->isValid($id)) {
            $this->redirect(BusinessResource::getUrl('view', ['record' => $id]), navigate: true);
            return;
        }
        $this->redirect(route('filament.business.pages.select-business'), navigate: true);
    }
}
