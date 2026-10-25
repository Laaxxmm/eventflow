<?php

namespace App\Models\Tenancy;

use App\Models\Org;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Org-owned model: every query is filtered by the current org (OrgScope) and
 * new rows get the current org's id. Bypass deliberately with
 * Model::withoutGlobalScope(OrgScope::class).
 */
trait BelongsToOrg
{
    public static function bootBelongsToOrg(): void
    {
        static::addGlobalScope(new OrgScope);

        static::creating(function (Model $model) {
            $model->org_id ??= app(CurrentOrg::class)->id();
        });
    }

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class);
    }
}
