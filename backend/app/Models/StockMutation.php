<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasSupplierScope;

class StockMutation extends Model
{
    use HasFactory, HasSupplierScope;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'type',
        'qty',
        'old_qty',
        'new_qty',
        'reference_type',
        'reference_id',
        'note',
        'transaction_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
