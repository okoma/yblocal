<?php

// ============================================
// Ensure every user has a unique referral_code
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')
            ->where(function ($q) {
                $q->whereNull('referral_code')->orWhere('referral_code', '');
            })
            ->get();

        foreach ($users as $user) {
            $code = $this->uniqueCode();
            DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
        }
    }

    protected function uniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (DB::table('users')->where('referral_code', $code)->exists());

        return $code;
    }

    public function down(): void
    {
        // No-op: we don't track which users were backfilled
    }
};
