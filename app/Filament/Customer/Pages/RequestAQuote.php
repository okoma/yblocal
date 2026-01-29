<?php

namespace App\Filament\Customer\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use Filament\Pages\Page;

class RequestAQuote extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Request a Quote';

    protected static ?string $navigationGroup = 'Quote';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.customer.pages.request-a-quote';

    protected static bool $shouldRegisterNavigation = true;

    public function mount()
    {
        return redirect()->to(QuoteRequestResource::getUrl('create'));
    }
}