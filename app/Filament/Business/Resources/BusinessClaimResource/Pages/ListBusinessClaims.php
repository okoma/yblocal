<?php

namespace App\Filament\Business\Resources\BusinessClaimResource\Pages;

use App\Filament\Business\Resources\BusinessClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessClaims extends ListRecords
{
    protected static string $resource = BusinessClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Claim a Business')
                ->icon('heroicon-o-hand-raised')
                ->modalWidth('3xl')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    return $data;
                }),
        ];
    }
}
