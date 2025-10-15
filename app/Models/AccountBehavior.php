<?php

/**
 * AccountBehavior.php
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

class AccountBehavior extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'name',
        'description',
        'calculation_method',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function accountTypes(): HasMany
    {
        return $this->hasMany(AccountType::class);
    }

    /**
     * Get all accounts that use this behavior
     */
    public function accounts()
    {
        return $this->hasManyThrough(Account::class, AccountType::class);
    }

    /**
     * Calculate account balance based on behavior type
     */
    public function calculateBalance(Account $account): float
    {
        return match ($this->calculation_method) {
            'direct_balance' => $this->calculateDirectBalance($account),
            'sum_contained' => $this->calculateContainerBalance($account),
            'shares_times_price' => $this->calculateSecurityBalance($account),
            default => 0.0,
        };
    }

    private function calculateDirectBalance(Account $account): float
    {
        // For simple accounts, return the account's balance directly
        return (float) $account->balance;
    }

    private function calculateContainerBalance(Account $account): float
    {
        // For container accounts, sum all contained accounts and positions
        $balance = 0.0;
        
        // Add cash component
        $cashComponent = $account->cashComponent();
        if ($cashComponent) {
            $balance += (float) $cashComponent->balance;
        }
        
        // Add contained accounts
        $containedAccounts = $account->containedAccounts();
        foreach ($containedAccounts as $containedAccount) {
            $balance += (float) $containedAccount->balance;
        }
        
        return $balance;
    }

    private function calculateSecurityBalance(Account $account): float
    {
        // For security accounts, calculate shares × current price
        $position = $account->securityPosition()->first();
        if (!$position) {
            return 0.0;
        }
        
        return (float) $position->shares * (float) $position->current_price;
    }
}
