<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Subscription;
use App\Models\AdCampaign;
use App\Models\Wallet;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If business_id is not set, try to get it from transactionable
        if (empty($data['business_id']) && !empty($data['transactionable_type']) && !empty($data['transactionable_id'])) {
            $transactionableType = $data['transactionable_type'];
            $transactionableId = $data['transactionable_id'];
            
            $businessId = null;
            
            if ($transactionableType === Subscription::class) {
                $subscription = Subscription::find($transactionableId);
                $businessId = $subscription?->business_id;
            } elseif ($transactionableType === AdCampaign::class) {
                $campaign = AdCampaign::find($transactionableId);
                $businessId = $campaign?->business_id;
            } elseif ($transactionableType === Wallet::class) {
                $wallet = Wallet::find($transactionableId);
                $businessId = $wallet?->business_id;
            }
            
            if ($businessId) {
                $data['business_id'] = $businessId;
            }
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
