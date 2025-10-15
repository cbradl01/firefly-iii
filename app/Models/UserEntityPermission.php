<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEntityPermission extends Model
{
    use HasFactory;
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'user_id',
        'entity_id',
        'permission_level',
        'permission_metadata',
        'is_active',
    ];

    protected $casts = [
        'permission_metadata' => 'array',
        'is_active' => 'boolean',
    ];

    // Permission levels
    public const LEVEL_VIEW = 'view';
    public const LEVEL_EDIT = 'edit';
    public const LEVEL_ADMIN = 'admin';

    /**
     * Get the user for this permission
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the entity for this permission
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(FinancialEntity::class, 'entity_id');
    }

    /**
     * Scope for active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific permission level
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('permission_level', $level);
    }

    /**
     * Get permission metadata
     */
    public function getPermissionMetadataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set permission metadata
     */
    public function setPermissionMetadataAttribute($value)
    {
        $this->attributes['permission_metadata'] = json_encode($value);
    }

    /**
     * Check if this is an admin permission
     */
    public function isAdmin(): bool
    {
        return $this->permission_level === self::LEVEL_ADMIN;
    }

    /**
     * Check if this is an edit permission
     */
    public function isEdit(): bool
    {
        return $this->permission_level === self::LEVEL_EDIT;
    }

    /**
     * Check if this is a view permission
     */
    public function isView(): bool
    {
        return $this->permission_level === self::LEVEL_VIEW;
    }
}
