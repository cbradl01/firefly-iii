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
    public function createModal(): View
    {
        $entityTypes = [
            FinancialEntity::TYPE_INDIVIDUAL => 'Individual',
            FinancialEntity::TYPE_TRUST => 'Trust',
            FinancialEntity::TYPE_BUSINESS => 'Business',
            FinancialEntity::TYPE_ADVISOR => 'Financial Advisor',
            FinancialEntity::TYPE_CUSTODIAN => 'Custodian',
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
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'entity_type']);

        // Create a dummy entity for the form (empty values)
        $financialEntity = new FinancialEntity();
        $currentTrustee = null;
        $isCreate = true;

        return view('financial-entities.edit-modal', compact('entityTypes', 'trusteeOptions', 'financialEntity', 'currentTrustee', 'isCreate'));
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
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'entity_type']);

        return view('financial-entities.create', compact('entityTypes', 'trusteeOptions'));
    }

    /**
     * Store a newly created financial entity via modal
     */
    public function storeModal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|in:' . implode(',', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_TRUST,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
                FinancialEntity::TYPE_CUSTODIAN,
            ]),
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'contact_info' => 'nullable|array',
            'metadata' => 'nullable|array',
            'trustee_id' => 'nullable|integer|exists:financial_entities,id',
        ]);

        // Custom validation: trustee_id is required when entity_type is 'trust'
        if ($validated['entity_type'] === FinancialEntity::TYPE_TRUST && empty($validated['trustee_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'A trustee must be selected for trust entities.',
                'errors' => ['trustee_id' => ['A trustee must be selected for trust entities.']]
            ], 422);
        }

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|in:' . implode(',', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_TRUST,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
                FinancialEntity::TYPE_CUSTODIAN,
            ]),
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'contact_info' => 'nullable|array',
            'metadata' => 'nullable|array',
            'trustee_id' => 'nullable|integer|exists:financial_entities,id',
        ]);

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
        $user = Auth::user();
        
        // Find the entity and check if user has permission
        $financialEntity = FinancialEntity::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);
        
        if (!$financialEntity) {
            abort(404, 'Financial entity not found or you do not have permission to view it.');
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
        $user = Auth::user();
        
        // Find the entity and check if user has permission
        $financialEntity = FinancialEntity::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);
        
        if (!$financialEntity) {
            abort(404, 'Financial entity not found or you do not have permission to edit it.');
        }
        
        $entityTypes = [
            FinancialEntity::TYPE_INDIVIDUAL => 'Individual',
            FinancialEntity::TYPE_TRUST => 'Trust',
            FinancialEntity::TYPE_BUSINESS => 'Business',
            FinancialEntity::TYPE_ADVISOR => 'Financial Advisor',
            FinancialEntity::TYPE_CUSTODIAN => 'Custodian',
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
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
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
        $user = Auth::user();
        
        // Find the entity and check if user has permission
        $financialEntity = FinancialEntity::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);
        
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
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'entity_type']);

        // Get current trustee if this is a trust
        $currentTrustee = null;
        if ($financialEntity->entity_type === FinancialEntity::TYPE_TRUST) {
            $trusteeRelationship = $financialEntity->trustees()->first();
            $currentTrustee = $trusteeRelationship ? $trusteeRelationship->relatedEntity : null;
        }

        $isCreate = false;

        return view('financial-entities.edit-modal', compact('financialEntity', 'entityTypes', 'trusteeOptions', 'currentTrustee', 'isCreate'));
    }

    /**
     * Update the specified financial entity
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        // Find the entity and check if user has permission
        $financialEntity = FinancialEntity::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);
        
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
        $user = Auth::user();
        
        // Find the entity and check if user has permission
        $financialEntity = FinancialEntity::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);
        
        if (!$financialEntity) {
            return response()->json([
                'success' => false,
                'message' => 'Financial entity not found or you do not have permission to update it.'
            ], 404);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|in:' . implode(',', [
                FinancialEntity::TYPE_INDIVIDUAL,
                FinancialEntity::TYPE_TRUST,
                FinancialEntity::TYPE_BUSINESS,
                FinancialEntity::TYPE_ADVISOR,
                FinancialEntity::TYPE_CUSTODIAN,
            ]),
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'contact_info' => 'nullable|array',
            'metadata' => 'nullable|array',
            'trustee_id' => 'nullable|integer|exists:financial_entities,id',
        ]);

        // Custom validation: trustee_id is required when entity_type is 'trust'
        if ($validated['entity_type'] === FinancialEntity::TYPE_TRUST && empty($validated['trustee_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'A trustee must be selected for trust entities.',
                'errors' => ['trustee_id' => ['A trustee must be selected for trust entities.']]
            ], 422);
        }

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
    public function destroy(FinancialEntity $financialEntity)
    {
        $financialEntity->update(['is_active' => false]);

        return redirect()->route('financial-entities.index')
            ->with('success', 'Financial entity deactivated successfully.');
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
}
