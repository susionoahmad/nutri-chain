<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSupplierScope;
use App\Models\Order;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, HasSupplierScope;

    protected $fillable = ['supplier_id', 'name', 'address', 'phone'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
