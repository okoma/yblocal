<?php
// ============================================
// 6. CreateReferral.php
// Location: app/Filament/Admin/Resources/ReferralResource/Pages/CreateReferral.php
// ============================================

namespace App\Filament\Admin\Resources\ReferralResource\Pages;

use App\Filament\Admin\Resources\ReferralResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReferral extends CreateRecord
{
    protected static string $resource = ReferralResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate referral_code from referrer's code if not provided
        if (empty($data['referral_code']) && !empty($data['referrer_id'])) {
            $referrer = \App\Models\User::find($data['referrer_id']);
            if ($referrer && $referrer->referral_code) {
                $data['referral_code'] = $referrer->referral_code;
            }
        }
        
        return $data;
    }
}