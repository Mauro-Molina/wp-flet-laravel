<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteUserAccess extends TenantScopedModel
{
    protected $table = 'site_user_access';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'user_id',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
