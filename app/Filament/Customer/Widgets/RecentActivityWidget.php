<?php

namespace App\Filament\Customer\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecentActivityWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Activity')
            ->query(
                $this->getRecentActivityQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'danger' => 'Saved Business',
                        'warning' => 'Review',
                        'info' => 'Inquiry',
                    ]),
                
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business')
                    ->weight('bold')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(),
                
                Tables\Columns\TextColumn::make('details')
                    ->label('Details')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->since()
                    ->sortable(),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc');
    }
    
    protected function getRecentActivityQuery(): Builder
    {
        $userId = Auth::id();
        
        // Combine recent reviews, saved businesses, and leads into one query
        return DB::table(
            DB::raw("(
                SELECT 
                    'Review' as type,
                    b.business_name,
                    CONCAT(r.rating, ' stars - ', LEFT(r.comment, 50)) as details,
                    b.slug,
                    bt.slug as business_type_slug,
                    r.created_at,
                    CONCAT('/', bt.slug, '/', b.slug) as url
                FROM reviews r
                JOIN businesses b ON r.reviewable_id = b.id AND r.reviewable_type = 'App\\\\Models\\\\Business'
                LEFT JOIN business_types bt ON b.business_type_id = bt.id
                WHERE r.user_id = {$userId}
                
                UNION ALL
                
                SELECT 
                    'Saved Business' as type,
                    b.business_name,
                    CONCAT('Saved to favorites') as details,
                    b.slug,
                    bt.slug as business_type_slug,
                    sb.created_at,
                    CONCAT('/', bt.slug, '/', b.slug) as url
                FROM saved_businesses sb
                JOIN businesses b ON sb.business_id = b.id
                LEFT JOIN business_types bt ON b.business_type_id = bt.id
                WHERE sb.user_id = {$userId}
                
                UNION ALL
                
                SELECT 
                    'Inquiry' as type,
                    b.business_name,
                    CONCAT('Inquiry: ', l.lead_button_text) as details,
                    b.slug,
                    bt.slug as business_type_slug,
                    l.created_at,
                    CONCAT('/', bt.slug, '/', b.slug) as url
                FROM leads l
                JOIN businesses b ON l.business_id = b.id
                LEFT JOIN business_types bt ON b.business_type_id = bt.id
                WHERE l.user_id = {$userId}
                
                ORDER BY created_at DESC
                LIMIT 20
            ) as activities")
        );
    }
}
