<?php

namespace App\Models;

use App\Domain\Audit\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends TenantScopedModel
{
    use Auditable;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'command_id',
        'type',
        'status',
        'label',
        'size_bytes',
        'storage_path',
        'metadata',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }
}
