<?php

namespace App\Models\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrgScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $orgId = app(CurrentOrg::class)->constraint();

        if ($orgId !== null) {
            $builder->where($model->qualifyColumn('org_id'), $orgId);
        }
    }
}
