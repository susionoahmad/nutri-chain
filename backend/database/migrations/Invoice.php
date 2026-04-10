<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'total',
        'total_cost',
        'total_profit',
        'status',
        'due_date',
        'payment_proof',
        'payment_method',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'due_date' => 'date',
    ];

    /**
     * Scope untuk mempermudah pengecekan status pembayaran.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Relasi ke Order (Pesanan).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}