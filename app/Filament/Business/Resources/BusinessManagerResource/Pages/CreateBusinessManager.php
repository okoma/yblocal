<?php

namespace App\Filament\Business\Resources\BusinessManagerResource\Pages;

use App\Filament\Business\Resources\BusinessManagerResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateBusinessManager extends CreateRecord
{
    protected static string $resource = BusinessManagerResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
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
        
        // Set joined_at
        if (!isset($data['joined_at'])) {
            $data['joined_at'] = now();
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $manager = $this->record;
        
        Notification::make()
            ->title('Manager added')
            ->body($manager->user->name . ' has been added as a manager for ' . $manager->business->business_name)
            ->success()
            ->send();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
