<?php

namespace App\Filament\Admin\Resources\WithdrawalRequestResource\Pages;

use App\Filament\Admin\Resources\WithdrawalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawalRequests extends ListRecords
{
    protected static string $resource = WithdrawalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
