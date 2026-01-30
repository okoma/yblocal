<?php

namespace App\Filament\Admin\Resources\BusinessReferralResource\Pages;

use App\Filament\Admin\Resources\BusinessReferralResource;
use App\Models\BusinessReferral;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessReferrals extends ListRecords
{
    protected static string $resource = BusinessReferralResource::class;

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
                        'business-referrals-all-' . now()->format('Ymd-His') . '.csv',
                        [
                            'Referral Code',
                            'Referrer Business',
                            'Referred Business',
                            'Credits Awarded',
                            'Status',
                            'Suspicious',
                            'IP Address',
                            'Created At',
                        ],
                        BusinessReferral::query()->with(['referrerBusiness', 'referredBusiness']),
                        fn (BusinessReferral $record) => [
                            $record->referral_code,
                            $record->referrerBusiness?->business_name,
                            $record->referredBusiness?->business_name,
                            $record->referral_credits_awarded,
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
