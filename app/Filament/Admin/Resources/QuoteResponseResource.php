<?php
// ============================================
// app/Filament/Admin/Resources/QuoteResponseResource.php
// Location: app/Filament/Admin/Resources/QuoteResponseResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: System-wide quote response management
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\QuoteResponseResource\Pages;
use App\Models\QuoteResponse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class QuoteResponseResource extends Resource
{
    protected static ?string $model = QuoteResponse::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Quote Responses';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 9;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['quoteRequest.user', 'quoteRequest.category', 'business']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Response Details')
                ->schema([
                    Forms\Components\Select::make('quote_request_id')
                        ->label('Quote Request')
                        ->relationship('quoteRequest', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\Select::make('business_id')
                        ->label('Business')
                        ->relationship('business', 'business_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\TextInput::make('price')
                        ->label('Price')
                        ->required()
                        ->numeric()
                        ->prefix('₦')
                        ->minValue(0)
                        ->step(0.01),
                    
                    Forms\Components\TextInput::make('delivery_time')
                        ->label('Delivery Time')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g., 2-3 weeks, 1 month'),
                    
                    Forms\Components\Textarea::make('message')
                        ->label('Proposal Message')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('status')
                        ->options([
                            'submitted' => 'Submitted',
                            'shortlisted' => 'Shortlisted',
                            'accepted' => 'Accepted',
                            'rejected' => 'Rejected',
                        ])
                        ->required()
                        ->default('submitted'),
                ])
                ->columns(2),
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
                
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('quoteRequest.category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
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
                
                Tables\Filters\SelectFilter::make('quote_request_id')
                    ->label('Quote Request')
                    ->relationship('quoteRequest', 'title')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'business_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Response Information')
                    ->schema([
                        Components\TextEntry::make('quoteRequest.title')
                            ->label('Quote Request')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('business.business_name')
                            ->label('Business')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('price')
                            ->label('Price')
                            ->money('NGN')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),
                        
                        Components\TextEntry::make('delivery_time')
                            ->label('Delivery Time'),
                        
                        Components\TextEntry::make('message')
                            ->label('Proposal Message')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'submitted' => 'gray',
                                'shortlisted' => 'info',
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),
                
                Components\Section::make('Request Details')
                    ->schema([
                        Components\TextEntry::make('quoteRequest.user.name')
                            ->label('Customer'),
                        
                        Components\TextEntry::make('quoteRequest.category.name')
                            ->label('Category')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('quoteRequest.stateLocation.name')
                            ->label('State'),
                        
                        Components\TextEntry::make('quoteRequest.cityLocation.name')
                            ->label('City')
                            ->placeholder('Whole state'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Timeline')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuoteResponses::route('/'),
            'create' => Pages\CreateQuoteResponse::route('/create'),
            'view' => Pages\ViewQuoteResponse::route('/{record}'),
            'edit' => Pages\EditQuoteResponse::route('/{record}/edit'),
        ];
    }
}
