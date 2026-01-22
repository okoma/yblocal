<?php

namespace App\Filament\Business\Resources\BusinessClaimResource\Pages;

use App\Filament\Business\Resources\BusinessClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBusinessClaim extends ViewRecord
{
    protected static string $resource = BusinessClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => in_array($record->status, ['pending', 'rejected'])),
        ];
    }
}
