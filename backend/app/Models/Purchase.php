<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSupplierScope;

class Purchase extends Model
{
    use HasFactory, HasSupplierScope;

    protected $fillable = [
        'supplier_id', 'producer_id', 'purchase_number', 'total_amount', 
        'status', 'payment_status', 'payment_method', 'payment_proof', 'purchase_date'
    ];

    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
