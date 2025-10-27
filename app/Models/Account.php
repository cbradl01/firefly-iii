<?php

/**
 * Account.php
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

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Handlers\Observer\AccountObserver;
use FireflyIII\Models\AccountCategory;
use FireflyIII\Models\AccountBehavior;
use FireflyIII\Models\FinancialEntity;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[ObservedBy([AccountObserver::class])]
class Account extends Model
{
    use HasFactory;
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $fillable = [];

    /**
     * Get system fields that are not user-facing but needed for model operations
     */
    protected function getSystemFields(): array
    {
        // TODO: what is the purpose of this?
        return [
            'user_id', 'user_group_id', 'account_type_id', 'template_id', 'entity_id', 
            'native_virtual_balance', 'name', 'active', 'iban', 'virtual_balance',
            'account_holder_ids', 'institution_id'
        ];
    }

    /**
     * Get fillable fields dynamically from FieldDefinitions + system fields
     */
    public function getFillable()
    {
        $systemFields = $this->getSystemFields();
        $accountFields = self::getAccountFields();
        $fieldNames = array_keys($accountFields);
        
        return array_merge($systemFields, $fieldNames);
    }

    protected $hidden                = ['encrypted'];
    private bool $joinedAccountTypes = false;

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $accountId = (int)$value;

            /** @var User $user */
            $user      = auth()->user();

            /** @var null|Account $account */
            $account   = $user->accounts()->with(['accountType.category', 'accountType.behavior'])->find($accountId);
            if (null !== $account) {
                return $account;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountBalances(): HasMany
    {
        return $this->hasMany(AccountBalance::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    // Template relationship removed - using account types directly now

    /**
     * Get the financial entity that owns this account
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(FinancialEntity::class, 'entity_id');
    }

    /**
     * Get all entity roles for this account
     */
    public function entityRoles(): HasMany
    {
        return $this->hasMany(AccountEntityRole::class);
    }

    /**
     * Get all owners of this account
     */
    public function owners()
    {
        return $this->entityRoles()
            ->where('role_type', AccountEntityRole::ROLE_OWNER)
            ->with('entity');
    }

    /**
     * Get all beneficiaries of this account (entity roles)
     */
    public function beneficiaryEntities()
    {
        return $this->entityRoles()
            ->where('role_type', AccountEntityRole::ROLE_BENEFICIARY)
            ->with('entity');
    }

    /**
     * Get all trustees of this account
     */
    public function trustees()
    {
        return $this->entityRoles()
            ->where('role_type', AccountEntityRole::ROLE_TRUSTEE)
            ->with('entity');
    }

    /**
     * Get all custodians of this account
     */
    public function custodians()
    {
        return $this->entityRoles()
            ->where('role_type', AccountEntityRole::ROLE_CUSTODIAN)
            ->with('entity');
    }

    /**
     * Get all advisors for this account
     */
    public function advisors()
    {
        return $this->entityRoles()
            ->where('role_type', AccountEntityRole::ROLE_ADVISOR)
            ->with('entity');
    }

    /**
     * Get category through account type (computed property)
     */
    public function getCategoryAttribute()
    {
        return $this->accountType?->category;
    }

    /**
     * Get behavior through account type (computed property)
     */
    public function getBehaviorAttribute()
    {
        return $this->accountType?->behavior;
    }


    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    /**
     * Get all the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * Get all the tags for the post.
     */
    public function objectGroups(): MorphToMany
    {
        return $this->morphToMany(ObjectGroup::class, 'object_groupable');
    }

    public function piggyBanks(): BelongsToMany
    {
        return $this->belongsToMany(PiggyBank::class);
    }

    public function setVirtualBalanceAttribute(mixed $value): void
    {
        $value                               = (string)$value;
        if ('' === $value) {
            $value = null;
        }
        $this->attributes['virtual_balance'] = $value;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    protected function accountId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    /**
     * Get the account number.
     */
    protected function accountNumber(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->attributes['account_number'] ?? '';
        });
    }

    /**
     * Get the account path for file system operations.
     */
    protected function accountPath(): Attribute
    {
        return Attribute::make(get: function () {
            $accountsPath = config('firefly.accounts_path'); // TODO: make this configurable
            
            $institution = $this->institutionEntity?->name;
            
            // Get account holders from the array field (multiple holders supported)
            $accountHolders = $this->account_holders ?? [];
            $accountHolder = !empty($accountHolders) ? $accountHolders[0] : 'Unknown';
            
            $accountType = $this->accountType?->name;
            $accountNumber = $this->account_number;

            $typeSuffix = $accountType ? " - {$accountType}" : "";
            $numberSuffix = $accountNumber ? " - {$accountNumber}" : "";
            
            $accountPath = "{$accountsPath}/{$accountHolder}/{$institution}{$typeSuffix}{$numberSuffix}";
            
            // Check if path exists, if not try closed accounts
            // if (!file_exists($accountPath)) {
            //     throw new \Exception("Account path does not exist: {$accountPath}");
            // }

            return $accountPath;
        });
    }

    // Removed accountMeta relationship - data now stored directly in accounts table

    public function accountHolder(): BelongsTo
    {
        return $this->belongsTo(FinancialEntity::class, 'account_holder_id');
    }

    public function institutionEntity(): BelongsTo
    {
        return $this->belongsTo(FinancialEntity::class, 'institution_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    // New account classification system relationships
    public function securityPosition(): HasMany
    {
        return $this->hasMany(SecurityPosition::class);
    }

    public function positionAllocations(): HasMany
    {
        return $this->hasMany(PositionAllocation::class, 'container_account_id');
    }

    public function parentRelationships(): HasMany
    {
        return $this->hasMany(AccountRelationship::class, 'parent_account_id');
    }

    public function childRelationships(): HasMany
    {
        return $this->hasMany(AccountRelationship::class, 'child_account_id');
    }

    public function containedAccounts()
    {
        return $this->belongsToMany(Account::class, 'account_relationships', 'parent_account_id', 'child_account_id')
            ->withPivot(['relationship_type_id', 'metadata'])
            ->withTimestamps();
    }

    public function containerAccounts()
    {
        return $this->belongsToMany(Account::class, 'account_relationships', 'child_account_id', 'parent_account_id')
            ->withPivot(['relationship_type_id', 'metadata'])
            ->withTimestamps();
    }

    public function cashComponent()
    {
        return $this->hasOne(Account::class, 'id')
            ->whereHas('childRelationships', function ($query) {
                $query->whereHas('relationshipType', function ($q) {
                    $q->where('name', 'contains');
                })->where('metadata->component_type', 'cash');
            });
    }

    /**
     * Get the account type name for backward compatibility
     */
    public function getAccountTypeNameAttribute(): string
    {
        if ($this->category && $this->behavior) {
            // Create a virtual account type name based on category and behavior
            return $this->category->name . ' (' . $this->behavior->name . ')';
        }
        
        return 'Unknown';
    }


    #[Scope]
    protected function accountTypeIn(EloquentBuilder $query, array $types): void
    {
        if (false === $this->joinedAccountTypes) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        
        // Find account type IDs for the given type names
        $accountTypeIds = AccountType::whereIn('name', $types)
            ->where('active', true)
            ->pluck('id')
            ->toArray();
        
        if (!empty($accountTypeIds)) {
            $query->whereIn('accounts.account_type_id', $accountTypeIds);
        } else {
            // If no matching account types found, ensure no results
            $query->whereRaw('1 = 0');
        }
    }


    protected function casts(): array
    {
        // TODO: what is the purpose of this?
        return [
            'created_at'             => 'datetime',
            'updated_at'             => 'datetime',
            'deleted_at'             => 'datetime',
            'encrypted'              => 'boolean',
            'native_virtual_balance' => 'string',
            'account_holders'        => 'array',
            'account_holder_ids'     => 'array',
        ];
    }

    protected function editName(): Attribute
    {
        return Attribute::make(get: function () {
            $name = $this->name;
            if ($this->accountType && $this->accountType->name === 'Cash account') {
                return '';
            }

            return $name;
        });
    }

    protected function iban(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->account_number ? trim(str_replace(' ', '', (string)$this->account_number)) : null,
            set: fn ($value) => $this->attributes['account_number'] = $value,
        );
    }


    /**
     * Get the virtual balance
     */
    protected function virtualBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['virtual_balance'] ?? null,
            set: fn ($value) => $this->attributes['virtual_balance'] = $value,
        );
    }



    public function primaryPeriodStatistics(): MorphMany
    {

        return $this->morphMany(PeriodStatistic::class, 'primary_statable');

    }

    /**
     * Calculate balance using the account type's behavior
     */
    public function calculateBalance(): float
    {
        return $this->accountType->calculateBalance($this);
    }

    /**
     * Check if this account is a container account
     */
    public function isContainer(): bool
    {
        return $this->accountType->isContainer();
    }

    /**
     * Check if this account is a security account
     */
    public function isSecurity(): bool
    {
        return $this->accountType->isSecurity();
    }

    /**
     * Check if this account is a simple account
     */
    public function isSimple(): bool
    {
        return $this->accountType->isSimple();
    }

    /**
     * Get metadata value by key (now stored directly in accounts table)
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->getAttribute($key) ?? $default;
    }

    /**
     * Set metadata value (now stored directly in accounts table)
     */
    public function setMetadataValue(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Get all account fields from FieldDefinitions
     * 
     * @return array
     */
    public static function getAccountFields(): array
    {
        return \FireflyIII\FieldDefinitions\FieldDefinitions::getFieldsForTargetType('account');
    }

    /**
     * Get overview data for display on account show page
     * Only shows fields that are defined in the account type's firefly_mapping
     * 
     * @return array
     */
    public function getOverviewData(): array
    {
        // Get all possible overview fields from FieldDefinitions
        $allOverviewFields = \FireflyIII\FieldDefinitions\FieldDefinitions::getOverviewFields('account');
        
        // Get the fields that are actually configured for this account type
        $accountTypeFields = [];
        if ($this->accountType && $this->accountType->firefly_mapping) {
            $accountTypeFields = $this->accountType->firefly_mapping['account_fields'] ?? [];
        }
        
        // Filter to only show fields that are defined in the account type template
        $overviewFields = [];
        foreach ($allOverviewFields as $fieldName => $fieldData) {
            if (isset($accountTypeFields[$fieldName])) {
                $overviewFields[$fieldName] = $fieldData;
            }
        }
        
        $overviewData = [];
        
        foreach ($overviewFields as $fieldName => $fieldData) {
            $value = $this->getMetadataValue($fieldName);
            
            // Special handling for account_holder to get the entity name and link
            if ($fieldName === 'account_holder' && $value) {
                $entity = \FireflyIII\Models\FinancialEntity::where('name', $value)->first();
                if ($entity) {
                    $displayValue = $entity->display_name ?? $entity->name;
                    $overviewData[$fieldName] = [
                        'value' => $displayValue,
                        'link' => route('financial-entities.show', $entity->id),
                        'field_data' => $fieldData
                    ];
                } else {
                    $overviewData[$fieldName] = [
                        'value' => \FireflyIII\FieldDefinitions\FieldDefinitions::getOverviewDisplayValue($fieldName, $value, $fieldData),
                        'link' => null,
                        'field_data' => $fieldData
                    ];
                }
            } else {
                $overviewData[$fieldName] = [
                    'value' => \FireflyIII\FieldDefinitions\FieldDefinitions::getOverviewDisplayValue($fieldName, $value, $fieldData),
                    'link' => null,
                    'field_data' => $fieldData
                ];
            }
        }
        
        return $overviewData;
    }
}
