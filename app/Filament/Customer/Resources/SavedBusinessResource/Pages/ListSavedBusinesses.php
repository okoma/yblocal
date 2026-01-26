<?php

namespace App\Filament\Customer\Resources\SavedBusinessResource\Pages;

use App\Filament\Customer\Resources\SavedBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSavedBusinesses extends ListRecords
{
    protected static string $resource = SavedBusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('discover')
                ->label('Discover Businesses')
                ->icon('heroicon-o-magnifying-glass')
                ->url('/')
                ->color('primary'),
        ];
    }
}
