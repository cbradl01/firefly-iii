<?php

/**
 * PositionAllocation.php
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

class PositionAllocation extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'position_id',
        'container_account_id',
        'shares',
        'cost_basis',
        'allocation_percentage',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'shares' => 'decimal:6',
        'cost_basis' => 'decimal:2',
        'allocation_percentage' => 'decimal:2',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(SecurityPosition::class);
    }

    public function containerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'container_account_id');
    }

    /**
     * Get the current market value of this allocation
     */
    public function getMarketValueAttribute(): float
    {
        return (float) $this->shares * (float) $this->position->current_price;
    }

    /**
     * Get the unrealized gain/loss for this allocation
     */
    public function getUnrealizedGainLossAttribute(): float
    {
        return $this->market_value - (float) $this->cost_basis;
    }

    /**
     * Get the unrealized gain/loss percentage for this allocation
     */
    public function getUnrealizedGainLossPercentageAttribute(): float
    {
        if ((float) $this->cost_basis === 0.0) {
            return 0.0;
        }
        
        return ($this->unrealized_gain_loss / (float) $this->cost_basis) * 100;
    }
}
