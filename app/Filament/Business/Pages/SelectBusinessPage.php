<?php

namespace App\Filament\Business\Pages;

use App\Services\ActiveBusiness;
use Filament\Pages\Page;
use Filament\Actions\Action;
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
        return 'Choose the business you want to manage. All data, analytics, and settings will be scoped to this business.';
    }

    public function getBusinessesProperty(): \Illuminate\Support\Collection
    {
        return app(ActiveBusiness::class)->getSelectableBusinesses();
    }

    public function selectBusiness(int $id): void
    {
        $active = app(ActiveBusiness::class);
        if (!$active->isValid($id)) {
            return;
        }
        $active->setActiveBusinessId($id);
        $this->dispatch('business-switched');
        $this->redirect(route('filament.business.pages.dashboard'), navigate: true);
    }
}
