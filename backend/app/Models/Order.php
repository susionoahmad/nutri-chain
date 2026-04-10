<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Traits\HasSupplierScope;

class Order extends Model
{
    use HasFactory, HasSupplierScope;

    protected $fillable = [
        'supplier_id',
        'customer_id',
        'order_number',
        'status',
        'order_date',
        'delivery_date',
        'total_amount',
        'notes',
    ];

    /**
     * Relasi ke item-item pesanan (One-to-Many).
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relasi ke invoice (One-to-One).
     * Digunakan saat status berubah menjadi 'delivered'.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Relasi ke customer yang memesan.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi ke supplier (Tenant context).
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relasi ke data pengiriman.
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    /**
     * Jejak audit aktivitas untuk pesanan ini.
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }
}