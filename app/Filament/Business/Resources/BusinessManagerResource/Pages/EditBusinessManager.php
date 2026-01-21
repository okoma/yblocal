<?php

namespace App\Filament\Business\Resources\BusinessManagerResource\Pages;

use App\Filament\Business\Resources\BusinessManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBusinessManager extends EditRecord
{
    protected static string $resource = BusinessManagerResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Remove Manager')
                ->modalDescription('Are you sure you want to remove this manager? They will lose all access to this business.')
                ->modalSubmitActionLabel('Yes, Remove Manager'),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert permissions array to proper format
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $permissions = [];
            $allPermissions = [
                'can_edit_business',
                'can_manage_products',
                'can_respond_to_reviews',
                'can_view_leads',
                'can_respond_to_leads',
                'can_view_analytics',
                'can_access_financials',
                'can_manage_staff',
            ];
            
            foreach ($allPermissions as $permission) {
                $permissions[$permission] = in_array($permission, $data['permissions']);
            }
            
            $data['permissions'] = $permissions;
        }
        
        // Handle primary manager setting
        if (isset($data['is_primary']) && $data['is_primary']) {
            // Remove primary flag from other managers of this business
            BusinessManager::where('business_id', $this->record->business_id)
                ->where('id', '!=', $this->record->id)
                ->update(['is_primary' => false]);
        }
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        $manager = $this->record;
        
        Notification::make()
            ->title('Manager updated')
            ->body('Manager permissions and settings have been updated.')
            ->success()
            ->send();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
