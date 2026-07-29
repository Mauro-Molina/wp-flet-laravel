<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteLicense extends TenantScopedModel
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'status',
        'grace_ends_at',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'grace_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function allowsCommands(): bool
    {
        $status = $this->status;

        if ($status === LicenseStatus::Grace && $this->grace_ends_at?->isPast()) {
            return false;
        }

        return $status->allowsCommands();
    }

    public function allowsContent(): bool
    {
        $status = $this->status;

        if ($status === LicenseStatus::Grace && $this->grace_ends_at?->isPast()) {
            return false;
        }

        return $status->allowsContent();
    }
}
