<?php

namespace App\Filament\Business\Resources\QuoteResponseResource\Pages;

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
    
    protected static string $view = 'filament.business.pages.available-quote-requests';
    
    protected static ?string $navigationLabel = 'Available Requests';
    
    protected static ?string $title = 'Available Quote Requests';
    
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    
    protected static ?string $navigationGroup = 'Sales & Marketing';
    
    protected static ?int $navigationSort = 6;
    
    protected static bool $shouldRegisterNavigation = true;
    
    public function getHeading(): string
    {
        return 'Available Quote Requests';
    }
    
    public function getSubheading(): ?string
    {
        return 'Browse and submit quotes for requests matching your business. Each submission costs 1 quote credit.';
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
        
        return $table
            ->query(QuoteRequest::query()->whereIn('id', $requestIds))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Request')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(100)
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('stateLocation.name')
                    ->label('State')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('cityLocation.name')
                    ->label('City')
                    ->placeholder('Whole state')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('budget_min')
                    ->label('Budget')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->budget_min && $record->budget_max) {
                            return '₦' . number_format($record->budget_min, 0) . ' - ₦' . number_format($record->budget_max, 0);
                        } elseif ($record->budget_min) {
                            return 'From ₦' . number_format($record->budget_min, 0);
                        } elseif ($record->budget_max) {
                            return 'Up to ₦' . number_format($record->budget_max, 0);
                        }
                        return 'Not specified';
                    })
                    ->placeholder('Not specified'),
                
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Quotes')
                    ->counts('responses')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('No expiration'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime('M d, Y')
                    ->sortable(),
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
                    ->label('Submit Quote')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        Forms\Components\Placeholder::make('request_info')
                            ->label('Request Details')
                            ->content(function ($record) {
                                return view('filament.business.components.quote-request-summary', [
                                    'request' => $record
                                ])->render();
                            })
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('price')
                            ->label('Your Price')
                            ->required()
                            ->numeric()
                            ->prefix('₦')
                            ->minValue(0)
                            ->step(0.01),
                        
                        Forms\Components\TextInput::make('delivery_time')
                            ->label('Delivery Time')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., 2-3 weeks, 1 month')
                            ->helperText('Estimated time to complete the work'),
                        
                        Forms\Components\Textarea::make('message')
                            ->label('Proposal Message')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Write a brief proposal explaining your approach...')
                            ->helperText('This message will be visible to the customer')
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Attachments (Optional)')
                            ->multiple()
                            ->directory('quote-responses')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, QuoteRequest $record) {
                        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
                        
                        if (!$businessId) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No active business selected')
                                ->send();
                            return;
                        }
                        
                        $wallet = Wallet::where('business_id', $businessId)->first();
                        
                        if (!$wallet || !$wallet->hasQuoteCredits()) {
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
                            
                            Notification::make()
                                ->success()
                                ->title('Quote Submitted!')
                                ->body('Your quote has been submitted successfully. 1 quote credit deducted.')
                                ->send();
                            
                            // Refresh the table
                            $this->dispatch('refresh-table');
                            
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Failed to submit quote: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Submit Quote')
                    ->modalHeading(fn ($record) => "Submit Quote: {$record->title}"),
            ])
            ->emptyStateHeading('No available quote requests')
            ->emptyStateDescription('New quote requests matching your business category and location will appear here')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
