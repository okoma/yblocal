<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired subscriptions and remove premium status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired subscriptions...');

        // Find all active subscriptions that have expired
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('No expired subscriptions found.');
            return 0;
        }

        $count = 0;

        foreach ($expiredSubscriptions as $subscription) {
            // Mark subscription as expired
            $subscription->update(['status' => 'expired']);

            // Remove premium status from business
            if ($subscription->business) {
                $subscription->business->update([
                    'is_premium' => false,
                    'premium_until' => null,
                ]);

                $this->info("Removed premium from business #{$subscription->business->id} ({$subscription->business->business_name})");
                
                Log::info('Premium removed due to subscription expiry', [
                    'subscription_id' => $subscription->id,
                    'business_id' => $subscription->business->id,
                    'expired_at' => $subscription->ends_at,
                ]);

                $count++;
            }
        }

        $this->info("Processed {$count} expired subscriptions.");
        return 0;
    }
}
