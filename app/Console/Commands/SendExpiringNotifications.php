<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\AdCampaign;
use App\Notifications\PremiumExpiringNotification;
use App\Notifications\CampaignEndingNotification;
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
        $this->info('Checking for expiring subscriptions and campaigns...');
        
        $this->sendPremiumExpiringNotifications();
        $this->sendCampaignExpiringNotifications();
        
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
     * Send notifications for expiring campaigns
     */
    protected function sendCampaignExpiringNotifications(): void
    {
        $now = Carbon::now();
        
        // Define notification intervals (days before expiration)
        $intervals = [7, 3, 1];
        
        foreach ($intervals as $days) {
            $targetDate = $now->copy()->addDays($days)->startOfDay();
            
            // Find active campaigns expiring in X days
            $campaigns = AdCampaign::where('is_active', true)
                ->whereDate('ends_at', $targetDate)
                ->whereHas('business.user') // Make sure business and owner exist
                ->with(['business.user'])
                ->get();
            
            $count = 0;
            foreach ($campaigns as $campaign) {
                // Check if notification already sent today for this campaign
                $alreadySent = $campaign->business->user
                    ->notifications()
                    ->where('type', CampaignEndingNotification::class)
                    ->whereDate('created_at', $now->toDateString())
                    ->where('data->campaign_id', $campaign->id)
                    ->exists();
                
                if (!$alreadySent) {
                    try {
                        $campaign->business->user->notify(
                            new CampaignEndingNotification($campaign, $days)
                        );
                        $count++;
                        
                        Log::info('Campaign ending notification sent', [
                            'campaign_id' => $campaign->id,
                            'business_id' => $campaign->business_id,
                            'days_left' => $days,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send campaign ending notification', [
                            'campaign_id' => $campaign->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            
            if ($count > 0) {
                $this->info("Sent {$count} notifications for campaigns ending in {$days} days");
            }
        }
    }
}
