<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\HasSupplierScope;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory, HasSupplierScope;
    
    protected $fillable = ['supplier_id', 'order_id', 'total', 'total_cost', 'total_profit', 'status', 'due_date', 'payment_proof', 'payment_method'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }
}
