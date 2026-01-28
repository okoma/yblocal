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
        // Modify the enum to include quote notification types
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'claim_submitted',
            'claim_approved',
            'claim_rejected',
            'verification_submitted',
            'verification_approved',
            'verification_rejected',
            'verification_resubmission_required',
            'new_review',
            'review_reply',
            'new_lead',
            'business_reported',
            'premium_expiring',
            'campaign_ending',
            'new_quote_request',
            'new_quote_response',
            'quote_shortlisted',
            'quote_accepted',
            'quote_rejected',
            'system'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (without quote types)
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'claim_submitted',
            'claim_approved',
            'claim_rejected',
            'verification_submitted',
            'verification_approved',
            'verification_rejected',
            'verification_resubmission_required',
            'new_review',
            'review_reply',
            'new_lead',
            'business_reported',
            'premium_expiring',
            'campaign_ending',
            'system'
        )");
    }
};
