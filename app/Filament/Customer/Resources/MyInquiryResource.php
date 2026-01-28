<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\MyInquiryResource\Pages;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyInquiryResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationLabel = 'My Inquiries';
    
    protected static ?string $modelLabel = 'Inquiry';
    
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        // Only show leads/inquiries created by the authenticated user
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->with('business');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inquiry Details')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->label('Your Name')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('lead_button_text')
                            ->label('Inquiry Type')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\KeyValue::make('custom_fields')
                            ->label('Additional Information')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Business Response')
                    ->schema([
                        Forms\Components\Placeholder::make('status')
                            ->label('Status')
                            ->content(fn ($record) => ucfirst(str_replace('_', ' ', $record->status))),
                        
                        Forms\Components\Placeholder::make('replied_at')
                            ->label('Replied On')
                            ->content(fn ($record) => $record->replied_at ? $record->replied_at->format('M d, Y g:i A') : 'Not yet replied'),
                        
                        Forms\Components\Textarea::make('reply_message')
                            ->label('Response Message')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('No response yet'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn ($record) => $record->business?->getUrl())
                    ->openUrlInNewTab(),
                
                Tables\Columns\TextColumn::make('lead_button_text')
                    ->label('Inquiry Type')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'new',
                        'info' => 'contacted',
                        'primary' => 'qualified',
                        'success' => 'converted',
                        'danger' => 'lost',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                
                Tables\Columns\IconColumn::make('is_replied')
                    ->label('Replied')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('M d, Y g:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'converted' => 'Converted',
                        'lost' => 'Lost',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_replied')
                    ->label('Business Reply')
                    ->placeholder('All')
                    ->trueLabel('With Reply')
                    ->falseLabel('No Reply'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('view_business')
                    ->label('View Business')
                    ->icon('heroicon-o-building-storefront')
                    ->url(fn ($record) => $record->business?->getUrl())
                    ->openUrlInNewTab()
                    ->color('info'),
            ])
            ->emptyStateHeading('No inquiries yet')
            ->emptyStateDescription('Contact businesses to start your inquiry!')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyInquiries::route('/'),
            'view' => Pages\ViewMyInquiry::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Inquiries are created from business pages, not here
    }
}
