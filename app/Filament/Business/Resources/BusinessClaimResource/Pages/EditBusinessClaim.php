<?php

namespace App\Filament\Business\Resources\BusinessClaimResource\Pages;

use App\Filament\Business\Resources\BusinessClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBusinessClaim extends EditRecord
{
    protected static string $resource = BusinessClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn ($record) => in_array($record->status, ['pending', 'rejected']))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Claim Withdrawn')
                        ->body('Your claim has been withdrawn.')
                ),
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
            ->title('Claim Updated')
            ->body('Your claim has been updated successfully.');
    }

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;
        
        if (!$record) {
            return false;
        }
        
        // Only allow editing pending or rejected claims
        return in_array($record->status, ['pending', 'rejected']);
    }
}
