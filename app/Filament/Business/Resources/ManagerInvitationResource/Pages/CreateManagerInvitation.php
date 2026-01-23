<?php

namespace App\Filament\Business\Resources\ManagerInvitationResource\Pages;

use App\Filament\Business\Resources\ManagerInvitationResource;
use App\Models\ManagerInvitation;
use App\Mail\ManagerInvitationMail;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateManagerInvitation extends CreateRecord
{
    protected static string $resource = ManagerInvitationResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate invitation token if not provided
        if (empty($data['invitation_token'])) {
            $data['invitation_token'] = Str::random(64);
        }
        
        // Set invited_by to current user
        $data['invited_by'] = Auth::id();
        
        // Set status to pending
        $data['status'] = 'pending';
        
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
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $invitation = $this->record;
        
        try {
            // Send email notification
            Mail::to($invitation->email)->send(new ManagerInvitationMail($invitation));
            
            Log::info('Manager invitation email sent', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
                'business_id' => $invitation->business_id,
            ]);
            
            Notification::make()
                ->title('Invitation sent')
                ->body('Manager invitation has been sent to ' . $invitation->email)
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Failed to send manager invitation email', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->title('Invitation created but email failed')
                ->body('The invitation was created but the email could not be sent. Please contact support.')
                ->warning()
                ->send();
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
