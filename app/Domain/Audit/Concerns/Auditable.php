<?php

namespace App\Domain\Audit\Concerns;

use App\Domain\Audit\AuditLogger;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model): void {
            app(AuditLogger::class)->logModel($model, 'created');
        });

        static::updated(function ($model): void {
            app(AuditLogger::class)->logModel($model, 'updated');
        });

        static::deleted(function ($model): void {
            app(AuditLogger::class)->logModel($model, 'deleted');
        });
    }

    /**
     * Attributes that should not appear in audit payloads.
     *
     * @return list<string>
     */
    public function auditExclude(): array
    {
        return property_exists($this, 'auditExclude')
            ? $this->auditExclude
            : ['password', 'remember_token', 'two_factor_secret'];
    }
}
