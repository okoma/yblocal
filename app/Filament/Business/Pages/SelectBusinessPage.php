<?php

namespace App\Filament\Business\Pages;

use App\Filament\Business\Resources\BusinessResource;
use App\Services\ActiveBusiness;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SelectBusinessPage extends Page
{
    protected static ?string $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'select-business';

    protected static string $view = 'filament.business.pages.select-business';

    public function getTitle(): string | Htmlable
    {
        return 'Select Business';
    }

    public function getHeading(): string | Htmlable
    {
        return 'Select Business';
    }

    public function getSubheading(): ?string
    {
        $businesses = $this->getBusinessesProperty();
        if ($businesses->isEmpty()) {
            return null; // Heading/subheading handled in Blade view for better styling
        }
        return 'Choose the business you want to manage. All data, analytics, and settings will be scoped to this business.';
    }

    public function getBusinessesProperty(): \Illuminate\Support\Collection
    {
        return app(ActiveBusiness::class)->getSelectableBusinesses();
    }

    protected function getHeaderActions(): array
    {
        $businesses = $this->getBusinessesProperty();
        // Only show header action when there are businesses (for "Add New Business")
        if ($businesses->isEmpty()) {
            return [];
        }
        return [
            Action::make('add_new_business')
                ->label('Add New Business')
                ->icon('heroicon-o-plus')
                ->url(BusinessResource::getUrl('create'))
                ->color('primary'),
        ];
    }

    public function selectBusiness(int $id): void
    {
        $active = app(ActiveBusiness::class);
        if (!$active->isValid($id)) {
            return;
        }
        $active->setActiveBusinessId($id);
        
        // Redirect to dashboard only from the select business page
        // (This page is specifically for selecting a business, so dashboard makes sense here)
        $this->js('window.location.href = "' . route('filament.business.pages.dashboard') . '"');
    }
}
