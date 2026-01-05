<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class HospitalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply scope if we have a current hospital set and user is not super admin
        if (app()->bound('current_hospital_id')) {
            $hospitalId = app('current_hospital_id');

            if ($hospitalId) {
                $builder->where($model->getTable() . '.hospital_id', $hospitalId);
            }
        }
    }
}
