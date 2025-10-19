<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FinancialEntity extends Model
{
    use HasFactory;
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'name',
        'entity_type',
        'display_name',
        'description',
        'contact_info',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'contact_info' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    // Entity types
    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_TRUST = 'trust';
    public const TYPE_BUSINESS = 'business';
    public const TYPE_ADVISOR = 'advisor';
    public const TYPE_CUSTODIAN = 'custodian';
    public const TYPE_PLAN_ADMINISTRATOR = 'plan_administrator';

    /**
     * Get all accounts owned by this entity
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'entity_id');
    }

    /**
     * Get all account roles for this entity
     */
    public function accountRoles(): HasMany
    {
        return $this->hasMany(AccountEntityRole::class, 'entity_id');
    }

    /**
     * Get all relationships where this entity is the primary entity
     */
    public function relationships(): HasMany
    {
        return $this->hasMany(EntityRelationship::class, 'entity_id');
    }

    /**
     * Get all relationships where this entity is the related entity
     */
    public function relatedRelationships(): HasMany
    {
        return $this->hasMany(EntityRelationship::class, 'related_entity_id');
    }

    /**
     * Get all users who have permissions for this entity
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_entity_permissions', 'entity_id', 'user_id')
            ->withPivot(['permission_level', 'permission_metadata', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get all notes for this entity
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * Scope for active entities
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific entity type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Get display name or fall back to name
     */
    public function getDisplayNameAttribute($value)
    {
        return $value ?: $this->name;
    }

    /**
     * Get contact information
     */
    public function getContactInfoAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set contact information
     */
    public function setContactInfoAttribute($value)
    {
        $this->attributes['contact_info'] = json_encode($value);
    }

    /**
     * Get metadata
     */
    public function getMetadataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set metadata
     */
    public function setMetadataAttribute($value)
    {
        $this->attributes['metadata'] = json_encode($value);
    }

    /**
     * Check if this entity is a trust
     */
    public function isTrust(): bool
    {
        return $this->entity_type === self::TYPE_TRUST;
    }

    /**
     * Check if this entity is an individual
     */
    public function isIndividual(): bool
    {
        return $this->entity_type === self::TYPE_INDIVIDUAL;
    }

    /**
     * Check if this entity is a business
     */
    public function isBusiness(): bool
    {
        return $this->entity_type === self::TYPE_BUSINESS;
    }

    /**
     * Get all beneficiaries for this entity (if it's a trust)
     */
    public function beneficiaries()
    {
        return $this->relationships()
            ->where('relationship_type', 'beneficiary')
            ->with('relatedEntity');
    }

    /**
     * Get all trustees for this entity (if it's a trust)
     */
    public function trustees()
    {
        return $this->relationships()
            ->where('relationship_type', 'trustee')
            ->with('relatedEntity');
    }

    /**
     * Get all accounts where this entity is a beneficiary
     */
    public function beneficiaryAccounts()
    {
        return $this->accountRoles()
            ->where('role_type', 'beneficiary')
            ->with('account');
    }

    /**
     * Get all accounts where this entity is a trustee
     */
    public function trusteeAccounts()
    {
        return $this->accountRoles()
            ->where('role_type', 'trustee')
            ->with('account');
    }
}
