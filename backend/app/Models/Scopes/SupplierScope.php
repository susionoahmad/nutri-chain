<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SupplierScope implements Scope
{
    /**
     * Terapkan scope ke query builder Eloquent.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Pastikan user sudah login dan memiliki supplier_id
        if (Auth::check() && Auth::user()->supplier_id) {
            $builder->where($model->getTable() . '.supplier_id', Auth::user()->supplier_id);
        }
    }
}