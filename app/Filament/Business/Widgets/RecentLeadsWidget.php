<?php

namespace App\Filament\Business\Widgets;

use App\Models\Lead;
use App\Services\ActiveBusiness;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentLeadsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;
    public function table(Table $table): Table
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        $query = Lead::query()->with(['business', 'user'])->latest()->limit(10);
        if ($id !== null) {
            $query->where('business_id', $id);
        } else {
            $query->whereIn('business_id', []);
        }

        return $table
            ->heading('Recent Leads')
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('lead_button_text')
                    ->label('Type')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->icon('heroicon-o-map-pin'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'new',
                        'info' => 'contacted',
                        'success' => 'converted',
                        'danger' => 'rejected',
                    ]),
                
                Tables\Columns\IconColumn::make('is_replied')
                    ->boolean()
                    ->label('Replied'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Lead $record): string => route('filament.business.resources.leads.view', $record))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}