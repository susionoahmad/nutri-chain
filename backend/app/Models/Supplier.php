<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory, \App\Traits\HasSubscriptionLimits;

    protected $fillable = ['name', 'code', 'contact_person', 'email', 'phone', 'address', 'is_active', 'valid_until', 'is_onboarded'];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
