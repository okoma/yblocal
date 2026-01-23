<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use App\Services\ActivationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected ?string $statusBeforeSave = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->statusBeforeSave = $this->record->status;

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Only activate if status changed from pending to completed
        if (
            $this->statusBeforeSave === 'pending'
            && $record->status === 'completed'
        ) {
            try {
                // Resolve ActivationService from the container
                $activationService = app(ActivationService::class);
                $activationService->completeAndActivate($record);

                Notification::make()
                    ->title('Transaction completed and payable activated')
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('EditTransaction: activation failed', [
                    'transaction_id' => $record->id,
                    'error' => $e->getMessage(),
                ]);

                Notification::make()
                    ->title('Transaction saved, but activation failed')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
}