<?php

namespace App\Filament\Business\Resources\BusinessManagerResource\Pages;

use App\Filament\Business\Resources\BusinessManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessManagers extends ListRecords
{
    protected static string $resource = BusinessManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Manager'),
        ];
    }
}
