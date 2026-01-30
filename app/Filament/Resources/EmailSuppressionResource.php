<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailSuppressionResource\Pages;
use App\Models\EmailSuppression;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextInputColumn;

class EmailSuppressionResource extends Resource
{
    protected static ?string $model = EmailSuppression::class;

    protected static ?string $navigationIcon = 'heroicon-o-mail';

    public static function form(Forms\Components\Form $form): Forms\Components\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('email')->required()->email()->disabled(),
            Forms\Components\TextInput::make('reason')->disabled(),
            Forms\Components\TextInput::make('source')->disabled(),
        ]);
    }

    public static function table(Tables\Contracts\Table $table): Tables\Contracts\Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('reason')->limit(40),
                TextColumn::make('source'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailSuppressions::route('/'),
        ];
    }
}
