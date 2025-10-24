<?php

declare(strict_types=1);

namespace FireflyIII\Services;

use FireflyIII\Models\FinancialEntity;
use FireflyIII\Models\UserEntityPermission;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

class UserEntityService
{
    /**
     * Ensure a user has an individual financial entity
     * This should be called when a user first logs in or when they need an entity
     */
    public function ensureUserEntity(User $user): FinancialEntity
    {
        // Check if user already has an individual entity
        $existingEntity = FinancialEntity::where('entity_type', FinancialEntity::TYPE_INDIVIDUAL)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if ($existingEntity) {
            return $existingEntity;
        }

        // Create new individual entity for user
        $entity = FinancialEntity::create([
            'name' => $this->generateEntityName($user),
            'entity_type' => FinancialEntity::TYPE_INDIVIDUAL,
            'display_name' => $this->generateEntityName($user),
            'description' => 'Individual account holder',
            'contact_info' => [
                'email' => $user->email,
            ],
            'metadata' => [
                'user_id' => $user->id,
                'created_automatically' => true,
            ],
        ]);

        // Give user admin permissions for their own entity
        UserEntityPermission::create([
            'user_id' => $user->id,
            'entity_id' => $entity->id,
            'permission_level' => 'admin',
            'permission_metadata' => [
                'is_owner' => true,
            ],
        ]);

        Log::info("Created individual financial entity for user {$user->id}: {$entity->name}");

        return $entity;
    }

    /**
     * Generate a name for the user's entity
     * For now, use email prefix, but this should be updated when we have first/last name
     */
    private function generateEntityName(User $user): string
    {
        $emailPrefix = explode('@', $user->email)[0];
        return ucfirst($emailPrefix) . ' (Individual)';
    }

    /**
     * Update user's entity with first and last name
     */
    public function updateUserEntityWithName(User $user, string $firstName, string $lastName): FinancialEntity
    {
        $entity = $this->ensureUserEntity($user);
        
        $fullName = trim($firstName . ' ' . $lastName);
        
        $entity->update([
            'name' => $fullName,
            'display_name' => $fullName,
            'metadata' => array_merge($entity->metadata, [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]),
        ]);

        Log::info("Updated financial entity for user {$user->id} with name: {$fullName}");

        return $entity;
    }

    /**
     * Get user's primary entity
     */
    public function getUserEntity(User $user): ?FinancialEntity
    {
        // Return the first individual entity or null if none exist
        // In the current system, we don't need user-specific entities
        return FinancialEntity::where('entity_type', FinancialEntity::TYPE_INDIVIDUAL)
            ->first();
    }

    /**
     * Get all entities a user can manage
     */
    public function getUserManageableEntities(User $user): \Illuminate\Support\Collection
    {
        // Return all active financial entities - no permission check needed for basic entities
        return FinancialEntity::active()->get();
    }
}
