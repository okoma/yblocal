<?php

// ============================================
// Business referral code (for business → business referral links)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('user_id');
        });

        // Backfill: use owner's user.referral_code or generate unique code
        $businesses = DB::table('businesses')->whereNull('referral_code')->get();

        foreach ($businesses as $business) {
            $code = $this->uniqueBusinessCode();
            DB::table('businesses')->where('id', $business->id)->update(['referral_code' => $code]);
        }
    }

    protected function uniqueBusinessCode(): string
    {
        do {
            $code = 'B' . strtoupper(Str::random(9));
        } while (DB::table('businesses')->where('referral_code', $code)->exists());

        return $code;
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }
};
