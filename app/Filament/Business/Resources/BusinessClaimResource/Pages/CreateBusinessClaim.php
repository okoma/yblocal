<?php

namespace App\Filament\Business\Resources\BusinessClaimResource\Pages;

use App\Filament\Business\Resources\BusinessClaimResource;
use App\Models\BusinessClaim;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateBusinessClaim extends CreateRecord
{
    protected static string $resource = BusinessClaimResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        
        // Check for duplicate claims
        if (BusinessClaim::hasExistingClaim($data['user_id'], $data['business_id'])) {
            Notification::make()
                ->danger()
                ->title('Duplicate Claim')
                ->body('You already have a pending or approved claim for this business.')
                ->persistent()
                ->send();
            
            $this->halt();
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Claim Submitted!')
            ->body('Your business claim has been submitted for review. We will notify you once it has been reviewed.')
            ->persistent();
    }
}
