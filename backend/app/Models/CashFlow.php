<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSupplierScope;

class CashFlow extends Model
{
    use HasFactory, HasSupplierScope;

    protected $fillable = [
        'supplier_id', 'type', 'category', 'amount', 
        'account_type', 'reference_type', 'reference_id', 
        'note', 'transaction_date'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
