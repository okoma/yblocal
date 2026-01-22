<?php

namespace App\Filament\Business\Resources\BusinessVerificationResource\Pages;

use App\Filament\Business\Resources\BusinessVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBusinessVerification extends EditRecord
{
    protected static string $resource = BusinessVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Verification Updated')
            ->body('Your verification has been updated successfully.');
    }

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;
        
        if (!$record) {
            return false;
        }
        
        // Only allow editing pending or requires_resubmission verifications
        return in_array($record->status, ['pending', 'requires_resubmission']);
    }
}
