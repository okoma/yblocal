<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WithdrawalRequestResource\Pages;
use App\Models\WithdrawalRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class WithdrawalRequestResource extends Resource
{
    protected static ?string $model = WithdrawalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Withdrawal Requests';

    protected static ?string $navigationGroup = 'Financial Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Select::make('wallet_id')
                            ->label('Wallet')
                            ->relationship('wallet', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->prefix('₦')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Bank Details')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('account_name')
                            ->label('Account Name')
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('account_number')
                            ->label('Account Number')
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('sort_code')
                            ->label('Sort Code')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Processing')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === 'approved' || $state === 'rejected') {
                                    $set('processed_at', now());
                                    $set('processed_by', auth()->id());
                                }
                            }),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'rejected')
                            ->required(fn (Forms\Get $get) => $get('status') === 'rejected'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->helperText('Internal notes (not visible to user)'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable(),

                Tables\Columns\TextColumn::make('account_name')
                    ->label('Account Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('account_number')
                    ->label('Account Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved', 'completed' => 'success',
                        'rejected', 'failed' => 'danger',
                        'processing' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('processor.name')
                    ->label('Processed By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('pending_only')
                    ->label('Pending Only')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Withdrawal Request')
                        ->modalDescription('This will approve the withdrawal request. The funds will be transferred to the bank account.')
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('Admin Notes (Optional)')
                                ->rows(3),
                        ])
                        ->action(function (WithdrawalRequest $record, array $data) {
                            $record->approve(auth()->user(), $data['notes'] ?? null);
                            
                            Notification::make()
                                ->success()
                                ->title('Withdrawal Approved')
                                ->body('The withdrawal request has been approved.')
                                ->send();
                        })
                        ->visible(fn (WithdrawalRequest $record) => $record->isPending()),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Withdrawal Request')
                        ->modalDescription('This will reject the withdrawal request. The user will be notified.')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3)
                                ->helperText('This reason will be shown to the user.'),

                            Forms\Components\Textarea::make('notes')
                                ->label('Admin Notes (Optional)')
                                ->rows(3),
                        ])
                        ->action(function (WithdrawalRequest $record, array $data) {
                            $record->reject(auth()->user(), $data['rejection_reason'], $data['notes'] ?? null);
                            
                            // TODO: Refund amount back to wallet if already deducted
                            // TODO: Send notification to user
                            
                            Notification::make()
                                ->success()
                                ->title('Withdrawal Rejected')
                                ->body('The withdrawal request has been rejected.')
                                ->send();
                        })
                        ->visible(fn (WithdrawalRequest $record) => $record->isPending()),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Withdrawal Requests')
            ->emptyStateDescription('Withdrawal requests from users will appear here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawalRequests::route('/'),
            'view' => Pages\ViewWithdrawalRequest::route('/{record}'),
            'edit' => Pages\EditWithdrawalRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
