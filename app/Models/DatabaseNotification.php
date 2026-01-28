<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as LaravelDatabaseNotification;

class DatabaseNotification extends LaravelDatabaseNotification
{
    /**
     * The table associated with the model.
     */
    protected $table = 'notifications';

    /**
     * Get the notification type from data.
     * This maps Laravel's notification class to your custom type enum.
     */
    public function getTypeAttribute(): string
    {
        // First check if type is already set (for custom notifications)
        if (isset($this->attributes['type'])) {
            return $this->attributes['type'];
        }

        // For Laravel notifications, extract type from data
        $data = $this->data ?? [];
        return $data['type'] ?? 'system';
    }

    /**
     * Get the title from data or use notification class name.
     */
    public function getTitleAttribute(): string
    {
        if (isset($this->attributes['title'])) {
            return $this->attributes['title'];
        }

        $data = $this->data ?? [];
        return $data['title'] ?? $this->getTypeAttribute();
    }

    /**
     * Get the message from data.
     */
    public function getMessageAttribute(): string
    {
        if (isset($this->attributes['message'])) {
            return $this->attributes['message'];
        }

        $data = $this->data ?? [];
        return $data['message'] ?? '';
    }

    /**
     * Get the action URL from data.
     */
    public function getActionUrlAttribute(): ?string
    {
        if (isset($this->attributes['action_url'])) {
            return $this->attributes['action_url'];
        }

        $data = $this->data ?? [];
        return $data['url'] ?? $data['action_url'] ?? null;
    }

    /**
     * Check if notification is read.
     */
    public function getIsReadAttribute(): bool
    {
        if (isset($this->attributes['is_read'])) {
            return (bool) $this->attributes['is_read'];
        }

        return !is_null($this->read_at);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Check if notification is unread.
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Get icon based on type.
     */
    public function getIcon(): string
    {
        return match($this->type) {
            'claim_submitted' => 'heroicon-o-document-text',
            'claim_approved' => 'heroicon-o-check-circle',
            'claim_rejected' => 'heroicon-o-x-circle',
            'verification_submitted' => 'heroicon-o-shield-check',
            'verification_approved' => 'heroicon-o-badge-check',
            'verification_rejected' => 'heroicon-o-shield-exclamation',
            'verification_resubmission_required' => 'heroicon-o-arrow-path',
            'new_review' => 'heroicon-o-star',
            'review_reply' => 'heroicon-o-chat-bubble-left',
            'new_lead' => 'heroicon-o-user-plus',
            'business_reported' => 'heroicon-o-flag',
            'premium_expiring' => 'heroicon-o-clock',
            'campaign_ending' => 'heroicon-o-megaphone',
            'new_quote_request' => 'heroicon-o-document-text',
            'new_quote_response' => 'heroicon-o-document-check',
            'quote_shortlisted' => 'heroicon-o-star',
            'quote_accepted' => 'heroicon-o-check-circle',
            'quote_rejected' => 'heroicon-o-x-circle',
            'system' => 'heroicon-o-bell',
            default => 'heroicon-o-bell',
        };
    }

    /**
     * Get color based on type.
     */
    public function getColor(): string
    {
        return match($this->type) {
            'claim_approved', 'verification_approved', 'quote_accepted' => 'success',
            'claim_rejected', 'verification_rejected', 'quote_rejected' => 'danger',
            'verification_resubmission_required' => 'warning',
            'new_review', 'review_reply', 'new_lead', 'new_quote_request', 'new_quote_response', 'quote_shortlisted' => 'info',
            'business_reported' => 'danger',
            'premium_expiring', 'campaign_ending' => 'warning',
            default => 'gray',
        };
    }
}
