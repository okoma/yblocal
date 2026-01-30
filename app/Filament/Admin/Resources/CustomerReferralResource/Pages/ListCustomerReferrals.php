<?php

namespace App\Filament\Admin\Resources\CustomerReferralResource\Pages;

use App\Filament\Admin\Resources\CustomerReferralResource;
use App\Models\CustomerReferral;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerReferrals extends ListRecords
{
    protected static string $resource = CustomerReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => auth()->user()?->can('export-data'))
                ->action(function () {
                    return ExportService::streamCsvFromQuery(
                        'customer-referrals-all-' . now()->format('Ymd-His') . '.csv',
                        [
                            'Referral Code',
                            'Referrer Name',
                            'Referrer Email',
                            'Referred Business',
                            'Status',
                            'Suspicious',
                            'IP Address',
                            'Created At',
                        ],
                        CustomerReferral::query()->with(['referrer', 'referredBusiness']),
                        fn (CustomerReferral $record) => [
                            $record->referral_code,
                            $record->referrer?->name,
                            $record->referrer?->email,
                            $record->referredBusiness?->business_name,
                            $record->status,
                            $record->is_suspicious ? 'yes' : 'no',
                            $record->ip_address,
                            optional($record->created_at)->toDateTimeString(),
                        ]
                    );
                }),
        ];
    }
}
