<?php

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    protected $fillable = [
        'account_id',
        'name',
        'relationship',
        'priority',
        'percentage',
        'email',
        'phone',
        'notes',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    /**
     * Get the account that owns the beneficiary.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope to get primary beneficiaries.
     */
    public function scopePrimary($query)
    {
        return $query->where('priority', 'primary');
    }

    /**
     * Scope to get secondary beneficiaries.
     */
    public function scopeSecondary($query)
    {
        return $query->where('priority', 'secondary');
    }

    /**
     * Scope to get tertiary beneficiaries.
     */
    public function scopeTertiary($query)
    {
        return $query->where('priority', 'tertiary');
    }

    /**
     * Scope to get quaternary beneficiaries.
     */
    public function scopeQuaternary($query)
    {
        return $query->where('priority', 'quaternary');
    }

    /**
     * Scope to order by priority level.
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderByRaw("CASE priority 
            WHEN 'primary' THEN 1 
            WHEN 'secondary' THEN 2 
            WHEN 'tertiary' THEN 3 
            WHEN 'quaternary' THEN 4 
            END")->orderBy('name', 'asc');
    }
}
