<?php

namespace App\Models; // Change namespace to match the current file path

use Illuminate\Database\Eloquent\Builder;

/**
 * World-class multi-tenancy trait to ensure data isolation.
 * Apply this to Asset, AssetRequest, AssetAssignment, etc.
 */
trait Multitenantable
{
    protected static function bootMultitenantable(): void
    {
        // Ensure this only runs in a web context with a logged-in user
        if (app()->runningInConsole()) return;

        if (auth()->check()) {
            // Automatically set organization_id when creating records
            static::creating(function ($model) {
                if (empty($model->organization_id)) {
                    $model->organization_id = auth()->user()->organization_id;
                }
            });

            // Automatically filter all queries by the user's organization
            static::addGlobalScope('organization_id', function (Builder $builder) {
                $builder->where('organization_id', auth()->user()->organization_id);
            });
        }
    }
}