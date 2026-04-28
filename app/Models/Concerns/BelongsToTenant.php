<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait per modelli multi-tenant.
 * - Auto-filtra ogni query per tenant_id corrente
 * - Auto-popola tenant_id su create
 *
 * Per bypassare (es. cron globali, admin), usare:
 *   Model::withoutGlobalScope('tenant')->...
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = tenant_id();
            $table = $builder->getModel()->getTable();
            $builder->where("{$table}.tenant_id", $tenantId);
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = tenant_id();
            }
        });
    }
}
