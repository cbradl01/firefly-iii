<?php

declare(strict_types=1);

namespace FireflyIII\Services;

use FireflyIII\Models\FinancialEntity;
use FireflyIII\Models\EntityRelationship;
use FireflyIII\Models\AccountEntityRole;
use FireflyIII\Models\Account;
use FireflyIII\User;
use Illuminate\Support\Collection;

class FinancialEntityService
{
    /**
     * Create a new financial entity
     */
    public function createEntity(array $data): FinancialEntity
    {
        // Map entity-specific field names to database column names
        $mappedData = $this->mapFieldsToDatabase($data);
        
        return FinancialEntity::create($mappedData);
    }
    
    /**
     * Map entity-specific field names to database column names
     */
    private function mapFieldsToDatabase(array $data): array
    {
        $mappedData = $data;
        
        // Map entity-specific name fields to the 'name' column
        if (isset($data['individual_name'])) {
            $mappedData['name'] = $data['individual_name'];
            unset($mappedData['individual_name']);
        } elseif (isset($data['institution_name'])) {
            $mappedData['name'] = $data['institution_name'];
            unset($mappedData['institution_name']);
        } elseif (isset($data['trustee_name'])) {
            $mappedData['name'] = $data['trustee_name'];
            unset($mappedData['trustee_name']);
        } elseif (isset($data['business_name'])) {
            $mappedData['name'] = $data['business_name'];
            unset($mappedData['business_name']);
        } elseif (isset($data['advisor_name'])) {
            $mappedData['name'] = $data['advisor_name'];
            unset($mappedData['advisor_name']);
        } elseif (isset($data['custodian_name'])) {
            $mappedData['name'] = $data['custodian_name'];
            unset($mappedData['custodian_name']);
        } elseif (isset($data['plan_administrator_name'])) {
            $mappedData['name'] = $data['plan_administrator_name'];
            unset($mappedData['plan_administrator_name']);
        }
        
        // Store all other fields in metadata
        $metadata = [];
        foreach ($mappedData as $key => $value) {
            if (!in_array($key, ['name', 'entity_type', 'display_name', 'description', 'contact_info', 'is_active'])) {
                $metadata[$key] = $value;
                unset($mappedData[$key]);
            }
        }
        
        if (!empty($metadata)) {
            $mappedData['metadata'] = $metadata;
        }
        
        return $mappedData;
    }
    
    /**
     * Update an existing financial entity
     */
    public function updateEntity(FinancialEntity $entity, array $data): FinancialEntity
    {
        // Map entity-specific field names to database column names
        $mappedData = $this->mapFieldsToDatabase($data);
        
        // Handle metadata merging for updates
        if (isset($mappedData['metadata'])) {
            $existingMetadata = $entity->metadata ?? [];
            $newMetadata = $mappedData['metadata'];
            $mappedData['metadata'] = array_merge($existingMetadata, $newMetadata);
        }
        
        // Use fill and save instead of update
        $entity->fill($mappedData);
        $entity->save();
        
        return $entity;
    }
    
    /**
     * Map database fields back to form field names for editing
     */
    public function mapFieldsFromDatabase(FinancialEntity $entity): array
    {
        $data = $entity->toArray();
        
        // Map the 'name' field back to entity-specific field names
        if ($entity->entity_type === FinancialEntity::TYPE_INDIVIDUAL) {
            $data['individual_name'] = $entity->name;
        } elseif ($entity->entity_type === FinancialEntity::TYPE_INSTITUTION) {
            $data['institution_name'] = $entity->name;
        } elseif ($entity->entity_type === FinancialEntity::TYPE_TRUST) {
            $data['trustee_name'] = $entity->name;
        } elseif ($entity->entity_type === FinancialEntity::TYPE_BUSINESS) {
            $data['business_name'] = $entity->name;
        } elseif ($entity->entity_type === FinancialEntity::TYPE_ADVISOR) {
            $data['advisor_name'] = $entity->name;
        } elseif ($entity->entity_type === FinancialEntity::TYPE_CUSTODIAN) {
            $data['custodian_name'] = $entity->name;
        } elseif ($entity->entity_type === FinancialEntity::TYPE_PLAN_ADMINISTRATOR) {
            $data['plan_administrator_name'] = $entity->name;
        }
        
        // Merge metadata fields back into the main data array
        if (isset($entity->metadata) && is_array($entity->metadata)) {
            $data = array_merge($data, $entity->metadata);
        }
        
        return $data;
    }

    /**
     * Get all entities that a user can manage
     */
    public function getManageableEntities(User $user): Collection
    {
        // For now, return all entities (we'll add permission logic later)
        return FinancialEntity::active()->get();
    }

    /**
     * Get all entities of a specific type
     */
    public function getEntitiesByType(string $type): Collection
    {
        return FinancialEntity::active()->ofType($type)->get();
    }

    /**
     * Get all individuals
     */
    public function getIndividuals(): Collection
    {
        return $this->getEntitiesByType(FinancialEntity::TYPE_INDIVIDUAL);
    }

    /**
     * Get all trusts
     */
    public function getTrusts(): Collection
    {
        return $this->getEntitiesByType(FinancialEntity::TYPE_TRUST);
    }

    /**
     * Get all businesses
     */
    public function getBusinesses(): Collection
    {
        return $this->getEntitiesByType(FinancialEntity::TYPE_BUSINESS);
    }

    /**
     * Get all advisors
     */
    public function getAdvisors(): Collection
    {
        return $this->getEntitiesByType(FinancialEntity::TYPE_ADVISOR);
    }

    /**
     * Get all custodians
     */
    public function getCustodians(): Collection
    {
        return $this->getEntitiesByType(FinancialEntity::TYPE_CUSTODIAN);
    }

    /**
     * Get all institutions
     */
    public function getInstitutions(): Collection
    {
        return $this->getEntitiesByType(FinancialEntity::TYPE_INSTITUTION);
    }

    /**
     * Create a relationship between two entities
     */
    public function createRelationship(int $entityId, int $relatedEntityId, string $relationshipType, array $metadata = []): EntityRelationship
    {
        return EntityRelationship::create([
            'entity_id' => $entityId,
            'related_entity_id' => $relatedEntityId,
            'relationship_type' => $relationshipType,
            'relationship_metadata' => $metadata,
        ]);
    }

    /**
     * Get all relationships for an entity
     */
    public function getEntityRelationships(FinancialEntity $entity): Collection
    {
        return $entity->relationships()->with('relatedEntity')->get();
    }

    /**
     * Get all beneficiaries for a trust
     */
    public function getTrustBeneficiaries(FinancialEntity $trust): Collection
    {
        return $trust->beneficiaries()->get();
    }

    /**
     * Get all trustees for a trust
     */
    public function getTrustTrustees(FinancialEntity $trust): Collection
    {
        return $trust->trustees()->get();
    }

    /**
     * Assign an entity role to an account
     */
    public function assignAccountRole(Account $account, FinancialEntity $entity, string $roleType, float $percentage = null, array $metadata = []): AccountEntityRole
    {
        return AccountEntityRole::create([
            'account_id' => $account->id,
            'entity_id' => $entity->id,
            'role_type' => $roleType,
            'percentage' => $percentage,
            'role_metadata' => $metadata,
        ]);
    }

    /**
     * Get all accounts for an entity
     */
    public function getEntityAccounts(FinancialEntity $entity): Collection
    {
        return $entity->accounts()->with(['accountType.category', 'accountType.behavior'])->get();
    }

    /**
     * Get all accounts where an entity has a specific role
     */
    public function getAccountsByEntityRole(FinancialEntity $entity, string $roleType): Collection
    {
        return AccountEntityRole::where('entity_id', $entity->id)
            ->where('role_type', $roleType)
            ->with(['account.accountType.category', 'account.accountType.behavior'])
            ->get()
            ->pluck('account');
    }

    /**
     * Get all accounts where an entity is a beneficiary
     */
    public function getBeneficiaryAccounts(FinancialEntity $entity): Collection
    {
        return $this->getAccountsByEntityRole($entity, AccountEntityRole::ROLE_BENEFICIARY);
    }

    /**
     * Get all accounts where an entity is a trustee
     */
    public function getTrusteeAccounts(FinancialEntity $entity): Collection
    {
        return $this->getAccountsByEntityRole($entity, AccountEntityRole::ROLE_TRUSTEE);
    }

    /**
     * Get all accounts where an entity is an advisor
     */
    public function getAdvisorAccounts(FinancialEntity $entity): Collection
    {
        return $this->getAccountsByEntityRole($entity, AccountEntityRole::ROLE_ADVISOR);
    }

    /**
     * Get total balance for all accounts owned by an entity
     */
    public function getEntityTotalBalance(FinancialEntity $entity): float
    {
        return $entity->accounts()->sum('virtual_balance');
    }

    /**
     * Get total balance for all accounts where an entity is a beneficiary
     */
    public function getBeneficiaryTotalBalance(FinancialEntity $entity): float
    {
        $beneficiaryAccounts = $this->getBeneficiaryAccounts($entity);
        $totalBalance = 0;

        foreach ($beneficiaryAccounts as $account) {
            $role = AccountEntityRole::where('account_id', $account->id)
                ->where('entity_id', $entity->id)
                ->where('role_type', AccountEntityRole::ROLE_BENEFICIARY)
                ->first();

            if ($role && $role->percentage) {
                $totalBalance += ($account->virtual_balance * $role->percentage / 100);
            } else {
                // If no percentage specified, assume equal share among beneficiaries
                $beneficiaryCount = AccountEntityRole::where('account_id', $account->id)
                    ->where('role_type', AccountEntityRole::ROLE_BENEFICIARY)
                    ->count();
                
                if ($beneficiaryCount > 0) {
                    $totalBalance += ($account->virtual_balance / $beneficiaryCount);
                }
            }
        }

        return $totalBalance;
    }

    /**
     * Search entities by name or description
     */
    public function searchEntities(string $query): Collection
    {
        return FinancialEntity::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('display_name', 'ILIKE', "%{$query}%")
                  ->orWhere('description', 'ILIKE', "%{$query}%");
            })
            ->get();
    }

    /**
     * Get entity statistics
     */
    public function getEntityStatistics(FinancialEntity $entity): array
    {
        $ownedAccounts = $this->getEntityAccounts($entity);
        $beneficiaryAccounts = $this->getBeneficiaryAccounts($entity);
        $trusteeAccounts = $this->getTrusteeAccounts($entity);
        $advisorAccounts = $this->getAdvisorAccounts($entity);

        return [
            'owned_accounts_count' => $ownedAccounts->count(),
            'owned_accounts_balance' => $this->getEntityTotalBalance($entity),
            'beneficiary_accounts_count' => $beneficiaryAccounts->count(),
            'beneficiary_accounts_balance' => $this->getBeneficiaryTotalBalance($entity),
            'trustee_accounts_count' => $trusteeAccounts->count(),
            'advisor_accounts_count' => $advisorAccounts->count(),
            'total_relationships' => $entity->relationships()->count(),
        ];
    }
}
