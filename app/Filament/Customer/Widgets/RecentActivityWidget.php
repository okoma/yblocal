<?php

namespace App\Filament\Customer\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Review;

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
            ->defaultPaginationPageOption(5);
    }
    
    protected function getRecentActivityQuery(): Builder
    {
        $userId = Auth::id();
        
        // Build the union query
        $unionQuery = DB::table('reviews as r')
            ->select([
                DB::raw("'Review' as type"),
                'b.business_name',
                DB::raw("CONCAT(r.rating, ' stars - ', LEFT(r.comment, 50)) as details"),
                'b.slug',
                'bt.slug as business_type_slug',
                'r.created_at',
                DB::raw("CONCAT('/', bt.slug, '/', b.slug) as url"),
                DB::raw("1 as id") // Add a dummy id column
            ])
            ->join('businesses as b', function ($join) {
                $join->on('r.reviewable_id', '=', 'b.id')
                     ->where('r.reviewable_type', '=', 'App\\Models\\Business');
            })
            ->leftJoin('business_types as bt', 'b.business_type_id', '=', 'bt.id')
            ->where('r.user_id', $userId)
            ->whereNull('r.deleted_at')
            
            ->unionAll(
                DB::table('saved_businesses as sb')
                    ->select([
                        DB::raw("'Saved Business' as type"),
                        'b.business_name',
                        DB::raw("'Saved to favorites' as details"),
                        'b.slug',
                        'bt.slug as business_type_slug',
                        'sb.created_at',
                        DB::raw("CONCAT('/', bt.slug, '/', b.slug) as url"),
                        DB::raw("1 as id")
                    ])
                    ->join('businesses as b', 'sb.business_id', '=', 'b.id')
                    ->leftJoin('business_types as bt', 'b.business_type_id', '=', 'bt.id')
                    ->where('sb.user_id', $userId)
            )
            
            ->unionAll(
                DB::table('leads as l')
                    ->select([
                        DB::raw("'Inquiry' as type"),
                        'b.business_name',
                        DB::raw("CONCAT('Inquiry: ', l.lead_button_text) as details"),
                        'b.slug',
                        'bt.slug as business_type_slug',
                        'l.created_at',
                        DB::raw("CONCAT('/', bt.slug, '/', b.slug) as url"),
                        DB::raw("1 as id")
                    ])
                    ->join('businesses as b', 'l.business_id', '=', 'b.id')
                    ->leftJoin('business_types as bt', 'b.business_type_id', '=', 'bt.id')
                    ->where('l.user_id', $userId)
            )
            ->orderByRaw('created_at DESC')
            ->limit(20);
        
        // Use Review model but disable global scopes (like soft deletes)
        return Review::query()
            ->withoutGlobalScopes() // This removes soft delete constraint
            ->fromSub($unionQuery, 'activities')
            ->select('activities.*');
    }
}