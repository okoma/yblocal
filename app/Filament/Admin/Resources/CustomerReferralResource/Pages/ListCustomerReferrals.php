<?php

namespace App\Filament\Admin\Resources\CustomerReferralResource\Pages;

use App\Filament\Admin\Resources\CustomerReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerReferrals extends ListRecords
{
    protected static string $resource = CustomerReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
