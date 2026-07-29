<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UptimeCheck extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'is_up',
        'response_time_ms',
        'http_status',
        'performance',
        'checked_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_up' => 'boolean',
            'performance' => 'array',
            'checked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
