<?php

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\QuoteResponseResource\Pages;
use App\Models\QuoteResponse;
use App\Services\ActiveBusiness;
use App\Services\QuoteDistributionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuoteResponseResource extends Resource
{
    protected static ?string $model = QuoteResponse::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'My Quotes';
    
    protected static ?string $modelLabel = 'My Submissions';
    
    protected static ?string $navigationGroup = 'Quotes';
    
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        
        if (!$businessId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        
        return parent::getEloquentQuery()
            ->where('business_id', $businessId)
            ->with(['quoteRequest.category', 'quoteRequest.stateLocation', 'quoteRequest.cityLocation', 'quoteRequest.user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('quote_request_id')
                    ->label('Quote Request')
                    ->options(function () {
                        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
                        if (!$businessId) {
                            return [];
                        }
                        
                        $distributionService = app(QuoteDistributionService::class);
                        $business = \App\Models\Business::find($businessId);
                        
                        if (!$business) {
                            return [];
                        }
                        
                        $availableRequests = $distributionService->getAvailableQuoteRequests($business);
                        
                        return $availableRequests->pluck('title', 'id')->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(fn ($record) => $record === null),
                
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
                    ->helperText('This message will be visible to the customer'),
                
                Forms\Components\FileUpload::make('attachments')
                    ->label('Attachments (Optional)')
                    ->multiple()
                    ->directory('quote-responses')
                    ->visibility('private')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->maxSize(5120) // 5MB
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quoteRequest.title')
                    ->label('Request')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('quoteRequest.category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('quoteRequest.stateLocation.name')
                    ->label('State'),
                
                Tables\Columns\TextColumn::make('quoteRequest.cityLocation.name')
                    ->label('City')
                    ->placeholder('Whole state'),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Your Price')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('delivery_time')
                    ->label('Delivery Time'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'submitted',
                        'info' => 'shortlisted',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'shortlisted' => 'Shortlisted',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->emptyStateHeading('No quotes submitted yet')
            ->emptyStateDescription('Browse available quote requests and submit your quotes!')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuoteResponses::route('/'),
            'view' => Pages\ViewQuoteResponse::route('/{record}'),
        ];
    }
}
