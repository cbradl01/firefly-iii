<?php

/**
 * AccountType.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use Deprecated;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    use ReturnsIntegerIdTrait;

    #[Deprecated]
    /** @deprecated */
    public const string ASSET            = 'Asset account';

    #[Deprecated]
    /** @deprecated */
    public const string BENEFICIARY      = 'Beneficiary account';

    #[Deprecated]
    /** @deprecated */
    public const string CASH             = 'Cash account';

    #[Deprecated]
    /** @deprecated */
    public const string CREDITCARD       = 'Credit card';

    #[Deprecated]
    /** @deprecated */
    public const string DEBT             = 'Debt';

    #[Deprecated]
    /** @deprecated */
    public const string DEFAULT          = 'Default account';

    #[Deprecated]
    /** @deprecated */
    public const string EXPENSE          = 'Expense account';

    #[Deprecated]
    /** @deprecated */
    public const string IMPORT           = 'Import account';

    #[Deprecated]
    /** @deprecated */
    public const string INITIAL_BALANCE  = 'Initial balance account';

    #[Deprecated]
    /** @deprecated */
    public const string LIABILITY_CREDIT = 'Liability credit account';

    #[Deprecated]
    /** @deprecated */
    public const string LOAN             = 'Loan';

    #[Deprecated]
    /** @deprecated */
    public const string MORTGAGE         = 'Mortgage';

    #[Deprecated]
    /** @deprecated */
    public const string RECONCILIATION   = 'Reconciliation account';

    #[Deprecated]
    /** @deprecated */
    public const string REVENUE          = 'Revenue account';

    protected $fillable = [
        'name',
        'category_id',
        'behavior_id',
        'description',
        'firefly_mapping',
        'metadata_schema',
        'active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'active' => 'boolean',
        'metadata_schema' => 'array',
        'firefly_mapping' => 'array',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class);
    }

    public function behavior(): BelongsTo
    {
        return $this->belongsTo(AccountBehavior::class);
    }

    /**
     * Get the legacy 'type' attribute for backward compatibility
     */
    public function getTypeAttribute(): string
    {
        return $this->name;
    }

    /**
     * Calculate account balance using the behavior's calculation method
     */
    public function calculateBalance(Account $account): float
    {
        return $this->behavior->calculateBalance($account);
    }

    /**
     * Check if this account type is a container type
     */
    public function isContainer(): bool
    {
        return $this->behavior->calculation_method === 'sum_contained';
    }

    /**
     * Check if this account type is a security type
     */
    public function isSecurity(): bool
    {
        return $this->behavior->calculation_method === 'shares_times_price';
    }

    /**
     * Check if this account type is a simple type
     */
    public function isSimple(): bool
    {
        return $this->behavior->calculation_method === 'direct_balance';
    }

    /**
     * Scope to find account type by name (for backward compatibility with whereType)
     */
    public function scopeWhereType($query, string $type)
    {
        return $query->where('name', $type);
    }
}
