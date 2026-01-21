<?php

// ============================================
// Update manager_invitations table to work with standalone businesses
// Changes business_branch_id to business_id
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if business_branch_id column exists
        if (Schema::hasColumn('manager_invitations', 'business_branch_id')) {
            // Drop foreign key constraint if it exists
            $connection = DB::connection();
            $database = $connection->getDatabaseName();
            
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = ? 
                    AND TABLE_NAME = 'manager_invitations'
                    AND COLUMN_NAME = 'business_branch_id'
                    AND CONSTRAINT_NAME != 'PRIMARY'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$database]);
                
                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE `manager_invitations` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        // Continue if fails
                    }
                }
            } catch (\Exception $e) {
                // Continue if query fails
            }
            
            // Rename column
            Schema::table('manager_invitations', function (Blueprint $table) {
                $table->renameColumn('business_branch_id', 'business_id');
            });
        } else {
            // Column doesn't exist, add business_id if it doesn't exist
            if (!Schema::hasColumn('manager_invitations', 'business_id')) {
                Schema::table('manager_invitations', function (Blueprint $table) {
                    $table->foreignId('business_id')->after('id')->constrained('businesses')->onDelete('cascade');
                });
            }
        }
        
        // Update position default
        Schema::table('manager_invitations', function (Blueprint $table) {
            $table->string('position')->default('Business Manager')->change();
        });
        
        // Add foreign key constraint if it doesn't exist
        if (Schema::hasColumn('manager_invitations', 'business_id')) {
            try {
                DB::statement("
                    ALTER TABLE manager_invitations 
                    ADD CONSTRAINT manager_invitations_business_id_foreign 
                    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
                ");
            } catch (\Exception $e) {
                // Constraint might already exist
            }
        }
    }

    public function down(): void
    {
        // Reverse: rename back to business_branch_id if needed
        if (Schema::hasColumn('manager_invitations', 'business_id')) {
            Schema::table('manager_invitations', function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->renameColumn('business_id', 'business_branch_id');
            });
        }
    }
};
