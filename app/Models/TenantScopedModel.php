<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for all tenant-scoped domain models. Prefer extending this over
 * applying BelongsToTenant manually so reviews can enforce inheritance.
 */
abstract class TenantScopedModel extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';
}
