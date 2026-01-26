<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\PremiumExpiringNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendExpiringNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:send-expiring';

    /**
     * The console command description.
     */
    protected $description = 'Send notifications for expiring premium subscriptions and campaigns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expiring subscriptions...');
        
        $this->sendPremiumExpiringNotifications();
        
        // TODO: Add campaign expiring notifications when Campaign model exists
        // $this->sendCampaignExpiringNotifications();
        
        $this->info('Done!');
        return Command::SUCCESS;
    }

    /**
     * Send notifications for expiring premium subscriptions
     */
    protected function sendPremiumExpiringNotifications(): void
    {
        $now = Carbon::now();
        
        // Define notification intervals (days before expiration)
        $intervals = [7, 3, 1];
        
        foreach ($intervals as $days) {
            $targetDate = $now->copy()->addDays($days)->startOfDay();
            
            // Find subscriptions expiring in X days
            $subscriptions = Subscription::where('status', 'active')
                ->whereDate('ends_at', $targetDate)
                ->whereHas('business.user') // Make sure business and owner exist
                ->with(['business.user'])
                ->get();
            
            $count = 0;
            foreach ($subscriptions as $subscription) {
                // Check if notification already sent today for this subscription
                $alreadySent = $subscription->business->user
                    ->notifications()
                    ->where('type', PremiumExpiringNotification::class)
                    ->whereDate('created_at', $now->toDateString())
                    ->where('data->subscription_id', $subscription->id)
                    ->exists();
                
                if (!$alreadySent) {
                    try {
                        $subscription->business->user->notify(
                            new PremiumExpiringNotification($subscription, $days)
                        );
                        $count++;
                        
                        Log::info('Premium expiring notification sent', [
                            'subscription_id' => $subscription->id,
                            'business_id' => $subscription->business_id,
                            'days_left' => $days,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send premium expiring notification', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            
            if ($count > 0) {
                $this->info("Sent {$count} notifications for subscriptions expiring in {$days} days");
            }
        }
    }

    /**
     * Send notifications for expiring campaigns (when implemented)
     */
    protected function sendCampaignExpiringNotifications(): void
    {
        // TODO: Implement when Campaign model is created
        // Similar logic to premium expiring notifications
        
        $this->comment('Campaign expiring notifications not yet implemented');
    }
}
