<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\MyReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationLabel = 'My Reviews';
    
    protected static ?string $modelLabel = 'Review';
    
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        // Only show reviews created by the authenticated user
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->with('reviewable');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Details')
                    ->schema([
                        Forms\Components\Select::make('rating')
                            ->label('Rating')
                            ->required()
                            ->options([
                                5 => '⭐⭐⭐⭐⭐ Excellent',
                                4 => '⭐⭐⭐⭐ Good',
                                3 => '⭐⭐⭐ Average',
                                2 => '⭐⭐ Below Average',
                                1 => '⭐ Poor',
                            ]),
                        
                        Forms\Components\Textarea::make('comment')
                            ->label('Your Review')
                            ->required()
                            ->rows(5)
                            ->maxLength(1000)
                            ->placeholder('Share your experience with this business...')
                            ->helperText('Be honest and detailed to help others make informed decisions.'),
                        
                        Forms\Components\FileUpload::make('photos')
                            ->label('Photos (Optional)')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(3072)
                            ->directory('review-photos')
                            ->helperText('Add up to 5 photos to support your review'),
                    ]),
                
                Forms\Components\Section::make('Business Reply')
                    ->schema([
                        Forms\Components\Placeholder::make('reply')
                            ->label('Business Response')
                            ->content(fn ($record) => $record->reply ?? 'No response yet'),
                        
                        Forms\Components\Placeholder::make('replied_at')
                            ->label('Replied On')
                            ->content(fn ($record) => $record->replied_at ? $record->replied_at->format('M d, Y g:i A') : 'N/A'),
                    ])
                    ->visible(fn ($record) => $record && $record->reply)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reviewable.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->reviewable?->businessType?->name),
                
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', $state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('comment')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record) => $record->is_approved ? 'Published' : 'Pending approval'),
                
                Tables\Columns\IconColumn::make('reply')
                    ->label('Business Replied')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars',
                        4 => '4 Stars',
                        3 => '3 Stars',
                        2 => '2 Stars',
                        1 => '1 Star',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Status')
                    ->placeholder('All reviews')
                    ->trueLabel('Published')
                    ->falseLabel('Pending'),
                
                Tables\Filters\TernaryFilter::make('reply')
                    ->label('Business Reply')
                    ->placeholder('All')
                    ->trueLabel('With Reply')
                    ->falseLabel('No Reply')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('reply'),
                        false: fn (Builder $query) => $query->whereNull('reply'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->is_approved), // Can only edit if not yet approved
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No reviews yet')
            ->emptyStateDescription('Start reviewing businesses to help others!')
            ->emptyStateIcon('heroicon-o-star');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyReviews::route('/'),
            'view' => Pages\ViewMyReview::route('/{record}'),
            'edit' => Pages\EditMyReview::route('/{record}/edit'),
        ];
    }
}
