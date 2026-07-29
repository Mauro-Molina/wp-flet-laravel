<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SiteCredential extends TenantScopedModel
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'secret_encrypted',
        'secret_hash',
        'version',
        'is_active',
        'rotated_at',
        'revoked_at',
    ];

    protected $hidden = [
        'secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rotated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isValid(): bool
    {
        return $this->is_active && $this->revoked_at === null;
    }

    public function plainSecret(): string
    {
        return Crypt::decryptString($this->secret_encrypted);
    }
}
