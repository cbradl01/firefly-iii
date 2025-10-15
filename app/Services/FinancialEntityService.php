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
        return FinancialEntity::create($data);
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
