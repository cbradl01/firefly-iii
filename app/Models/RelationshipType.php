<?php

/**
 * RelationshipType.php
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
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationshipType extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'name',
        'description',
        'metadata_schema',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'metadata_schema' => 'array',
    ];

    public function accountRelationships(): HasMany
    {
        return $this->hasMany(AccountRelationship::class);
    }

    /**
     * Validate metadata against the schema
     */
    public function validateMetadata(array $metadata): bool
    {
        if (!$this->metadata_schema) {
            return true; // No schema means no validation required
        }

        foreach ($this->metadata_schema as $key => $expectedType) {
            if (!array_key_exists($key, $metadata)) {
                return false; // Required key missing
            }

            $actualType = gettype($metadata[$key]);
            if ($actualType !== $expectedType) {
                return false; // Type mismatch
            }
        }

        return true;
    }
}
