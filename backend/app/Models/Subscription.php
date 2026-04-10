<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['supplier_id', 'plan_id', 'start_date', 'end_date', 'status', 'payment_proof'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function getPaymentProofUrlAttribute()
    {
        if (!$this->payment_proof) return null;
        
        // If it's already a full URL, return it
        if (filter_var($this->payment_proof, FILTER_VALIDATE_URL)) {
            return $this->payment_proof;
        }

        // If it starts with /storage, and we want to ensure it has the correct APP_URL
        if (str_starts_with($this->payment_proof, '/storage')) {
            return config('app.url') . $this->payment_proof;
        }

        return asset('storage/' . $this->payment_proof);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    protected $appends = ['payment_proof_url'];
}
