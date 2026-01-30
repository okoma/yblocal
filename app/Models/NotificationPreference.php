<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'topic',
        'channels',
        'frequency',
        'enabled',
    ];

    protected $casts = [
        'channels' => 'array',
        'enabled' => 'boolean',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }

    public static function for($notifiable, string $topic)
    {
        return static::where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->id)
            ->where('topic', $topic)
            ->first();
    }
}
