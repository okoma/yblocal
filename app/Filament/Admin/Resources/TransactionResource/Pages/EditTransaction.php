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

    public function __construct(
        protected ActivationService $activationService
    ) {}

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

        if (
            $this->statusBeforeSave === 'pending'
            && $record->status === 'completed'
        ) {
            try {
                $this->activationService->completeAndActivate($record);

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
