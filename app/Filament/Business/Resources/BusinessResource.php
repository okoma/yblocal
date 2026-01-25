<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource.php
// Complete Business Management Resource with Wizard
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\BusinessResource\Pages;
use App\Filament\Business\Resources\BusinessResource\RelationManagers;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Amenity;
use App\Services\ActiveBusiness;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationLabel = 'My Business';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Wizard handled in CreateBusiness page
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        
        // Show businesses owned by user OR managed by user
        $query = Business::query()
            ->with('businessType') // Eager load business type to prevent N+1 queries
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('managers', function ($query) use ($user) {
                      $query->where('user_id', $user->id);
                  });
            });
        
        return $table
            ->query($query)
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->business_name)),
                
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('businessType.name')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->icon('heroicon-o-map-pin'),
           
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending_review',
                        'success' => 'active',
                        'danger' => 'suspended',
                    ]),
                
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified'),
                
                Tables\Columns\IconColumn::make('is_premium')
                    ->boolean()
                    ->label('Premium'),
                
                Tables\Columns\TextColumn::make('avg_rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ⭐')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_reviews')
                    ->sortable()
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified Only'),
                
                Tables\Filters\TernaryFilter::make('is_premium')
                    ->label('Premium Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'view' => Pages\ViewBusiness::route('/{record}'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
        ];
    }

    /**
     * Nav links to view of active business (dropdown = business list).
     * No active business → select-business page.
     */
    public static function getNavigationUrl(): string
    {
        $active = app(ActiveBusiness::class);
        $id = $active->getActiveBusinessId();
        if ($id && $active->isValid($id)) {
            return static::getUrl('view', ['record' => $id]);
        }
        return route('filament.business.pages.select-business');
    }
}