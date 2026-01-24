<?php

namespace App\Filament\Admin\Resources\WithdrawalRequestResource\Pages;

use App\Filament\Admin\Resources\WithdrawalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWithdrawalRequest extends EditRecord
{
    protected static string $resource = WithdrawalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
