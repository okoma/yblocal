<?php
// ============================================
// app/Filament/Business/Resources/SubscriptionResource.php
// View and manage subscriptions
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\ActiveBusiness;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'My Subscription';

    protected static ?string $navigationGroup = 'Billing & Marketing';

    protected static ?int $navigationSort = 3;

    public static function getNavigationUrl(): string
    {
        $active = app(ActiveBusiness::class);
        $businessId = $active->getActiveBusinessId();
        
        if ($businessId === null) {
            return static::getUrl('index');
        }
            public static function canViewAny(): bool
{
    // Override Filament's default policy check for navigation
    // Business owners should see the navigation item
    return Auth::user()->isAdmin() 
        || Auth::user()->isModerator() 
        || Auth::user()->isBusinessOwner()
        || Auth::user()->isBusinessManager();
}
        // Get active subscription for current business
        $subscription = static::getModel()::where('user_id', auth()->id())
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();
        
        // If active subscription exists, go to view page
        if ($subscription) {
            return static::getUrl('view', ['record' => $subscription->id]);
        }
        
        // Otherwise, go to subscription plans page to subscribe
        return route('filament.business.pages.subscription-page');
    }

    public static function getEloquentQuery(): Builder
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        $query = parent::getEloquentQuery()->where('user_id', auth()->id())->with(['plan', 'business']);
        if ($id === null) {
            return $query->whereIn('business_id', []);
        }
        return $query->where('business_id', $id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->plan->description),

                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('billing_interval')
                    ->label('Billing')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'yearly' => 'success',
                        'monthly' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'cancelled', 'expired' => 'danger',
                        'paused' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Started')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Expires')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->daysRemaining() <= 7 ? 'danger' : null)
                    ->description(fn ($record) => $record->isActive() ? $record->daysRemaining() . ' days left' : null),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto Renew')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('NGN')
                    ->state(fn ($record) => $record->getPrice())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'trialing' => 'Trial',
                        'past_due' => 'Past Due',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        'paused' => 'Paused',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('auto_renew')
                    ->label('Auto Renewal')
                    ->placeholder('All subscriptions')
                    ->trueLabel('Auto-renew enabled')
                    ->falseLabel('Auto-renew disabled'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\Action::make('renew')
                        ->label('Renew')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->url(fn (Subscription $record) => static::getUrl('view', ['record' => $record->id]))
                        ->tooltip('Renew this subscription with payment')
                        ->visible(fn (Subscription $record) => $record->isActive()),

                    Tables\Actions\Action::make('toggle_auto_renew')
                        ->label(fn (Subscription $record) => $record->auto_renew ? 'Disable Auto-Renew' : 'Enable Auto-Renew')
                        ->icon('heroicon-o-arrow-path')
                        ->color(fn (Subscription $record) => $record->auto_renew ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Subscription $record) {
                            $record->update(['auto_renew' => !$record->auto_renew]);
                            
                            Notification::make()
                                ->success()
                                ->title('Auto-Renewal Updated')
                                ->body($record->auto_renew ? 'Auto-renewal enabled' : 'Auto-renewal disabled')
                                ->send();
                        })
                        ->visible(fn (Subscription $record) => $record->isActive()),

                    Tables\Actions\Action::make('pause')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Subscription $record) {
                            $record->pause();
                            
                            Notification::make()
                                ->success()
                                ->title('Subscription Paused')
                                ->send();
                        })
                        ->visible(fn (Subscription $record) => $record->status === 'active'),

                    Tables\Actions\Action::make('resume')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Subscription $record) {
                            $record->resume();
                            
                            Notification::make()
                                ->success()
                                ->title('Subscription Resumed')
                                ->send();
                        })
                        ->visible(fn (Subscription $record) => $record->status === 'paused'),

                    Tables\Actions\Action::make('cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Subscription?')
                        ->modalDescription('You will lose access to premium features at the end of your billing period.')
                        ->form([
                            Forms\Components\Textarea::make('cancellation_reason')
                                ->label('Reason for Cancellation (Optional)')
                                ->rows(3),
                        ])
                        ->action(function (Subscription $record, array $data) {
                            $record->cancel($data['cancellation_reason'] ?? null);
                            
                            Notification::make()
                                ->success()
                                ->title('Subscription Cancelled')
                                ->body('Your subscription will remain active until ' . $record->ends_at->format('M j, Y'))
                                ->send();
                        })
                        ->visible(fn (Subscription $record) => $record->isActive()),
                ]),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('No Active Subscriptions')
            ->emptyStateDescription('Subscribe to a plan to unlock premium features for your business.')
            ->emptyStateActions([
                Tables\Actions\Action::make('browse_plans')
                    ->label('Browse Plans')
                    ->url(fn () => route('filament.business.pages.subscription-page'))
                    ->icon('heroicon-o-sparkles'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'view' => Pages\ViewSubscription::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        if ($id === null) {
            return null;
        }
        $expiring = static::getModel()::where('user_id', auth()->id())
            ->where('business_id', $id)
            ->where('status', 'active')
            ->where('ends_at', '<=', now()->addDays(7))
            ->count();
        return $expiring > 0 ? (string) $expiring : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false; // Subscriptions created through plan purchase
    }
}