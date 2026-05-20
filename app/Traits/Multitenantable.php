<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * World-class multi-tenancy trait to ensure data isolation.
 * Automatically scopes all queries by organization_id.
 */
trait Multitenantable
{
    protected static function bootMultitenantable(): void
    {
        // Ensure this only runs when there is an authenticated user and it's not a console command (except during testing/seeding if needed)
        if (app()->runningInConsole() && !app()->runningUnitTests()) return;

        if (auth()->check()) {
            $user = auth()->user();
            
            // If the user belongs to an organization/company, apply multi-tenancy
            if ($user->organization_id) {
                // Automatically set organization_id when creating records
                static::creating(function ($model) use ($user) {
                    if (empty($model->organization_id)) {
                        $model->organization_id = $user->organization_id;
                    }
                });

                // Automatically filter all queries by the user's organization
                static::addGlobalScope('organization_id', function (Builder $builder) use ($user) {
                    $builder->where('organization_id', $user->organization_id);
                });
            }
        }
    }
}
