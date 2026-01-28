<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\QuoteRequestResource\Pages;
use App\Models\QuoteRequest;
use App\Models\Category;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Quote Requests';
    
    protected static ?string $modelLabel = 'Quote Request';
    
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->with(['category', 'stateLocation', 'cityLocation', 'responses.business']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Need catering for 100 guests')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000)
                            ->placeholder('Describe what you need in detail...')
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Select::make('state_location_id')
                            ->label('State')
                            ->relationship('stateLocation', 'name', fn ($query) => $query->where('type', 'state'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('city_location_id', null)),
                        
                        Forms\Components\Select::make('city_location_id')
                            ->label('City (Optional)')
                            ->relationship('cityLocation', 'name', function ($query, Forms\Get $get) {
                                $stateId = $get('state_location_id');
                                if ($stateId) {
                                    return $query->where('parent_id', $stateId)->where('type', 'city');
                                }
                                return $query->whereRaw('1 = 0'); // Empty if no state selected
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty to target the whole state'),
                        
                        Forms\Components\DatePicker::make('expires_at')
                            ->label('Expires On')
                            ->minDate(now()->addDay())
                            ->helperText('Leave empty for no expiration'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Budget (Optional)')
                    ->schema([
                        Forms\Components\TextInput::make('budget_min')
                            ->label('Minimum Budget')
                            ->numeric()
                            ->prefix('₦')
                            ->minValue(0),
                        
                        Forms\Components\TextInput::make('budget_max')
                            ->label('Maximum Budget')
                            ->numeric()
                            ->prefix('₦')
                            ->minValue(0),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                
                Forms\Components\Section::make('Attachments (Optional)')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Upload Files')
                            ->multiple()
                            ->directory('quote-requests')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),
                
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
                
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Quotes')
                    ->counts('responses')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'open',
                        'gray' => 'closed',
                        'warning' => 'expired',
                        'primary' => 'accepted',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('No expiration'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                        'expired' => 'Expired',
                        'accepted' => 'Accepted',
                    ])
                    ->multiple(),
                
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'open'),
            ])
            ->emptyStateHeading('No quote requests yet')
            ->emptyStateDescription('Create your first quote request to get quotes from businesses!')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuoteRequests::route('/'),
            'create' => Pages\CreateQuoteRequest::route('/create'),
            'view' => Pages\ViewQuoteRequest::route('/{record}'),
            'edit' => Pages\EditQuoteRequest::route('/{record}/edit'),
        ];
    }

    /**
     * Show count of new quote responses for customer's quote requests
     */
    public static function getNavigationBadge(): ?string
    {
        $newResponsesCount = \App\Models\QuoteResponse::whereHas('quoteRequest', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->where('status', 'submitted')
        ->count();

        return $newResponsesCount > 0 ? (string) $newResponsesCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
