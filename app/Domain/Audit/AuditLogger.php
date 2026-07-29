<?php

namespace App\Domain\Audit;

use App\Domain\Tenancy\TenantContext;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $actorType = 'user',
        ?string $actorId = null,
    ): AuditLog {
        $request = request();

        return AuditLog::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => TenantContext::id(),
            'actor_id' => $actorId ?? auth()->id(),
            'actor_type' => $actorType,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? Str::limit((string) $request->userAgent(), 255, '') : null,
            'request_id' => $request instanceof Request ? $request->headers->get('X-Request-Id') : null,
            'created_at' => now(),
        ]);
    }

    public function logModel(Model $model, string $event): void
    {
        $exclude = method_exists($model, 'auditExclude')
            ? $model->auditExclude()
            : ['password', 'remember_token', 'two_factor_secret'];

        $old = null;
        $new = null;

        if ($event === 'created') {
            $new = $this->filterAttributes($model->getAttributes(), $exclude);
        } elseif ($event === 'updated') {
            $old = $this->filterAttributes($model->getOriginal(), $exclude);
            $new = $this->filterAttributes($model->getChanges(), $exclude);
            if ($new === []) {
                return;
            }
        } elseif ($event === 'deleted') {
            $old = $this->filterAttributes($model->getAttributes(), $exclude);
        }

        $this->log(
            action: class_basename($model).'.'.$event,
            auditable: $model,
            oldValues: $old,
            newValues: $new,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $exclude
     * @return array<string, mixed>
     */
    private function filterAttributes(array $attributes, array $exclude): array
    {
        return collect($attributes)
            ->reject(fn ($_, $key) => in_array($key, $exclude, true))
            ->all();
    }
}
