<?php

/**
 * AccountMetadata.php
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

class AccountMetadata extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'account_id',
        'name',
        'data',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get metadata value as JSON if it's valid JSON, otherwise as string
     */
    public function getParsedValueAttribute(): mixed
    {
        $decoded = json_decode($this->data, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->data;
    }

    /**
     * Set metadata value, automatically encoding as JSON if it's an array or object
     */
    public function setParsedValueAttribute(mixed $value): void
    {
        if (is_array($value) || is_object($value)) {
            $this->data = json_encode($value);
        } else {
            $this->data = (string) $value;
        }
    }
}
