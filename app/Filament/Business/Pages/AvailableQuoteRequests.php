<?php

namespace App\Filament\Business\Pages;

use App\Models\QuoteRequest;
use App\Models\QuoteResponse;
use App\Models\Wallet;
use App\Services\ActiveBusiness;
use App\Services\QuoteDistributionService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class AvailableQuoteRequests extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $slug = 'available-quote-requests';
    
    protected static string $view = 'filament.business.pages.available-quote-requests';
    
    protected static ?string $navigationLabel = 'Available Requests';
    
    protected static ?string $title = 'Available Quote Requests';
    
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    
    protected static ?string $navigationGroup = 'Quotes';
    
    protected static ?int $navigationSort = 1;
    
    protected static bool $shouldRegisterNavigation = true;

    /**
     * Show count of new available quote requests for the business
     */
    public static function getNavigationBadge(): ?string
    {
        $activeBusiness = app(ActiveBusiness::class);
        $businessId = $activeBusiness->getActiveBusinessId();
        
        if (!$businessId) {
            return null;
        }
        
        $business = \App\Models\Business::find($businessId);
        if (!$business) {
            return null;
        }
        
        $distributionService = app(QuoteDistributionService::class);
        $availableRequests = $distributionService->getAvailableQuoteRequests($business);
        $count = $availableRequests->count();
        
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
    
    public function getHeading(): string
    {
        return 'Available Quote Requests';
    }
    
    public function getSubheading(): ?string
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        
        if (!$businessId) {
            return 'Browse quote requests from customers looking for your services.';
        }
        
        $wallet = Wallet::where('business_id', $businessId)->first();
        $credits = $wallet ? $wallet->quote_credits : 0;
        
        $creditText = $credits > 0 
            ? "You have {$credits} quote credit" . ($credits > 1 ? 's' : '') . " available."
            : "You have 0 quote credits. Purchase credits to submit quotes.";
        
        return "Browse quote requests from customers looking for your services. {$creditText}";
    }

    public function table(Table $table): Table
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        
        if (!$businessId) {
            return $table->query(QuoteRequest::whereRaw('1 = 0'));
        }
        
        $distributionService = app(QuoteDistributionService::class);
        $business = \App\Models\Business::find($businessId);
        
        if (!$business) {
            return $table->query(QuoteRequest::whereRaw('1 = 0'));
        }
        
        $availableRequests = $distributionService->getAvailableQuoteRequests($business);
        $requestIds = $availableRequests->pluck('id')->toArray();
        
        // Get wallet for credit check
        $wallet = Wallet::where('business_id', $businessId)->first();
        $hasCredits = $wallet && $wallet->quote_credits > 0;
        
        return $table
            ->query(QuoteRequest::query()->whereIn('id', $requestIds))
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('title')
                            ->label('Request')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->size('lg')
                            ->wrap()
                            ->grow(),
                        
                        Tables\Columns\TextColumn::make('created_at')
                            ->label('Posted')
                            ->dateTime('M d, Y')
                            ->sortable()
                            ->size('sm')
                            ->color('gray'),
                    ]),
                    
                    Tables\Columns\TextColumn::make('description')
                        ->label('')
                        ->limit(150)
                        ->wrap()
                        ->color('gray')
                        ->size('sm'),
                    
                    Tables\Columns\Layout\Grid::make(3)
                        ->schema([
                            Tables\Columns\TextColumn::make('category.name')
                                ->label('Category')
                                ->badge()
                                ->color('info')
                                ->icon('heroicon-m-tag'),
                            
                            Tables\Columns\TextColumn::make('location')
                                ->label('Location')
                                ->formatStateUsing(function ($record) {
                                    if (!$record) return 'N/A';
                                    
                                    $location = $record->cityLocation 
                                        ? $record->cityLocation->name . ', ' . $record->stateLocation->name
                                        : ($record->stateLocation ? $record->stateLocation->name : 'N/A');
                                    return $location;
                                })
                                ->icon('heroicon-m-map-pin')
                                ->color('gray'),
                            
                            Tables\Columns\TextColumn::make('responses_count')
                                ->label('Quotes Submitted')
                                ->counts('responses')
                                ->badge()
                                ->color('success')
                                ->icon('heroicon-m-document-text'),
                        ]),
                    
                    Tables\Columns\Layout\Grid::make(2)
                        ->schema([
                            Tables\Columns\TextColumn::make('budget')
                                ->label('Budget Range')
                                ->formatStateUsing(function ($record) {
                                    if (!$record) return 'Budget not specified';
                                    
                                    if ($record->budget_min && $record->budget_max) {
                                        return '₦' . number_format($record->budget_min, 0) . ' - ₦' . number_format($record->budget_max, 0);
                                    } elseif ($record->budget_min) {
                                        return 'From ₦' . number_format($record->budget_min, 0);
                                    } elseif ($record->budget_max) {
                                        return 'Up to ₦' . number_format($record->budget_max, 0);
                                    }
                                    return 'Budget not specified';
                                })
                                ->icon('heroicon-m-currency-dollar')
                                ->color('success')
                                ->visible(fn ($record) => $record && ($record->budget_min || $record->budget_max)),
                            
                            Tables\Columns\TextColumn::make('expires_at')
                                ->label('Expires')
                                ->dateTime('M d, Y')
                                ->icon('heroicon-m-clock')
                                ->color('warning')
                                ->visible(fn ($record) => $record && $record->expires_at),
                        ]),
                ])
                ->space(3),
            ])
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('state_location_id')
                    ->label('State')
                    ->relationship('stateLocation', 'name', fn ($query) => $query->where('type', 'state'))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('submit_quote')
                    ->label(fn () => $hasCredits ? 'Submit Quote' : 'No Credits')
                    ->icon(fn () => $hasCredits ? 'heroicon-o-paper-airplane' : 'heroicon-o-lock-closed')
                    ->color(fn () => $hasCredits ? 'primary' : 'gray')
                    ->disabled(fn () => !$hasCredits)
                    ->tooltip(fn () => !$hasCredits ? 'Purchase quote credits to submit quotes' : null)
                    ->form([
                        Forms\Components\Section::make('Request Details')
                            ->schema([
                                Forms\Components\Placeholder::make('title')
                                    ->label('Title')
                                    ->content(fn ($record) => $record->title),
                                
                                Forms\Components\Placeholder::make('description')
                                    ->label('Description')
                                    ->content(fn ($record) => $record->description),
                                
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Placeholder::make('category')
                                            ->label('Category')
                                            ->content(fn ($record) => $record->category->name),
                                        
                                        Forms\Components\Placeholder::make('location')
                                            ->label('Location')
                                            ->content(function ($record) {
                                                if (!$record) return 'N/A';
                                                
                                                return $record->cityLocation 
                                                    ? $record->cityLocation->name . ', ' . $record->stateLocation->name
                                                    : ($record->stateLocation ? $record->stateLocation->name : 'N/A');
                                            }),
                                    ]),
                                
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Placeholder::make('budget')
                                            ->label('Budget Range')
                                            ->content(function ($record) {
                                                if (!$record) return 'Not specified';
                                                
                                                if ($record->budget_min && $record->budget_max) {
                                                    return '₦' . number_format($record->budget_min, 2) . ' - ₦' . number_format($record->budget_max, 2);
                                                } elseif ($record->budget_min) {
                                                    return 'From ₦' . number_format($record->budget_min, 2);
                                                } elseif ($record->budget_max) {
                                                    return 'Up to ₦' . number_format($record->budget_max, 2);
                                                }
                                                return 'Not specified';
                                            })
                                            ->visible(fn ($record) => $record && ($record->budget_min || $record->budget_max)),
                                        
                                        Forms\Components\Placeholder::make('expires_at')
                                            ->label('Expires On')
                                            ->content(fn ($record) => $record && $record->expires_at ? $record->expires_at->format('M d, Y g:i A') : 'No expiration')
                                            ->visible(fn ($record) => $record && $record->expires_at),
                                    ]),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                        
                        Forms\Components\Section::make('Your Quote')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Your Price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₦')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Enter your competitive price for this service'),
                                
                                Forms\Components\TextInput::make('delivery_time')
                                    ->label('Delivery Time')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., 2-3 weeks, 1 month, 5 business days')
                                    ->helperText('Estimated time to complete the work'),
                                
                                Forms\Components\Textarea::make('message')
                                    ->label('Proposal Message')
                                    ->required()
                                    ->rows(5)
                                    ->maxLength(1000)
                                    ->placeholder('Explain your approach, experience, and why you\'re the best fit for this project...')
                                    ->helperText('This message will be visible to the customer. Make it compelling!')
                                    ->columnSpanFull(),
                                
                                Forms\Components\FileUpload::make('attachments')
                                    ->label('Attachments (Optional)')
                                    ->multiple()
                                    ->directory('quote-responses')
                                    ->visibility('private')
                                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                                    ->maxSize(5120) // 5MB
                                    ->helperText('Upload portfolio images, certificates, or relevant documents (max 5MB per file)')
                                    ->columnSpanFull(),
                            ]),
                        
                        Forms\Components\Section::make('Cost Breakdown')
                            ->schema([
                                Forms\Components\Placeholder::make('quote_cost')
                                    ->label('Quote Submission Cost')
                                    ->content('1 Quote Credit')
                                    ->helperText(function () use ($wallet) {
                                        $remaining = $wallet ? $wallet->quote_credits - 1 : -1;
                                        return $remaining > 0 
                                            ? "After submission, you will have {$remaining} credit" . ($remaining > 1 ? 's' : '') . " remaining."
                                            : "This will be your last available quote credit.";
                                    }),
                            ])
                            ->collapsed(),
                    ])
                    ->action(function (array $data, QuoteRequest $record) use ($businessId, $wallet, $hasCredits) {
                        // Double-check credits before submission
                        if (!$hasCredits) {
                            Notification::make()
                                ->danger()
                                ->title('Insufficient Quote Credits')
                                ->body('You need at least 1 quote credit to submit a quote. Please purchase quote credits first.')
                                ->send();
                            return;
                        }
                        
                        // Check if already submitted
                        $existing = QuoteResponse::where('quote_request_id', $record->id)
                            ->where('business_id', $businessId)
                            ->exists();
                        
                        if ($existing) {
                            Notification::make()
                                ->danger()
                                ->title('Already Submitted')
                                ->body('You have already submitted a quote for this request.')
                                ->send();
                            return;
                        }
                        
                        try {
                            DB::beginTransaction();
                            
                            // Create quote response
                            $quoteResponse = QuoteResponse::create([
                                'quote_request_id' => $record->id,
                                'business_id' => $businessId,
                                'price' => $data['price'],
                                'delivery_time' => $data['delivery_time'],
                                'message' => $data['message'],
                                'attachments' => $data['attachments'] ?? null,
                                'status' => 'submitted',
                            ]);
                            
                            // Deduct quote credit
                            $wallet->useQuoteCredit(
                                "Quote submission for request: {$record->title}",
                                $quoteResponse
                            );
                            
                            DB::commit();
                            
                            // Notify customer about new quote response
                            try {
                                $customer = $record->user;
                                $business = \App\Models\Business::find($businessId);
                                if ($customer && $business) {
                                    $preferences = $customer->preferences;
                                    
                                    // Send email/in-app notification if enabled
                                    if ($preferences && $preferences->notify_quote_responses) {
                                        $customer->notify(new \App\Notifications\NewQuoteResponseNotification($quoteResponse));
                                    }
                                    
                                    // Send Telegram notification if enabled
                                    if ($preferences && 
                                        $preferences->notify_quote_responses_telegram && 
                                        $preferences->telegram_notifications &&
                                        $preferences->getTelegramIdentifier()) {
                                        
                                        try {
                                            \Illuminate\Support\Facades\Log::info('Telegram quote response notification (pending API integration)', [
                                                'user_id' => $customer->id,
                                                'quote_response_id' => $quoteResponse->id,
                                                'telegram_id' => $preferences->getTelegramIdentifier(),
                                            ]);
                                        } catch (\Exception $e) {
                                            \Illuminate\Support\Facades\Log::error('Failed to send Telegram quote response notification', [
                                                'user_id' => $customer->id,
                                                'quote_response_id' => $quoteResponse->id,
                                                'error' => $e->getMessage(),
                                            ]);
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send quote response notification', [
                                    'quote_response_id' => $quoteResponse->id ?? null,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                            
                            $remainingCredits = $wallet->fresh()->quote_credits;
                            
                            Notification::make()
                                ->success()
                                ->title('Quote Submitted Successfully!')
                                ->body("Your quote has been sent to the customer. You have {$remainingCredits} quote credit" . ($remainingCredits != 1 ? 's' : '') . " remaining.")
                                ->send();
                            
                            // Refresh the page to update credit count
                            $this->dispatch('refresh');
                            
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->danger()
                                ->title('Submission Failed')
                                ->body('Failed to submit quote: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->modalWidth('3xl')
                    ->modalSubmitActionLabel('Submit Quote (1 Credit)')
                    ->modalHeading(fn ($record) => "Submit Quote: {$record->title}")
                    ->modalDescription('Complete the form below to submit your quote. This will cost 1 quote credit.')
                    ->slideOver(),
            ])
            ->emptyStateHeading('No Quote Requests Available')
            ->emptyStateDescription('There are currently no quote requests matching your business category and location. Check back soon!')
            ->emptyStateIcon('heroicon-o-inbox')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }
}