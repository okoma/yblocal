<?php

namespace App\Filament\Business\Resources\BusinessVerificationResource\Pages;

use App\Filament\Business\Resources\BusinessVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessVerifications extends ListRecords
{
    protected static string $resource = BusinessVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Verify a Business')
                ->icon('heroicon-o-shield-check')
                ->modalWidth('5xl')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['submitted_by'] = auth()->id();
                    $data['status'] = 'pending';
                    return $data;
                }),
        ];
    }
}
