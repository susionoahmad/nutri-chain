<?php

namespace App\Traits;

use App\Models\Scopes\SupplierScope;
use Illuminate\Support\Facades\Auth;

trait HasSupplierScope
{
    /**
     * Boot trait untuk menambahkan global scope dan event listener.
     */
    protected static function bootHasSupplierScope(): void
    {
        // Otomatis filter data berdasarkan supplier_id saat query (Read)
        static::addGlobalScope(new SupplierScope);

        // Otomatis isi supplier_id saat data dibuat (Create)
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->supplier_id) {
                // Hanya isi jika belum diset secara manual
                if (empty($model->supplier_id)) {
                    $model->supplier_id = Auth::user()->supplier_id;
                }
            }
        });
    }
}