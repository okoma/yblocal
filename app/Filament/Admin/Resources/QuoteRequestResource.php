<?php
// ============================================
// app/Filament/Admin/Resources/QuoteRequestResource.php
// Location: app/Filament/Admin/Resources/QuoteRequestResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: System-wide quote request management
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\QuoteRequestResource\Pages;
use App\Models\QuoteRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Quote Requests';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 8;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'category', 'stateLocation', 'cityLocation', 'responses.business']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Request Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Customer')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('description')
                        ->required()
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    
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
                            return $query->whereRaw('1 = 0');
                        })
                        ->searchable()
                        ->preload(),
                    
                    Forms\Components\Select::make('status')
                        ->options([
                            'open' => 'Open',
                            'closed' => 'Closed',
                            'expired' => 'Expired',
                            'accepted' => 'Accepted',
                        ])
                        ->required()
                        ->default('open'),
                    
                    Forms\Components\DatePicker::make('expires_at')
                        ->label('Expires On')
                        ->minDate(now()->addDay()),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Budget')
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
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                
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
                    ->placeholder('Whole state'),
                
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Quotes')
                    ->counts('responses')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
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
                
                Tables\Filters\Filter::make('expired')
                    ->label('Expired Requests')
                    ->query(fn (Builder $query) => $query->where('status', 'expired'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_expired')
                    ->label('Mark Expired')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (QuoteRequest $record) {
                        $record->markAsExpired();
                        Notification::make()
                            ->title('Request marked as expired')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (QuoteRequest $record) => $record->status === 'open' && $record->isExpired()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_expired')
                        ->label('Mark as Expired')
                        ->icon('heroicon-o-clock')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'open' && $record->isExpired()) {
                                    $record->markAsExpired();
                                }
                            }
                            Notification::make()
                                ->title('Selected requests marked as expired')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Request Information')
                    ->schema([
                        Components\TextEntry::make('title')
                            ->label('Title')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('user.name')
                            ->label('Customer'),
                        
                        Components\TextEntry::make('category.name')
                            ->label('Category')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('stateLocation.name')
                            ->label('State'),
                        
                        Components\TextEntry::make('cityLocation.name')
                            ->label('City')
                            ->placeholder('Whole state'),
                        
                        Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'open' => 'success',
                                'closed' => 'gray',
                                'expired' => 'warning',
                                'accepted' => 'primary',
                                default => 'gray',
                            }),
                        
                        Components\TextEntry::make('expires_at')
                            ->label('Expires On')
                            ->dateTime()
                            ->placeholder('No expiration'),
                    ])
                    ->columns(2),
                
                Components\Section::make('Budget')
                    ->schema([
                        Components\TextEntry::make('budget_min')
                            ->label('Minimum Budget')
                            ->money('NGN')
                            ->placeholder('Not specified'),
                        
                        Components\TextEntry::make('budget_max')
                            ->label('Maximum Budget')
                            ->money('NGN')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->budget_min || $record->budget_max),
                
                Components\Section::make('Statistics')
                    ->schema([
                        Components\TextEntry::make('responses_count')
                            ->label('Total Quotes')
                            ->state(fn ($record) => $record->responses()->count())
                            ->badge()
                            ->color('success'),
                        
                        Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuoteRequestResource\RelationManagers\ResponsesRelationManager::class,
        ];
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
}
