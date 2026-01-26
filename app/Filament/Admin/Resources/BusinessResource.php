<?php
// ============================================
// app/Filament/Admin/Resources/BusinessResource.php
// REFACTORED VERSION - Business can now have direct relationships including LEADS
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessResource\Pages;
use App\Filament\Admin\Resources\BusinessResource\RelationManagers;
use App\Models\Business;
use App\Models\Location;
use App\Models\User;
use App\Enums\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Businesses';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Wizard handled in CreateBusiness/EditBusiness pages
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
                    ->description(fn ($record) => Str::limit($record->description, 50))
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('businessType.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Multiple'),
                
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified')
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_premium')
                    ->boolean()
                    ->label('Premium')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_review',
                        'success' => 'active',
                        'danger' => 'suspended',
                        'secondary' => 'closed',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_'))),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('business_type')
                    ->relationship('businessType', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'closed' => 'Closed',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),
                
                Tables\Filters\TernaryFilter::make('is_premium')
                    ->label('Premium'),
                
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Direct relationships (for standalone businesses)
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\OfficialsRelationManager::class,
            RelationManagers\SocialAccountsRelationManager::class,
            RelationManagers\LeadsRelationManager::class, // NEW: Business can have leads
            RelationManagers\ReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
            'view' => Pages\ViewBusiness::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}