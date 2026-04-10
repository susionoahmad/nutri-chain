<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSupplierScope;

class Producer extends Model
{
    use HasFactory, HasSupplierScope;

    protected $fillable = ['supplier_id', 'name', 'address', 'phone'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
