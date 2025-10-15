<?php

/**
 * AccountTemplate.php
 * Copyright (c) 2025 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountTemplate extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'name',
        'account_type_id',
        'description',
        'metadata_preset',
        'suggested_fields',
        'field_requirements',
        'is_system_template',
        'created_by_user_id',
        'active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'active' => 'boolean',
        'is_system_template' => 'boolean',
        'metadata_preset' => 'array',
        'suggested_fields' => 'array',
        'field_requirements' => 'array',
    ];

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'template_id');
    }

    /**
     * Get the metadata preset as an array
     */
    public function getMetadataPresetAttribute($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    /**
     * Get the suggested fields as an array
     */
    public function getSuggestedFieldsAttribute($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    /**
     * Scope to get only system templates
     */
    public function scopeSystemTemplates($query)
    {
        return $query->where('is_system_template', true);
    }

    /**
     * Scope to get only user-created templates
     */
    public function scopeUserTemplates($query)
    {
        return $query->where('is_system_template', false);
    }

    /**
     * Scope to get templates for a specific account type
     */
    public function scopeForAccountType($query, int $accountTypeId)
    {
        return $query->where('account_type_id', $accountTypeId);
    }

    /**
     * Scope to get active templates
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}