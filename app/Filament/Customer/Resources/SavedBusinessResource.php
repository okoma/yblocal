<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\SavedBusinessResource\Pages;
use App\Models\Business;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SavedBusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    
    protected static ?string $navigationLabel = 'Saved Businesses';
    
    protected static ?string $modelLabel = 'Saved Business';
    
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Only show businesses saved by the authenticated user
        return parent::getEloquentQuery()
            ->whereHas('savedByUsers', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->where('status', 'active');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->business_name)),
                
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->businessType?->name)
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->icon('heroicon-o-map-pin')
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->copyMessage('Phone number copied!')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('avg_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'No ratings')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_type')
                    ->relationship('businessType', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('state')
                    ->relationship('stateLocation', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Business $record): string => $record->getUrl())
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('call')
                    ->label('Call')
                    ->icon('heroicon-o-phone')
                    ->url(fn (Business $record): ?string => $record->phone ? 'tel:' . $record->phone : null)
                    ->visible(fn (Business $record): bool => !empty($record->phone))
                    ->color('success'),
                
                Tables\Actions\Action::make('unsave')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove from Saved')
                    ->modalDescription('Are you sure you want to remove this business from your saved list?')
                    ->action(function (Business $record) {
                        $record->savedByUsers()->detach(Auth::id());
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Business removed')
                            ->body('The business has been removed from your saved list.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('remove_all')
                        ->label('Remove from Saved')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->savedByUsers()->detach(Auth::id());
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Businesses removed')
                                ->body(count($records) . ' businesses have been removed from your saved list.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No saved businesses yet')
            ->emptyStateDescription('Start exploring and save businesses you like!')
            ->emptyStateIcon('heroicon-o-heart');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavedBusinesses::route('/'),
        ];
    }
}
