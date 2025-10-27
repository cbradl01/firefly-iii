<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Models\FinancialEntity;
use FireflyIII\Models\EntityRelationship;
use FireflyIII\Services\FinancialEntityService;
use FireflyIII\Services\UserEntityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinancialEntityController extends Controller
{
    protected FinancialEntityService $entityService;
    protected UserEntityService $userEntityService;

    public function __construct(FinancialEntityService $entityService, UserEntityService $userEntityService)
    {
        parent::__construct();
        $this->entityService = $entityService;
        $this->userEntityService = $userEntityService;

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', 'Financial Entities');
                app('view')->share('mainTitleIcon', 'fa-users');

                return $next($request);
            }
        );
    }

    /**
     * Display a listing of financial entities
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $entities = $this->userEntityService->getUserManageableEntities($user);
        
        // Filter by type if requested
        if ($request->has('type') && $request->type) {
            $entities = $entities->where('entity_type', $request->type);
        }

        // Search if requested
        if ($request->has('search') && $request->search) {
            $entities = $this->entityService->searchEntities($request->search);
        }

        $entityTypes = [
            FinancialEntity::TYPE_INDIVIDUAL => 'Individuals',
            FinancialEntity::TYPE_TRUST => 'Trusts',
            FinancialEntity::TYPE_BUSINESS => 'Businesses',
            FinancialEntity::TYPE_ADVISOR => 'Advisors',
            FinancialEntity::TYPE_CUSTODIAN => 'Custodians',
            FinancialEntity::TYPE_INSTITUTION => 'Institutions',
        ];

        // Pass request parameters to view
        $searchQuery = $request->get('search', '');
        $typeFilter = $request->get('type', '');

        return view('financial-entities.index', compact('entities', 'entityTypes', 'searchQuery', 'typeFilter'))
            ->with('entityService', $this->entityService);
    }

    /**
     * Show the modal form for creating a new financial entity
     */
    public function createModal(Request $request): View
    {
        $entityTypes = [
            FinancialEntity::TYPE_INDIVIDUAL => 'Individual',
            FinancialEntity::TYPE_TRUST => 'Trust',
            FinancialEntity::TYPE_BUSINESS => 'Business',
            FinancialEntity::TYPE_ADVISOR => 'Financial Advisor',
            FinancialEntity::TYPE_CUSTODIAN => 'Custodian',
            FinancialEntity::TYPE_PLAN_ADMINISTRATOR => 'Plan Administrator',
            FinancialEntity::TYPE_INSTITUTION => 'Institution',
        ];

        // Get all entities that can be trustees (individuals, businesses, advisors)
        // Only include entities that the current user has permission to see
        $user = Auth::user();
        $trusteeOptions = FinancialEntity::active()
            ->whereIn('entity_type', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'entity_type']);

        // Create a dummy entity for the form (empty values)
        $financialEntity = new FinancialEntity();
        
        // Pre-select entity type if provided
        $preselectedEntityType = $request->get('entity_type');
        if ($preselectedEntityType && array_key_exists($preselectedEntityType, $entityTypes)) {
            $financialEntity->entity_type = $preselectedEntityType;
        }
        
        $currentTrustee = null;
        $isCreate = true;

        // Get field definitions for all entity types
        $fieldDefinitions = app(\FireflyIII\Services\FieldDefinitionService::class);

        return view('financial-entities.edit-modal', compact('entityTypes', 'trusteeOptions', 'financialEntity', 'currentTrustee', 'isCreate', 'fieldDefinitions'));
    }

    /**
     * Show the form for creating a new financial entity
     */
    public function create(): View
    {
        $entityTypes = [
            FinancialEntity::TYPE_INDIVIDUAL => 'Individual',
            FinancialEntity::TYPE_TRUST => 'Trust',
            FinancialEntity::TYPE_BUSINESS => 'Business',
            FinancialEntity::TYPE_ADVISOR => 'Financial Advisor',
            FinancialEntity::TYPE_CUSTODIAN => 'Custodian',
            FinancialEntity::TYPE_INSTITUTION => 'Institution',
        ];

        // Get all entities that can be trustees (individuals, businesses, advisors)
        // Only include entities that the current user has permission to see
        $user = Auth::user();
        $trusteeOptions = FinancialEntity::active()
            ->whereIn('entity_type', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'entity_type']);

        return view('financial-entities.create', compact('entityTypes', 'trusteeOptions'));
    }

    /**
     * Store a newly created financial entity via modal
     */
    public function storeModal(Request $request): JsonResponse
    {
        // Validate entity data
        $validationResult = $this->validateEntityData($request);
        
        if (!$validationResult['success']) {
            return response()->json($validationResult, 422);
        }
        
        $validated = $validationResult['data'];

        $entity = $this->entityService->createEntity($validated);

        // If this is a trust and a trustee is selected, create the trustee relationship
        if ($entity->entity_type === FinancialEntity::TYPE_TRUST && !empty($validated['trustee_id'])) {
            $this->entityService->createRelationship(
                $entity->id,
                (int) $validated['trustee_id'],
                EntityRelationship::TYPE_TRUSTEE,
                ['created_via' => 'entity_creation']
            );
        }

        // Give the current user admin permissions for the new entity
        $user = Auth::user();
        \FireflyIII\Models\UserEntityPermission::create([
            'user_id' => $user->id,
            'entity_id' => $entity->id,
            'permission_level' => 'admin',
            'permission_metadata' => [
                'is_creator' => true,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Financial entity created successfully.',
            'entity' => [
                'id' => $entity->id,
                'name' => $entity->name,
                'display_name' => $entity->display_name,
                'entity_type' => $entity->entity_type,
                'description' => $entity->description,
                'contact_info' => $entity->contact_info,
            ]
        ]);
    }

    /**
     * Store a newly created financial entity
     */
    public function store(Request $request)
    {
        // First validate the entity type
        $entityType = $request->validate([
            'entity_type' => 'required|string|in:' . implode(',', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_TRUST,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
                FinancialEntity::TYPE_CUSTODIAN,
                FinancialEntity::TYPE_PLAN_ADMINISTRATOR,
                FinancialEntity::TYPE_INSTITUTION,
            ])
        ])['entity_type'];

        // Get field definitions for this entity type
        $fieldDefinitions = app(\FireflyIII\Services\FieldDefinitionService::class);
        $allValidationRules = $fieldDefinitions->getValidationRules($entityType);
        
        // Only validate fields that are actually present in the request and have values
        $validationRules = [];
        foreach ($request->all() as $fieldName => $value) {
            // Skip empty values (empty strings, null, empty arrays)
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }
            
            if (isset($allValidationRules[$fieldName])) {
                $validationRules[$fieldName] = $allValidationRules[$fieldName];
            }
        }
        
        // Always validate entity_type
        $validationRules['entity_type'] = 'required|string|in:' . implode(',', [
            FinancialEntity::TYPE_INDIVIDUAL,
            FinancialEntity::TYPE_TRUST,
            FinancialEntity::TYPE_BUSINESS,
            FinancialEntity::TYPE_ADVISOR,
            FinancialEntity::TYPE_CUSTODIAN,
            FinancialEntity::TYPE_PLAN_ADMINISTRATOR,
            FinancialEntity::TYPE_INSTITUTION,
        ]);
        
        // Add trustee_id if present
        if ($request->has('trustee_id')) {
            $validationRules['trustee_id'] = 'nullable|integer|exists:financial_entities,id';
        }

        $validated = $request->validate($validationRules);

        $entity = $this->entityService->createEntity($validated);

        // If this is a trust and a trustee is selected, create the trustee relationship
        if ($entity->entity_type === FinancialEntity::TYPE_TRUST && !empty($validated['trustee_id'])) {
            $this->entityService->createRelationship(
                $entity->id,
                (int) $validated['trustee_id'],
                EntityRelationship::TYPE_TRUSTEE,
                ['created_via' => 'entity_creation']
            );
        }

        // Give the current user admin permissions for the new entity
        $user = Auth::user();
        \FireflyIII\Models\UserEntityPermission::create([
            'user_id' => $user->id,
            'entity_id' => $entity->id,
            'permission_level' => 'admin',
            'permission_metadata' => [
                'is_creator' => true,
            ],
        ]);

        return redirect()->route('financial-entities.show', $entity)
            ->with('success', 'Financial entity created successfully.');
    }

    /**
     * Display the specified financial entity
     */
    public function show($id): View
    {
        // Find the entity - no permission check needed for basic financial entities
        $financialEntity = FinancialEntity::find($id);
        
        if (!$financialEntity) {
            abort(404, 'Financial entity not found.');
        }
        
        $relationships = $this->entityService->getEntityRelationships($financialEntity);
        $accounts = $this->entityService->getEntityAccounts($financialEntity);
        $statistics = $this->entityService->getEntityStatistics($financialEntity);

        return view('financial-entities.show', compact('financialEntity', 'relationships', 'accounts', 'statistics'));
    }

    /**
     * Show the form for editing the specified financial entity
     */
    public function edit($id): View
    {
        // Find the entity - no permission check needed for basic financial entities
        $financialEntity = FinancialEntity::find($id);
        
        if (!$financialEntity) {
            abort(404, 'Financial entity not found or you do not have permission to edit it.');
        }
        
        $entityTypes = [
            FinancialEntity::TYPE_INDIVIDUAL => 'Individual',
            FinancialEntity::TYPE_TRUST => 'Trust',
            FinancialEntity::TYPE_BUSINESS => 'Business',
            FinancialEntity::TYPE_ADVISOR => 'Financial Advisor',
            FinancialEntity::TYPE_CUSTODIAN => 'Custodian',
            FinancialEntity::TYPE_INSTITUTION => 'Institution',
        ];

        // Get all entities that can be trustees (individuals, businesses, advisors)
        // Only include entities that the user has permission to see
        $trusteeOptions = FinancialEntity::active()
            ->whereIn('entity_type', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
            ])
            ->where('id', '!=', $financialEntity->id) // Exclude the current entity
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'entity_type']);

        // Get current trustee if this is a trust
        $currentTrustee = null;
        if ($financialEntity->entity_type === FinancialEntity::TYPE_TRUST) {
            $trusteeRelationship = $financialEntity->trustees()->first();
            $currentTrustee = $trusteeRelationship ? $trusteeRelationship->relatedEntity : null;
        }

        return view('financial-entities.edit', compact('financialEntity', 'entityTypes', 'trusteeOptions', 'currentTrustee'));
    }

    /**
     * Show the modal form for editing the specified financial entity
     */
    public function editModal($id): View
    {
        // Find the entity - no permission check needed for basic financial entities
        $financialEntity = FinancialEntity::find($id);
        
        if (!$financialEntity) {
            abort(404, 'Financial entity not found or you do not have permission to edit it.');
        }
        
        // Share the financialEntity globally for breadcrumbs
        app('view')->share('financialEntity', $financialEntity);
        
        $entityTypes = [
            FinancialEntity::TYPE_INDIVIDUAL => 'Individual',
            FinancialEntity::TYPE_TRUST => 'Trust',
            FinancialEntity::TYPE_BUSINESS => 'Business',
            FinancialEntity::TYPE_ADVISOR => 'Financial Advisor',
            FinancialEntity::TYPE_CUSTODIAN => 'Custodian',
            FinancialEntity::TYPE_INSTITUTION => 'Institution',
        ];

        // Get all entities that can be trustees (individuals, businesses, advisors)
        // Only include entities that the user has permission to see
        $trusteeOptions = FinancialEntity::active()
            ->whereIn('entity_type', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
            ])
            ->where('id', '!=', $financialEntity->id) // Exclude the current entity
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'entity_type']);

        // Get current trustee if this is a trust
        $currentTrustee = null;
        if ($financialEntity->entity_type === FinancialEntity::TYPE_TRUST) {
            $trusteeRelationship = $financialEntity->trustees()->first();
            $currentTrustee = $trusteeRelationship ? $trusteeRelationship->relatedEntity : null;
        }

        $isCreate = false;

        // Get field definitions for all entity types
        $fieldDefinitions = app(\FireflyIII\Services\FieldDefinitionService::class);
        
        // Map database fields back to form field names for editing
        $mappedEntityData = $this->entityService->mapFieldsFromDatabase($financialEntity);

        return view('financial-entities.edit-modal', compact('financialEntity', 'entityTypes', 'trusteeOptions', 'currentTrustee', 'isCreate', 'fieldDefinitions', 'mappedEntityData'));
    }

    /**
     * Get fields for a specific entity type (AJAX endpoint)
     */
    public function getFieldsForEntityType(Request $request): JsonResponse
    {
        $entityType = $request->input('entity_type');

        if (!$entityType) {
            return response()->json(['error' => 'Entity type is required'], 400);
        }

        $fieldDefinitions = app(\FireflyIII\Services\FieldDefinitionService::class);
        $fields = $fieldDefinitions->getFieldsWithTranslations($entityType);

        return response()->json([
            'fields' => $fields,
            'entity_type' => $entityType
        ]);
    }

    /**
     * Show the new centralized modal template
     *
     * @return View
     */
    public function editModalNew()
    {
        return view('financial-entities.edit-modal-new');
    }

    /**
     * Get all financial entity field definitions for the shared modal
     */
    public function getAllEntityFields(): JsonResponse
    {
        try {
            $fieldDefinitions = app(\FireflyIII\Services\FieldDefinitionService::class);
            
            // Get fields for all entity types
            $allFields = [];
            $entityTypes = [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_TRUST,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
                FinancialEntity::TYPE_CUSTODIAN,
                FinancialEntity::TYPE_INSTITUTION,
            ];
            
            foreach ($entityTypes as $entityType) {
                $fields = $fieldDefinitions->getFieldsWithTranslations($entityType);
                $allFields = array_merge($allFields, $fields);
            }
            
            // Remove duplicates based on field name
            $uniqueFields = [];
            foreach ($allFields as $fieldName => $fieldData) {
                if (!isset($uniqueFields[$fieldName])) {
                    $uniqueFields[$fieldName] = $fieldData;
                }
            }
            
            return response()->json([
                'success' => true,
                'fields' => $uniqueFields
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading financial entity fields: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading field definitions'], 500);
        }
    }

    /**
     * Get financial entity data for the shared modal
     */
    public function getEntityData($id): JsonResponse
    {
        // Find the entity - no permission check needed for basic financial entities
        $financialEntity = FinancialEntity::find($id);
        
        if (!$financialEntity) {
            return response()->json(['success' => false, 'message' => 'Financial entity not found'], 404);
        }

        try {
            // Start with basic entity attributes
            $entityData = $financialEntity->toArray();
            
            // Add any computed fields
            $entityData['entity_status'] = $financialEntity->is_active ? 'active' : 'inactive';
            
            // Parse metadata if it exists
            if ($financialEntity->metadata) {
                $metadata = is_string($financialEntity->metadata) ? 
                    json_decode($financialEntity->metadata, true) : 
                    $financialEntity->metadata;
                
                if ($metadata && is_array($metadata)) {
                    $entityData = array_merge($entityData, $metadata);
                }
            }

            return response()->json([
                'success' => true,
                'entity' => $entityData
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading financial entity data: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading entity data'], 500);
        }
    }

    /**
     * Get financial entities for beneficiaries dropdown (excluding institutions and selected trustee)
     */
    public function getBeneficiaryEntities(Request $request): JsonResponse
    {
        $excludeIds = [];
        $excludeTypes = $request->get('exclude_types', []);
        
        // Convert comma-separated string to array if needed
        if (is_string($excludeTypes)) {
            $excludeTypes = explode(',', $excludeTypes);
        }
        
        // Exclude institutions by default
        if (!in_array('institution', $excludeTypes)) {
            $excludeTypes[] = 'institution';
        }
        
        // Exclude selected trustee if provided
        if ($request->has('exclude_trustee_id') && $request->exclude_trustee_id) {
            $excludeIds[] = $request->exclude_trustee_id;
        }
        
        // Remove empty values
        $excludeIds = array_filter($excludeIds);
        
        $query = FinancialEntity::query();
        
        // Exclude specified entity types
        if (!empty($excludeTypes)) {
            $query->whereNotIn('entity_type', $excludeTypes);
        }
        
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        
        $entities = $query->orderBy('name')
            ->get(['id', 'name', 'display_name', 'entity_type']);

        return response()->json($entities);
    }

    /**
     * Update the specified financial entity
     */
    public function update(Request $request, $id)
    {
        // Find the entity - no permission check needed for basic financial entities
        $financialEntity = FinancialEntity::find($id);
        
        if (!$financialEntity) {
            abort(404, 'Financial entity not found or you do not have permission to update it.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|in:' . implode(',', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_TRUST,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
                FinancialEntity::TYPE_CUSTODIAN,
                FinancialEntity::TYPE_INSTITUTION,
            ]),
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'contact_info' => 'nullable|array',
            'metadata' => 'nullable|array',
            'trustee_id' => 'nullable|integer|exists:financial_entities,id',
        ]);

        $financialEntity->update($validated);

        // Handle trustee relationship for trusts
        if ($financialEntity->entity_type === FinancialEntity::TYPE_TRUST) {
            // Remove existing trustee relationships
            $financialEntity->trustees()->delete();
            
            // Create new trustee relationship if trustee is selected
            if (!empty($validated['trustee_id'])) {
                $this->entityService->createRelationship(
                    $financialEntity->id,
                    (int) $validated['trustee_id'],
                    EntityRelationship::TYPE_TRUSTEE,
                    ['updated_via' => 'entity_edit']
                );
            }
        } else {
            // If entity type changed from trust to something else, remove trustee relationships
            $financialEntity->trustees()->delete();
        }

        return redirect()->route('financial-entities.show', $financialEntity)
            ->with('success', 'Financial entity updated successfully.');
    }

    /**
     * Update the specified financial entity via modal
     */
    public function updateModal(Request $request, $id): JsonResponse
    {
        
        // Find the entity - no permission check needed for basic financial entities
        $financialEntity = FinancialEntity::find($id);
        
        if (!$financialEntity) {
            return response()->json([
                'success' => false,
                'message' => 'Financial entity not found or you do not have permission to update it.'
            ], 404);
        }
        
        // Validate entity data
        $validationResult = $this->validateEntityData($request);
        
        if (!$validationResult['success']) {
            return response()->json($validationResult, 422);
        }
        
        $validated = $validationResult['data'];

        $this->entityService->updateEntity($financialEntity, $validated);

        // Handle trustee relationship for trusts
        if ($financialEntity->entity_type === FinancialEntity::TYPE_TRUST) {
            // Remove existing trustee relationships
            $financialEntity->trustees()->delete();
            
            // Create new trustee relationship if trustee is selected
            if (!empty($validated['trustee_id'])) {
                $this->entityService->createRelationship(
                    $financialEntity->id,
                    (int) $validated['trustee_id'],
                    EntityRelationship::TYPE_TRUSTEE,
                    ['updated_via' => 'entity_edit']
                );
            }
        } else {
            // If entity type changed from trust to something else, remove trustee relationships
            $financialEntity->trustees()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Financial entity updated successfully.',
            'entity' => [
                'id' => $financialEntity->id,
                'name' => $financialEntity->name,
                'display_name' => $financialEntity->display_name,
                'entity_type' => $financialEntity->entity_type,
                'description' => $financialEntity->description,
                'contact_info' => $financialEntity->contact_info,
            ]
        ]);
    }

    /**
     * Remove the specified financial entity
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            // Use the same method as the index to get user-manageable entities
            $userManageableEntities = $this->userEntityService->getUserManageableEntities($user);
            $financialEntity = $userManageableEntities->find($id);

            if (!$financialEntity) {
                return redirect()->route('financial-entities.index')
                    ->with('error', 'Financial entity not found or you do not have permission to delete it.');
            }

            // Check if entity has any accounts - use direct database queries for more reliable checking
            $accountHolderCount = \FireflyIII\Models\Account::whereRaw('account_holder_ids::text LIKE ?', ['%"' . $financialEntity->id . '"%'])->count();
            $institutionCount = \FireflyIII\Models\Account::where('institution_id', $financialEntity->id)->count();
            
            if ($accountHolderCount > 0 || $institutionCount > 0) {
                return redirect()->route('financial-entities.index')
                    ->with('error', "Cannot delete '{$financialEntity->display_name}' - it is referenced by {$accountHolderCount} account(s) as account holder and {$institutionCount} account(s) as institution. Please remove or reassign accounts first.");
            }

            // Get entity name before deletion
            $entityName = $financialEntity->display_name ?: $financialEntity->name;
            
            // Delete related records first to avoid foreign key constraints
            $financialEntity->accountRoles()->delete();
            $financialEntity->relationships()->delete();
            $financialEntity->relatedRelationships()->delete();
            $financialEntity->notes()->delete();
            
            // Remove user permissions
            $financialEntity->users()->detach();
            
            // Finally delete the entity
            $deleted = $financialEntity->delete();
            
            if (!$deleted) {
                return redirect()->route('financial-entities.index')
                    ->with('error', "Failed to delete financial entity '{$entityName}'. Please try again.");
            }

            return redirect()->route('financial-entities.index')
                ->with('success', "Financial entity '{$entityName}' has been deleted successfully.");
                
        } catch (\Exception $e) {
            Log::error('Failed to delete financial entity: ' . $e->getMessage());
            return redirect()->route('financial-entities.index')
                ->with('error', 'An error occurred while deleting the financial entity. Please try again.');
        }
    }

    /**
     * Get entities for autocomplete
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->get('query', '');
        $type = $request->get('type');

        $entities = FinancialEntity::active()
            ->when($type, function ($q) use ($type) {
                return $q->where('entity_type', $type);
            })
            ->when($query, function ($q) use ($query) {
                return $q->where('name', 'ILIKE', "%{$query}%")
                        ->orWhere('display_name', 'ILIKE', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'display_name', 'entity_type']);

        return response()->json($entities);
    }

    /**
     * Bulk delete multiple financial entities
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $user = Auth::user();
            $entityIds = $request->input('entity_ids', []);
            
            if (empty($entityIds) || !is_array($entityIds)) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'No entities selected for deletion.'], 400);
                }
                return redirect()->route('financial-entities.index')
                    ->with('error', 'No entities selected for deletion.');
            }
            
            // Validate that all entities exist and user has permission
            // Use the same method as the index to get user-manageable entities
            $userManageableEntities = $this->userEntityService->getUserManageableEntities($user);
            $entities = $userManageableEntities->whereIn('id', $entityIds);
            
            if ($entities->count() !== count($entityIds)) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Some selected entities were not found or you do not have permission to delete them.'], 403);
                }
                return redirect()->route('financial-entities.index')
                    ->with('error', 'Some selected entities were not found or you do not have permission to delete them.');
            }
            
            $deletedCount = 0;
            $errors = [];
            
            foreach ($entities as $entity) {
                try {
                    // Check if entity has any accounts - use direct database queries for more reliable checking
                    $accountHolderCount = \FireflyIII\Models\Account::whereRaw('account_holder_ids::text LIKE ?', ['%"' . $entity->id . '"%'])->count();
                    $institutionCount = \FireflyIII\Models\Account::where('institution_id', $entity->id)->count();
                    
                    if ($accountHolderCount > 0 || $institutionCount > 0) {
                        $errors[] = "Cannot delete '{$entity->display_name}' - it is referenced by {$accountHolderCount} account(s) as account holder and {$institutionCount} account(s) as institution.";
                        continue;
                    }
                    
                    // Delete related records first to avoid foreign key constraints
                    $entity->accountRoles()->delete();
                    $entity->relationships()->delete();
                    $entity->relatedRelationships()->delete();
                    $entity->notes()->delete();
                    
                    // Remove user permissions
                    $entity->users()->detach();
                    
                    // Finally delete the entity
                    if ($entity->delete()) {
                        $deletedCount++;
                    } else {
                        $errors[] = "Failed to delete '{$entity->display_name}'.";
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Failed to delete financial entity {$entity->id}: " . $e->getMessage());
                    $errors[] = "Error deleting '{$entity->display_name}': " . $e->getMessage();
                }
            }
            
            // Prepare response message
            $message = "Successfully deleted {$deletedCount} financial entit" . ($deletedCount === 1 ? 'y' : 'ies') . ".";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(' ', $errors);
            }
            
            $messageType = empty($errors) ? 'success' : 'warning';
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => empty($errors),
                    'message' => $message,
                    'deleted_count' => $deletedCount,
                    'errors' => $errors
                ]);
            }
            
            return redirect()->route('financial-entities.index')
                ->with($messageType, $message);
                
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete financial entities: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An error occurred while deleting the financial entities. Please try again.'], 500);
            }
            
            return redirect()->route('financial-entities.index')
                ->with('error', 'An error occurred while deleting the financial entities. Please try again.');
        }
    }

    /**
     * Get entity relationships
     */
    public function relationships(FinancialEntity $financialEntity): JsonResponse
    {
        $relationships = $this->entityService->getEntityRelationships($financialEntity);
        return response()->json($relationships);
    }

    /**
     * Get entity accounts
     */
    public function accounts(FinancialEntity $financialEntity): JsonResponse
    {
        $accounts = $this->entityService->getEntityAccounts($financialEntity);
        return response()->json($accounts);
    }

    /**
     * Get entity statistics
     */
    public function statistics(FinancialEntity $financialEntity): JsonResponse
    {
        $statistics = $this->entityService->getEntityStatistics($financialEntity);
        return response()->json($statistics);
    }

    /**
     * Validate entity data based on entity type and field definitions
     */
    private function validateEntityData(Request $request): array
    {
        // First validate the entity type
        $entityType = $request->validate([
            'entity_type' => 'required|string|in:' . implode(',', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_TRUST,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
                FinancialEntity::TYPE_CUSTODIAN,
                FinancialEntity::TYPE_PLAN_ADMINISTRATOR,
                FinancialEntity::TYPE_INSTITUTION,
            ])
        ])['entity_type'];

        // Get field definitions for this entity type
        $fieldDefinitions = app(\FireflyIII\Services\FieldDefinitionService::class);
        $allValidationRules = $fieldDefinitions->getValidationRules($entityType);
        
        // Only validate fields that are actually present in the request and have values
        $validationRules = [];
        foreach ($request->all() as $fieldName => $value) {
            // Skip empty values (empty strings, null, empty arrays)
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }
            
            if (isset($allValidationRules[$fieldName])) {
                $validationRules[$fieldName] = $allValidationRules[$fieldName];
            }
        }
        
        // Always validate entity_type
        $validationRules['entity_type'] = 'required|string|in:' . implode(',', [
            FinancialEntity::TYPE_INDIVIDUAL,
            FinancialEntity::TYPE_TRUST,
            FinancialEntity::TYPE_BUSINESS,
            FinancialEntity::TYPE_ADVISOR,
            FinancialEntity::TYPE_CUSTODIAN,
            FinancialEntity::TYPE_PLAN_ADMINISTRATOR,
            FinancialEntity::TYPE_INSTITUTION,
        ]);
        
        // Add trustee_id if present
        if ($request->has('trustee_id')) {
            $validationRules['trustee_id'] = 'nullable|integer|exists:financial_entities,id';
        }

        try {
            $validated = $request->validate($validationRules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
        }

        // Custom validation: trustee_id is required when entity_type is 'trust'
        if ($validated['entity_type'] === FinancialEntity::TYPE_TRUST && empty($validated['trustee_id'])) {
            return [
                'success' => false,
                'message' => 'A trustee must be selected for trust entities.',
                'errors' => ['trustee_id' => ['A trustee must be selected for trust entities.']]
            ];
        }

        // Return ALL request data, not just validated fields
        $allData = $request->all();
        // Remove Laravel-specific fields
        unset($allData['_token'], $allData['_method']);

        return [
            'success' => true,
            'data' => $allData
        ];
    }

    /**
     * Show the entity import page
     */
    public function importJson(): View
    {
        return view('entities.import-json', [
            'subTitle' => 'Import Financial Entities from JSON'
        ]);
    }

}
