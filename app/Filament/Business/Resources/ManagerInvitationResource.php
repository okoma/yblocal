<?php

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\ManagerInvitationResource\Pages;
use App\Models\ManagerInvitation;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ManagerInvitationResource extends Resource
{
    protected static ?string $model = ManagerInvitation::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    
    protected static ?string $navigationLabel = 'Invite Managers';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invitation Details')
                    ->schema([
                        Forms\Components\Select::make('business_id')
                            ->label('Business')
                            ->relationship(
                                'business',
                                'business_name',
                                fn($query) => $query->where('user_id', Auth::id())
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn($context) => $context === 'edit'),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Manager Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn($context) => $context === 'edit')
                            ->helperText('The email address of the person you want to invite'),
                        
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
                            ->bulkToggleable(),
                    ]),
                
                Forms\Components\Section::make('Expiration')
                    ->schema([
                        Forms\Components\DatePicker::make('expires_at')
                            ->label('Invitation Expires')
                            ->default(now()->addDays(7))
                            ->required()
                            ->helperText('The invitation will expire after this date'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                ManagerInvitation::query()
                    ->whereHas('business', function ($query) {
                        $query->where('user_id', Auth::id());
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Invited Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'accepted',
                        'danger' => 'declined',
                        'secondary' => 'expired',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),
                
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
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn($record) => $record->expires_at->isPast() ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('accepted_at')
                    ->label('Accepted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'expired' => 'Expired',
                    ]),
                
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'business_name', fn($query) => $query->where('user_id', Auth::id()))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'pending' && $record->expires_at->isFuture())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // In a real app, you'd send an email here
                        Notification::make()
                            ->title('Invitation link')
                            ->body('Copy this link to send: ' . route('manager.invitation.accept', $record->invitation_token))
                            ->persistent()
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('copy_link')
                    ->label('Copy Link')
                    ->icon('heroicon-o-clipboard')
                    ->color('info')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        return route('manager.invitation.accept', $record->invitation_token);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Invitation Link')
                    ->modalDescription(fn($record) => route('manager.invitation.accept', $record->invitation_token))
                    ->modalSubmitActionLabel('Copy Link'),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagerInvitations::route('/'),
            'create' => Pages\CreateManagerInvitation::route('/create'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return Auth::user()->isBusinessOwner();
    }
}
