<?php

namespace App\Listeners;

use App\Models\Notification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

class SyncLaravelNotificationToCustomTable
{
    /**
     * Handle the event.
     * When a Laravel notification is sent via database channel,
     * also create a record in the custom notifications table.
     */
    public function handle(NotificationSent $event): void
    {
        // Only process database notifications
        if (!in_array('database', $event->notification->via($event->notifiable))) {
            return;
        }

        try {
            $data = $event->notification->toArray($event->notifiable);
            $type = $data['type'] ?? 'system';
            
            // Generate title from message or type if not provided
            $title = $data['title'] ?? null;
            if (!$title) {
                // Try to extract from message or create from type
                $message = $data['message'] ?? '';
                $title = !empty($message) 
                    ? \Illuminate\Support\Str::limit($message, 60)
                    : ucwords(str_replace('_', ' ', $type));
            }
            
            $message = $data['message'] ?? $title ?? '';
            $actionUrl = $data['url'] ?? $data['action_url'] ?? null;

            // Create custom notification record
            Notification::create([
                'user_id' => $event->notifiable->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'notifiable_type' => get_class($event->notifiable),
                'notifiable_id' => $event->notifiable->id,
                'data' => array_merge([
                    'format' => 'filament',
                    'title' => $title,
                    'message' => $message,
                    'action_url' => $actionUrl,
                    'type' => $type,
                ], $data),
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync Laravel notification to custom table', [
                'error' => $e->getMessage(),
                'notification_type' => get_class($event->notification),
            ]);
        }
    }
}
