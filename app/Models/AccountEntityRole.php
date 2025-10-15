<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountEntityRole extends Model
{
    use HasFactory;
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'account_id',
        'entity_id',
        'role_type',
        'percentage',
        'role_metadata',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'role_metadata' => 'array',
        'is_active' => 'boolean',
    ];

    // Role types
    public const ROLE_OWNER = 'owner';
    public const ROLE_BENEFICIARY = 'beneficiary';
    public const ROLE_TRUSTEE = 'trustee';
    public const ROLE_CUSTODIAN = 'custodian';
    public const ROLE_ADVISOR = 'advisor';
    public const ROLE_PLAN_ADMINISTRATOR = 'plan_administrator';
    public const ROLE_POWER_OF_ATTORNEY = 'power_of_attorney';
    public const ROLE_GUARDIAN = 'guardian';

    /**
     * Get the account for this role
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the entity for this role
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(FinancialEntity::class, 'entity_id');
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific role type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('role_type', $type);
    }

    /**
     * Scope for owners only
     */
    public function scopeOwners($query)
    {
        return $query->where('role_type', self::ROLE_OWNER);
    }

    /**
     * Scope for beneficiaries only
     */
    public function scopeBeneficiaries($query)
    {
        return $query->where('role_type', self::ROLE_BENEFICIARY);
    }

    /**
     * Scope for trustees only
     */
    public function scopeTrustees($query)
    {
        return $query->where('role_type', self::ROLE_TRUSTEE);
    }

    /**
     * Get role metadata
     */
    public function getRoleMetadataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set role metadata
     */
    public function setRoleMetadataAttribute($value)
    {
        $this->attributes['role_metadata'] = json_encode($value);
    }

    /**
     * Check if this is an ownership role
     */
    public function isOwnership(): bool
    {
        return $this->role_type === self::ROLE_OWNER;
    }

    /**
     * Check if this is a beneficiary role
     */
    public function isBeneficiary(): bool
    {
        return $this->role_type === self::ROLE_BENEFICIARY;
    }

    /**
     * Check if this is a trustee role
     */
    public function isTrustee(): bool
    {
        return $this->role_type === self::ROLE_TRUSTEE;
    }

    /**
     * Get percentage as decimal (0.0 to 1.0)
     */
    public function getPercentageDecimalAttribute(): float
    {
        return $this->percentage ? $this->percentage / 100 : 0.0;
    }

    /**
     * Set percentage from decimal (0.0 to 1.0)
     */
    public function setPercentageDecimalAttribute(float $value)
    {
        $this->percentage = $value * 100;
    }
}
