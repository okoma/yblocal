<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use App\Services\NotificationPreferenceService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public $notifiable,
        public Notification $notification
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('SendNotificationJob: Sending notification', [
                'notifiable_type' => get_class($this->notifiable),
                'notifiable_id' => $this->notifiable->id ?? null,
                'notification_type' => get_class($this->notification),
            ]);

            // Determine channels and filter by central preference/suppression checks
            $originalChannels = [];
            try {
                $originalChannels = $this->notification->via($this->notifiable);
            } catch (\Throwable $e) {
                // If via() errors, default to letting notify() handle it
                $originalChannels = [];
            }

            if (!empty($originalChannels)) {
                $allowed = [];
                foreach ($originalChannels as $ch) {
                    if (NotificationPreferenceService::shouldSend($this->notifiable, $this->notification, $ch)) {
                        $allowed[] = $ch;
                    }
                }

                if (empty($allowed)) {
                    Log::info('SendNotificationJob: No allowed channels after preference/suppression checks, skipping.');
                    return;
                }

                // Create a proxy notification that forces the allowed channels
                $proxy = new class($this->notification, $allowed) extends Notification {
                    public $orig;
                    public $allowedChannels;
                    public function __construct($orig, $allowed)
                    {
                        $this->orig = $orig;
                        $this->allowedChannels = $allowed;
                    }
                    public function via($notifiable)
                    {
                        return $this->allowedChannels;
                    }
                    public function __call($name, $args)
                    {
                        // Forward to original notification if method exists
                        if (method_exists($this->orig, $name)) {
                            return $this->orig->{$name}(...$args);
                        }
                        throw new \BadMethodCallException($name);
                    }
                };

                $this->notifiable->notify($proxy);
            } else {
                // No explicit channels from via(); call notify() which will resolve channels itself
                $this->notifiable->notify($this->notification);
            }

            Log::info('SendNotificationJob: Notification sent successfully');
        } catch (\Exception $e) {
            Log::error('SendNotificationJob: Failed to send notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationJob: Job failed permanently', [
            'notifiable_type' => get_class($this->notifiable),
            'notification_type' => get_class($this->notification),
            'error' => $exception->getMessage(),
        ]);
    }
}
