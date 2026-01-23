<?php

namespace App\Filament\Business\Resources\ManagerInvitationResource\Pages;

use App\Filament\Business\Resources\ManagerInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManagerInvitations extends ListRecords
{
    protected static string $resource = ManagerInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Invite Manager'),
        ];
    }
}
