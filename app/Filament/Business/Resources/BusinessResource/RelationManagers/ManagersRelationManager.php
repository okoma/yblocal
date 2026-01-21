<?php

namespace App\Filament\Business\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessManager;
use App\Models\ManagerInvitation;
use Illuminate\Support\Str;

class ManagersRelationManager extends RelationManager
{
    protected static string $relationship = 'managerAssignments';

    protected static ?string $title = 'Managers';

    protected static ?string $recordTitleAttribute = 'user.name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Manager Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn($context) => $context === 'edit'),
                        
                        Forms\Components\TextInput::make('position')
                            ->label('Position Title')
                            ->default('Business Manager')
                            ->maxLength(255),
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
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
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('permissions')
                    ->label('Permissions')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'None';
                        
                        // Handle case where permissions might be a JSON string
                        if (is_string($state)) {
                            $state = json_decode($state, true);
                        }
                        
                        // Ensure it's an array
                        if (!is_array($state)) {
                            return 'None';
                        }
                        
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
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('joined_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
                
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Only'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('invite')
                    ->label('Invite Manager')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('position')
                            ->label('Position Title')
                            ->default('Business Manager')
                            ->maxLength(255),
                        
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Permissions')
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
                            ->bulkToggleable(),
                    ])
                    ->action(function (array $data, $livewire) {
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
                            $permissions[$permission] = in_array($permission, $data['permissions'] ?? []);
                        }
                        
                        ManagerInvitation::create([
                            'business_id' => $livewire->ownerRecord->id,
                            'invited_by' => auth()->id(),
                            'email' => $data['email'],
                            'invitation_token' => Str::random(64),
                            'position' => $data['position'] ?? 'Business Manager',
                            'permissions' => $permissions,
                            'status' => 'pending',
                            'expires_at' => now()->addDays(7),
                        ]);
                        
                        Notification::make()
                            ->title('Invitation sent')
                            ->body('Manager invitation has been sent to ' . $data['email'])
                            ->success()
                            ->send();
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
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // Only business owners can manage managers
        return $ownerRecord->user_id === auth()->id();
    }
}
