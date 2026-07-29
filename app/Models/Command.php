<?php

namespace App\Models;

use App\Enums\CommandStatus;
use App\Domain\Audit\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Command extends TenantScopedModel
{
    use Auditable;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'created_by',
        'type',
        'payload',
        'status',
        'idempotency_key',
        'result',
        'error_message',
        'expires_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'status' => CommandStatus::class,
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === CommandStatus::Pending;
    }
}
