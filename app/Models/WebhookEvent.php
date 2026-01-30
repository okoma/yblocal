<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'reference',
        'transaction_id',
        'payload',
        'status',
        'error_message',
        'retry_count',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the transaction associated with this webhook event.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Mark event as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark event as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Check if event was already processed (idempotency check).
     */
    public static function isProcessed(string $gateway, string $eventId): bool
    {
        return self::where('gateway', $gateway)
            ->where('event_id', $eventId)
            ->where('status', 'processed')
            ->exists();
    }

    /**
     * Create or get webhook event (for idempotency).
     */
    public static function createOrGet(string $gateway, string $eventId, array $data): self
    {
        return self::firstOrCreate(
            [
                'gateway' => $gateway,
                'event_id' => $eventId,
            ],
            [
                'event_type' => $data['event_type'] ?? 'unknown',
                'reference' => $data['reference'] ?? null,
                'payload' => $data['payload'] ?? [],
                'status' => 'pending',
            ]
        );
    }
}
