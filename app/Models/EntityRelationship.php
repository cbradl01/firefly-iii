<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityRelationship extends Model
{
    use HasFactory;
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'entity_id',
        'related_entity_id',
        'relationship_type',
        'relationship_metadata',
        'is_active',
    ];

    protected $casts = [
        'relationship_metadata' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationship types
    public const TYPE_SPOUSE = 'spouse';
    public const TYPE_BENEFICIARY = 'beneficiary';
    public const TYPE_TRUSTEE = 'trustee';
    public const TYPE_CUSTODIAN = 'custodian';
    public const TYPE_ADVISOR = 'advisor';
    public const TYPE_PLAN_ADMINISTRATOR = 'plan_administrator';
    public const TYPE_CHILD = 'child';
    public const TYPE_PARENT = 'parent';
    public const TYPE_BUSINESS_PARTNER = 'business_partner';

    /**
     * Get the primary entity in this relationship
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(FinancialEntity::class, 'entity_id');
    }

    /**
     * Get the related entity in this relationship
     */
    public function relatedEntity(): BelongsTo
    {
        return $this->belongsTo(FinancialEntity::class, 'related_entity_id');
    }

    /**
     * Scope for active relationships
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific relationship type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Get relationship metadata
     */
    public function getRelationshipMetadataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set relationship metadata
     */
    public function setRelationshipMetadataAttribute($value)
    {
        $this->attributes['relationship_metadata'] = json_encode($value);
    }
}
