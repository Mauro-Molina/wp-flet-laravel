<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitePendingUpdate extends TenantScopedModel
{
    protected $table = 'site_pending_updates';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'update_type',
        'item_slug',
        'item_name',
        'current_version',
        'available_version',
        'metadata',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
