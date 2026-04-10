<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use App\Traits\HasSupplierScope;

class ActivityLog extends Model
{
    use HasSupplierScope;

    protected $fillable = [
        'user_id',
        'supplier_id',
        'subject_type',
        'subject_id',
        'action',
        'description',
        'properties',
        'causer_name'
    ];

    protected $casts = [
        'properties' => 'json'
    ];

    /**
     * Get the parent subject model.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who caused the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
