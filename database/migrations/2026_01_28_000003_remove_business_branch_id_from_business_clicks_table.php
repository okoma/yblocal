<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper function to get and drop foreign key by column name
        $dropForeignKeyByColumn = function ($table, $column) {
            $connection = DB::connection();
            $database = $connection->getDatabaseName();
            
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = ? 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ? 
                    AND CONSTRAINT_NAME != 'PRIMARY'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$database, $table, $column]);
                
                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        // Continue if fails
                    }
                }
            } catch (\Exception $e) {
                // Continue if query fails
            }
        };
        
        // Helper function to get and drop indexes by column name
        $dropIndexesByColumn = function ($table, $column) {
            $connection = DB::connection();
            $database = $connection->getDatabaseName();
            
            try {
                $indexes = DB::select("
                    SELECT DISTINCT INDEX_NAME 
                    FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = ? 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ?
                    AND INDEX_NAME != 'PRIMARY'
                ", [$database, $table, $column]);
                
                foreach ($indexes as $idx) {
                    try {
                        DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$idx->INDEX_NAME}`");
                    } catch (\Exception $e) {
                        // Continue if fails
                    }
                }
            } catch (\Exception $e) {
                // Continue if query fails
            }
        };

        $table = 'business_clicks';
        $column = 'business_branch_id';

        if (Schema::hasColumn($table, $column)) {
            // Drop foreign keys first
            $dropForeignKeyByColumn($table, $column);
            
            // Drop indexes
            $dropIndexesByColumn($table, $column);
            
            // Finally drop the column
            Schema::table($table, function (Blueprint $tableSchema) use ($column) {
                $tableSchema->dropColumn($column);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the column if needed (for rollback)
        if (!Schema::hasColumn('business_clicks', 'business_branch_id')) {
            Schema::table('business_clicks', function (Blueprint $table) {
                $table->foreignId('business_branch_id')
                    ->nullable()
                    ->after('business_id')
                    ->constrained('business_branches')
                    ->onDelete('cascade');
            });
        }
    }
};
