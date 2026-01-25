<?php

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\BusinessManagerResource\Pages;
use App\Models\BusinessManager;
use App\Services\ActiveBusiness;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BusinessManagerResource extends Resource
{
    protected static ?string $model = BusinessManager::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'My Managers';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Manager Details')
                    ->schema([
                        Forms\Components\Select::make('business_id')
                            ->label('Business')
                            ->options(function () {
                                $active = app(ActiveBusiness::class);
                                $b = $active->getActiveBusiness();
                                return $b ? [$b->id => $b->business_name] : [];
                            })
                            ->default(fn () => app(ActiveBusiness::class)->getActiveBusinessId())
                            ->required()
                            ->disabled(fn($context) => $context === 'edit'),
                        
                        Forms\Components\Select::make('user_id')
                            ->label('Manager')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn($context) => $context === 'edit')
                            ->helperText('Select the user who will manage this business'),
                        
                        Forms\Components\TextInput::make('position')
                            ->label('Position Title')
                            ->default('Business Manager')
                            ->maxLength(255)
                            ->helperText('e.g., Marketing Manager, Operations Manager'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Permissions')
                    ->description('Select what this manager can do')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('')
                            ->options([
                                'can_edit_business' => 'Edit Business Information',
                                'can_manage_products' => 'Manage Products & Services',
                                'can_respond_to_reviews' => 'Respond to Customer Reviews',
                                'can_view_leads' => 'View Customer Leads',
                                'can_respond_to_leads' => 'Respond to Customer Leads',
                                'can_view_analytics' => 'View Analytics & Reports',
                                'can_access_financials' => 'Access Financial Data',
                                'can_manage_staff' => 'Manage Staff Members',
                            ])
                            ->default([
                                'can_manage_products',
                                'can_respond_to_reviews',
                                'can_view_leads',
                                'can_respond_to_leads',
                                'can_view_analytics',
                            ])
                            ->columns(2)
                            ->bulkToggleable()
                            ->afterStateHydrated(function ($component, $state) {
                                if ($state && is_array($state)) {
                                    $granted = array_keys(array_filter($state));
                                    $component->state($granted);
                                }
                            })
                            ->dehydratedUsing(function ($state) {
                                $permissions = [];
                                $allPermissions = [
                                    'can_edit_business',
                                    'can_manage_products',
                                    'can_respond_to_reviews',
                                    'can_view_leads',
                                    'can_respond_to_leads',
                                    'can_view_analytics',
                                    'can_access_financials',
                                    'can_manage_staff',
                                ];
                                
                                foreach ($allPermissions as $permission) {
                                    $permissions[$permission] = in_array($permission, $state ?? []);
                                }
                                
                                return $permissions;
                            }),
                    ]),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Deactivate to revoke access temporarily'),
                        
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Manager')
                            ->helperText('Mark as primary manager for this business'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        $query = BusinessManager::query();
        if ($id === null) {
            $query->whereIn('business_id', []);
        } else {
            $query->where('business_id', $id);
        }
        return $table->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Manager')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('permissions')
                    ->label('Permissions')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'None';
                        
                        $permissionLabels = [
                            'can_edit_business' => 'Edit Business',
                            'can_manage_products' => 'Manage Products',
                            'can_respond_to_reviews' => 'Respond Reviews',
                            'can_view_leads' => 'View Leads',
                            'can_respond_to_leads' => 'Respond Leads',
                            'can_view_analytics' => 'Analytics',
                            'can_access_financials' => 'Financials',
                            'can_manage_staff' => 'Manage Staff',
                        ];
                        
                        $granted = array_filter($state);
                        $labels = array_map(fn($key) => $permissionLabels[$key] ?? $key, array_keys($granted));
                        
                        return implode(', ', $labels) ?: 'None';
                    })
                    ->wrap()
                    ->limit(50)
                    ->tooltip(function ($record) {
                        $permissionLabels = [
                            'can_edit_business' => 'Edit Business Information',
                            'can_manage_products' => 'Manage Products & Services',
                            'can_respond_to_reviews' => 'Respond to Customer Reviews',
                            'can_view_leads' => 'View Customer Leads',
                            'can_respond_to_leads' => 'Respond to Customer Leads',
                            'can_view_analytics' => 'View Analytics & Reports',
                            'can_access_financials' => 'Access Financial Data',
                            'can_manage_staff' => 'Manage Staff Members',
                        ];
                        
                        $granted = array_filter($record->permissions ?? []);
                        $labels = array_map(fn($key) => $permissionLabels[$key] ?? $key, array_keys($granted));
                        
                        return implode("\n", $labels) ?: 'No permissions';
                    }),
                
                Tables\Columns\TextColumn::make('joined_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
                
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Only'),
                
                Tables\Filters\Filter::make('has_permission')
                    ->label('Has Permission')
                    ->form([
                        Forms\Components\Select::make('permission')
                            ->options([
                                'can_edit_business' => 'Edit Business',
                                'can_manage_products' => 'Manage Products',
                                'can_respond_to_reviews' => 'Respond Reviews',
                                'can_view_leads' => 'View Leads',
                                'can_respond_to_leads' => 'Respond Leads',
                                'can_view_analytics' => 'View Analytics',
                                'can_access_financials' => 'Access Financials',
                                'can_manage_staff' => 'Manage Staff',
                            ])
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['permission'])) {
                            return $query;
                        }
                        
                        return $query->whereJsonContains('permissions->' . $data['permission'], true);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->is_active)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->deactivate();
                        Notification::make()
                            ->title('Manager deactivated')
                            ->body($record->user->name . ' can no longer access this business.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => !$record->is_active)
                    ->action(function ($record) {
                        $record->activate();
                        Notification::make()
                            ->title('Manager activated')
                            ->body($record->user->name . ' can now access this business.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('set_primary')
                    ->label('Set as Primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn($record) => !$record->is_primary)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->setAsPrimary();
                        Notification::make()
                            ->title('Primary manager set')
                            ->body($record->user->name . ' is now the primary manager.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Remove Manager')
                    ->modalDescription('Are you sure you want to remove this manager? They will lose all access to this business.')
                    ->modalSubmitActionLabel('Yes, Remove Manager'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->activate())
                        ->requiresConfirmation(),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($records) => $records->each->deactivate())
                        ->requiresConfirmation(),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessManagers::route('/'),
            'create' => Pages\CreateBusinessManager::route('/create'),
            'edit' => Pages\EditBusinessManager::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return Auth::user()->isBusinessOwner();
    }
}
