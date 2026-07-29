<?php

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\Exceptions\TenantContextMissingException;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Fail-closed global scope: without an active tenant (and without explicit bypass),
 * queries throw instead of leaking cross-tenant rows.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::isBypassed()) {
            return;
        }

        $tenantId = TenantContext::id();

        if ($tenantId === null) {
            throw new TenantContextMissingException(
                sprintf('Missing tenant context while querying [%s].', $model->getTable())
            );
        }

        $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
    }
}
