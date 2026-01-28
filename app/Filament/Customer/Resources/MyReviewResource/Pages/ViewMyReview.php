<?php

namespace App\Filament\Customer\Resources\MyReviewResource\Pages;

use App\Filament\Customer\Resources\MyReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMyReview extends ViewRecord
{
    protected static string $resource = MyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => !$record->is_approved),
        ];
    }
}
