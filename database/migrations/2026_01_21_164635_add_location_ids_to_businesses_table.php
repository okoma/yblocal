<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Add location foreign key columns if they don't exist
            if (!Schema::hasColumn('businesses', 'state_location_id')) {
                $table->foreignId('state_location_id')
                    ->nullable()
                    ->after('city')
                    ->constrained('locations')
                    ->nullOnDelete();
            }
            
            if (!Schema::hasColumn('businesses', 'city_location_id')) {
                $table->foreignId('city_location_id')
                    ->nullable()
                    ->after('state_location_id')
                    ->constrained('locations')
                    ->nullOnDelete();
            }
            
            // Add indexes for location-based queries
            if (!$this->indexExists('businesses', 'businesses_state_location_id_index')) {
                $table->index('state_location_id');
            }
            
            if (!$this->indexExists('businesses', 'businesses_city_location_id_index')) {
                $table->index('city_location_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Drop indexes
            if ($this->indexExists('businesses', 'businesses_city_location_id_index')) {
                $table->dropIndex(['city_location_id']);
            }
            
            if ($this->indexExists('businesses', 'businesses_state_location_id_index')) {
                $table->dropIndex(['state_location_id']);
            }
            
            // Drop foreign keys
            if ($this->foreignKeyExists('businesses', 'businesses_state_location_id_foreign')) {
                $table->dropForeign(['state_location_id']);
            }
            
            if ($this->foreignKeyExists('businesses', 'businesses_city_location_id_foreign')) {
                $table->dropForeign(['city_location_id']);
            }
            
            // Drop columns
            if (Schema::hasColumn('businesses', 'state_location_id')) {
                $table->dropColumn('state_location_id');
            }
            
            if (Schema::hasColumn('businesses', 'city_location_id')) {
                $table->dropColumn('city_location_id');
            }
        });
    }
    
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = \Illuminate\Support\Facades\DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$database, $table, $index]
        );
        
        return !empty($result) && isset($result[0]) && $result[0]->count > 0;
    }

    /**
     * Check if a foreign key exists on a table
     */
    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = \Illuminate\Support\Facades\DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.table_constraints 
             WHERE constraint_schema = ? 
             AND table_name = ? 
             AND constraint_name = ?
             AND constraint_type = 'FOREIGN KEY'",
            [$database, $table, $foreignKey]
        );
        
        return !empty($result) && isset($result[0]) && $result[0]->count > 0;
    }
};
