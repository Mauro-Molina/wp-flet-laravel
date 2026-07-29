<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends TenantScopedModel
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'type',
        'status',
        'title',
        'description',
        'started_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
