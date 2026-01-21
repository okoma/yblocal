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
        
        // Drop CHECK constraints for MySQL
        if (DB::getDriverName() === 'mysql') {
            $constraints = [
                'business_views' => 'check_business_views_parent',
                'business_interactions' => 'check_business_interactions_parent',
                'saved_businesses' => 'check_saved_businesses_parent',
                'business_view_summaries' => 'check_business_view_summaries_parent',
            ];
            
            foreach ($constraints as $table => $constraint) {
                try {
                    DB::statement("ALTER TABLE `{$table}` DROP CHECK {$constraint}");
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
            }
        }

        // List of tables to process
        $tables = [
            'products',
            'leads',
            'business_views',
            'business_interactions',
            'saved_businesses',
            'reviews',
            'business_reports',
            'business_view_summaries',
            'officials',
            'social_accounts',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'business_branch_id')) {
                // Drop foreign keys first
                $dropForeignKeyByColumn($table, 'business_branch_id');
                
                // Drop indexes
                $dropIndexesByColumn($table, 'business_branch_id');
                
                // Finally drop the column
                Schema::table($table, function (Blueprint $tableSchema) {
                    $tableSchema->dropColumn('business_branch_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration removes branch functionality permanently
        // To reverse, you would need to recreate the entire branch system
    }
};
