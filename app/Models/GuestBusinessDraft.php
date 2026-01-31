<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class GuestBusinessDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'guest_email',
        'guest_phone',
        'ip_address',
        'user_agent',
        'form_data',
        'current_step',
        'last_activity_at',
        'abandoned_email_sent_at',
        'reminder_count',
        'is_converted',
        'business_id',
        'user_id',
        'converted_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'current_step' => 'integer',
        'last_activity_at' => 'datetime',
        'abandoned_email_sent_at' => 'datetime',
        'reminder_count' => 'integer',
        'is_converted' => 'boolean',
        'converted_at' => 'datetime',
    ];

    /**
     * Related business (if converted)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Related user (if converted)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get abandoned drafts (not converted, inactive for X hours)
     */
    public function scopeAbandoned($query, int $hoursInactive = 24)
    {
        return $query->where('is_converted', false)
            ->where('last_activity_at', '<=', now()->subHours($hoursInactive))
            ->whereNotNull('guest_email');
    }

    /**
     * Scope: Get drafts that haven't received reminder emails yet
     */
    public function scopeNeedingReminder($query, int $hoursInactive = 24)
    {
        return $query->abandoned($hoursInactive)
            ->whereNull('abandoned_email_sent_at');
    }

    /**
     * Scope: Get drafts needing second reminder (48 hours)
     */
    public function scopeNeedingSecondReminder($query)
    {
        return $query->where('is_converted', false)
            ->where('last_activity_at', '<=', now()->subHours(48))
            ->whereNotNull('abandoned_email_sent_at')
            ->where('reminder_count', '<', 2)
            ->whereNotNull('guest_email');
    }

    /**
     * Check if draft is abandoned
     */
    public function isAbandoned(int $hoursInactive = 24): bool
    {
        return !$this->is_converted 
            && $this->last_activity_at 
            && $this->last_activity_at->diffInHours(now()) >= $hoursInactive;
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent(): void
    {
        $this->update([
            'abandoned_email_sent_at' => now(),
            'reminder_count' => $this->reminder_count + 1,
        ]);
    }

    /**
     * Get the resume URL for this draft
     */
    public function getResumeUrl(): string
    {
        return route('guest.business.create') . '?resume=' . $this->id;
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): int
    {
        $totalSteps = 4;
        return (int) (($this->current_step / $totalSteps) * 100);
    }

    /**
     * Get business name from form data
     */
    public function getBusinessName(): ?string
    {
        return $this->form_data['business_name'] ?? null;
    }

    /**
     * Get time since last activity (human readable)
     */
    public function getTimeSinceLastActivity(): string
    {
        if (!$this->last_activity_at) {
            return 'Unknown';
        }
        
        return $this->last_activity_at->diffForHumans();
    }
}
