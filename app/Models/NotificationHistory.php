<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationHistory extends TenantScopedModel
{
    protected $table = 'notification_history';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_type',
        'channel',
        'status',
        'payload',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
