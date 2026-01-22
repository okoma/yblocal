<?php

namespace App\Filament\Business\Resources\BusinessVerificationResource\Pages;

use App\Filament\Business\Resources\BusinessVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewBusinessVerification extends ViewRecord
{
    protected static string $resource = BusinessVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => in_array($record->status, ['pending', 'requires_resubmission'])),
            
            Actions\Action::make('resubmit')
                ->label('Resubmit for Review')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Resubmit Verification')
                ->modalDescription('This will mark your verification as resubmitted and pending review.')
                ->action(function () {
                    $this->record->resubmit();
                    
                    Notification::make()
                        ->success()
                        ->title('Resubmitted')
                        ->body('Your verification has been resubmitted for review.')
                        ->send();
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn ($record) => $record->status === 'requires_resubmission'),
        ];
    }
}
