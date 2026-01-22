<?php

namespace App\Filament\Business\Resources\BusinessVerificationResource\Pages;

use App\Filament\Business\Resources\BusinessVerificationResource;
use App\Models\Business;
use App\Models\BusinessVerification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateBusinessVerification extends CreateRecord
{
    protected static string $resource = BusinessVerificationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['submitted_by'] = auth()->id();
        $data['status'] = 'pending';
        $data['resubmission_count'] = 0;
        
        // Check if business is claimed by current user
        $business = Business::find($data['business_id']);
        if (!$business || $business->user_id !== auth()->id()) {
            Notification::make()
                ->danger()
                ->title('Not Authorized')
                ->body('You can only verify businesses that you own.')
                ->persistent()
                ->send();
            
            $this->halt();
        }
        
        // Check for existing pending verification
        $existing = BusinessVerification::where('business_id', $data['business_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
        
        if ($existing) {
            Notification::make()
                ->warning()
                ->title('Verification Already Exists')
                ->body('This business already has a pending or approved verification.')
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
            ->title('Verification Submitted!')
            ->body('Your verification request has been submitted. We will review your documents and notify you of the result.')
            ->persistent();
    }
}
