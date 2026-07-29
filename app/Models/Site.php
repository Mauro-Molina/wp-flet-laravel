<?php

namespace App\Models;

use App\Domain\Audit\Concerns\Auditable;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends TenantScopedModel
{
    /** @use HasFactory<\Database\Factories\SiteFactory> */
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'status',
        'last_seen_at',
        'connected_at',
        'disconnected_at',
        'plugins_snapshot',
        'themes_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'plugins_snapshot' => 'array',
            'themes_snapshot' => 'array',
        ];
    }

    public function usersWithAccess(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_user_access')
            ->using(SiteUserAccess::class)
            ->withPivot(['id', 'tenant_id'])
            ->withTimestamps();
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(SiteUserAccess::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(SiteCredential::class);
    }

    public function activeCredential(): HasOne
    {
        return $this->hasOne(SiteCredential::class)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->latest('version');
    }

    public function license(): HasOne
    {
        return $this->hasOne(SiteLicense::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function pendingUpdates(): HasMany
    {
        return $this->hasMany(SitePendingUpdate::class);
    }

    public function eventLogs(): HasMany
    {
        return $this->hasMany(EventLog::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * Resolve a site by ID without tenant context (plugin HMAC middleware).
     */
    public static function findForPlugin(string $siteId): ?self
    {
        return TenantContext::bypass(fn () => self::query()->find($siteId));
    }
}
