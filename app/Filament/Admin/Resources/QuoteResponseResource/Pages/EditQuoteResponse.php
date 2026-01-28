<?php

namespace App\Filament\Admin\Resources\QuoteResponseResource\Pages;

use App\Filament\Admin\Resources\QuoteResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuoteResponse extends EditRecord
{
    protected static string $resource = QuoteResponseResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
