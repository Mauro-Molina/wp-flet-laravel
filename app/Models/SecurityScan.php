<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityScan extends TenantScopedModel
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'scan_type',
        'status',
        'score',
        'findings',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'scanned_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
