<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Send welcome notification to new users.
     */
    public function created(User $user): void
    {
        // Determine user type for personalized welcome
        $userType = $user->isBusinessOwner() || $user->isAdmin() ? 'business_owner' : 'customer';
        
        try {
            // Send welcome notification
            $user->notify(new WelcomeNotification($userType));
            
            Log::info('Welcome notification sent to new user', [
                'user_id' => $user->id,
                'user_type' => $userType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
