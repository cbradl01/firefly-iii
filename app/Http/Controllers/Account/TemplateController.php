<?php

/**
 * TemplateController.php
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

namespace FireflyIII\Http\Controllers\Account;

use FireflyIII\Factory\AccountFactory;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\TemplateAccountFormRequest;
use FireflyIII\Models\AccountCategory;
use FireflyIII\Models\AccountType;
use FireflyIII\FieldDefinitions\FieldDefinitions;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TemplateController extends Controller
{
    /**
     * Show the account template selection page
     */
    public function index(Request $request): View
    {
        $query = AccountType::where('active', true)->with(['category', 'behavior']);
        
        // Search functionality
        $search = $request->get('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }
        
        // Filter by behavior (replaces account_type filter)
        $behavior = $request->get('account_type');
        if ($behavior) {
            $query->whereHas('behavior', function($q) use ($behavior) {
                $q->where('name', $behavior);
            });
        }
        
        // Filter by category
        $category = $request->get('category');
        if ($category) {
            $query->whereHas('category', function($q) use ($category) {
                $q->where('name', $category);
            });
        }
        
        // Sort options
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        
        switch ($sort) {
            case 'category':
                $query->join('account_categories', 'account_types.category_id', '=', 'account_categories.id')
                      ->orderBy('account_categories.name', $direction)
                      ->orderBy('account_types.name', 'asc');
                break;
            case 'type':
                $query->join('account_behaviors', 'account_types.behavior_id', '=', 'account_behaviors.id')
                      ->orderBy('account_behaviors.name', $direction)
                      ->orderBy('account_types.name', 'asc');
                break;
            default:
                $query->orderBy('name', $direction);
        }
        
        $templates = $query->get();
        
        // Group by category for display
        $templatesByCategory = $templates->groupBy('category.name');
        
        // Get filter options
        $accountTypes = AccountType::where('active', true)
            ->with('behavior')
            ->get()
            ->pluck('behavior.name')
            ->unique()
            ->sort()
            ->values();
            
        $categories = AccountCategory::orderBy('name')->get();

        return view('accounts.templates.index', compact(
            'templatesByCategory', 
            'templates', 
            'search', 
            'behavior', 
            'category', 
            'sort', 
            'direction',
            'accountTypes',
            'categories'
        ));
    }

    /**
     * Show the form to create an account from a template
     */
    public function create(Request $request, string $templateName): View
    {
        // URL decode the template name to handle spaces and special characters
        $decodedTemplateName = urldecode($templateName);
        
        $template = AccountType::where('name', $decodedTemplateName)
            ->where('active', true)
            ->with(['category', 'behavior'])
            ->firstOrFail();

        // Get field configuration from template's firefly_mapping
        $fireflyMapping = $template->firefly_mapping ?? [];
        $accountFields = $fireflyMapping['account_fields'] ?? [];
        
        // Get required and optional fields
        $requiredFields = [];
        $optionalFields = [];
        foreach ($accountFields as $fieldName => $config) {
            if (isset($config['required']) && $config['required'] === true) {
                $requiredFields[] = $fieldName;
            } else {
                $optionalFields[] = $fieldName;
            }
        }

        // Get user's default currency
        $defaultCurrency = Amount::getPrimaryCurrency();
        
        // Get all currencies for the dropdown
        $allCurrencies = TransactionCurrency::orderBy('code', 'ASC')->get();
        
        // Get current user's email (used as name)
        $userName = Auth::user()->email ?? '';
        
        // Get field definitions from config for all fields
        $fieldDefinitions = [];
        foreach (array_merge($requiredFields, $optionalFields) as $field) {
            $fieldDefinitions[$field] = config("account_fields.{$field}");
        }

        return view('accounts.templates.create', compact(
            'template', 
            'requiredFields',
            'optionalFields',
            'accountFields',
            'defaultCurrency', 
            'allCurrencies', 
            'userName', 
            'fieldDefinitions'
        ));
    }

    /**
     * Show the create account modal
     */
    public function createModal(Request $request, string $templateName): View
    {
        // URL decode the template name to handle spaces and special characters
        $decodedTemplateName = urldecode($templateName);
        
        $template = AccountType::where('name', $decodedTemplateName)
            ->where('active', true)
            ->with(['category', 'behavior'])
            ->firstOrFail();

        // Get user's default currency
        $defaultCurrency = Amount::getPrimaryCurrency();
        
        // Get current user's email (used as name)
        $userName = Auth::user()->email ?? '';

        // Create default account data based on template
        $accountData = [
            'id' => null, // New account
            'name' => '',
            'account_type_id' => $template->id,
            'account_type_name' => $template->name,
            'account_type_fields' => $template->firefly_mapping['account_fields'] ?? [],
            'account_category' => $template->category->name ?? '',
            'account_behavior' => $template->behavior->name ?? '',
            'currency_id' => $defaultCurrency->id,
            'currency_code' => $defaultCurrency->code,
            'account_holder' => $userName,
            'institution' => '',
            'account_status' => 'active',
            'description' => '',
            'account_number' => '',
            'opening_date' => date('Y-m-d'),
            'closing_date' => null,
            'notes' => '',
            'current_balance' => '0.00',
            'online_banking' => false,
            'mobile_banking' => false,
            'wire_transfer' => false,
            'monthly_fee' => '0.00',
            'transaction_fee' => '0.00',
            'routing_number' => '',
            'metadata' => []
        ];

        return view('accounts.account-modal', [
            'modalId' => 'createAccountModal',
            'modalTitle' => 'Create Account',
            'formId' => 'createAccountForm',
            'entityId' => null,
            'dataEndpoint' => '/accounts/create-data/' . urlencode($template->name),
            'updateEndpoint' => '/accounts/templates/store',
            'specialFields' => [
                'template' => $template->name,
                'account_type_id' => $template->id
            ],
            'accountData' => $accountData
        ]);
    }


    /**
     * Store the new account from template
     */
    public function store(Request $request)
    {
        // Get the template
        $templateName = $request->input('template');
        $template = AccountType::where('name', $templateName)
            ->where('active', true)
            ->firstOrFail();

        // Clean up the request data before validation
        $this->cleanRequestData($request);

        // Get all account fields from FieldDefinitions - single source of truth
        $accountFields = Account::getAccountFields();
        $fieldNames = array_keys($accountFields);
        
        // Get form data for all account fields
        $data = $request->only($fieldNames);
        
        // Add system fields that come from the template (not user input)
        $data['template'] = $templateName;
        $data['category_id'] = $template->category_id;
        $data['behavior_id'] = $template->behavior_id;
        
        // Add fallback for currency_id if not provided
        if (empty($data['currency_id'])) {
            $data['currency_id'] = Amount::getPrimaryCurrency()->id;
        }


        try {
            // Create the account using AccountFactory
            $accountFactory = app(AccountFactory::class);
            $accountFactory->setUser(Auth::user());
            $account = $accountFactory->create($data);

            // Return JSON response for AJAX requests
            if ($request->ajax()) {
                // Flash success message for the redirect
                session()->flash('success', 'Account "' . $account->name . '" created successfully from template "' . $templateName . '"');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Account "' . $account->name . '" created successfully from template "' . $templateName . '"',
                    'account' => [
                        'id' => $account->id,
                        'name' => $account->name
                    ]
                ]);
            }

            // Flash success message and redirect for non-AJAX requests
            session()->flash('success', 'Account "' . $account->name . '" created successfully from template "' . $templateName . '"');
            return redirect()->route('accounts.show', $account->id);
            
        } catch (\Exception $e) {
            // Handle errors
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating account: ' . $e->getMessage(),
                    'errors' => []
                ], 422);
            }
            
            return redirect()->back()->withErrors(['error' => 'Error creating account: ' . $e->getMessage()]);
        }
    }

    /**
     * Clean request data using FieldDefinitions hierarchical defaults
     */
    private function cleanRequestData(Request $request): void
    {
        // Get template-specific overrides if available
        $templateName = $request->input('template');
        $templateOverrides = null;
        
        if ($templateName) {
            $template = AccountType::where('name', $templateName)->first();
            if ($template && $template->firefly_mapping) {
                $templateOverrides = $template->firefly_mapping['account_fields'] ?? null;
            }
        }
        
        $accountFields = Account::getAccountFields();
        
        foreach ($accountFields as $fieldName => $fieldConfig) {
            if ($request->has($fieldName)) {
                $value = $request->input($fieldName);
                
                // Handle empty values using hierarchical defaults
                if (empty($value) || trim($value) === '' || $value === 'mm/dd/yyyy') {
                    $defaultValue = FieldDefinitions::getFieldDefault($fieldName, 'account', $templateOverrides);
                    $request->merge([$fieldName => $defaultValue]);
                }
            }
        }
    }

    /**
     * Get field definitions for the edit modal
     */
    public function getFieldDefinitions()
    {
        $fields = FieldDefinitions::getFieldsWithTranslations('account');
        
        // Convert to the format expected by the frontend
        $formattedFields = [];
        foreach ($fields as $fieldName => $fieldData) {
            $formattedFields[] = [
                'field_name' => $fieldName,
                'display_name' => $fieldData['display_name'],
                'data_type' => $fieldData['data_type'],
                'input_type' => $fieldData['input_type'] ?? 'text',
                'category' => $fieldData['category'],
                'description' => $fieldData['description'],
                'validation_rules' => json_encode(['required' => $fieldData['required'] ?? false]),
                'options' => $fieldData['options'] ?? [],
                'is_system_field' => true,
                'target_type' => 'account'
            ];
        }
        
        // Sort by category and display name
        usort($formattedFields, function($a, $b) {
            if ($a['category'] === $b['category']) {
                return strcmp($a['display_name'], $b['display_name']);
            }
            return strcmp($a['category'], $b['category']);
        });

        return response()->json($formattedFields);
    }

    /**
     * Get template data for editing
     */
    public function edit($id)
    {
        $template = AccountType::where('id', $id)
            ->where('active', true)
            ->firstOrFail();

        // Get account_fields from firefly_mapping
        $fireflyMapping = $template->firefly_mapping ?? [];
        $accountFields = $fireflyMapping['account_fields'] ?? [];

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'account_fields' => $accountFields
        ]);
    }

    /**
     * Update template
     */
    public function update(Request $request, $id)
    {
        $template = AccountType::where('id', $id)
            ->where('active', true)
            ->firstOrFail();
            
        // Reload the model to ensure we have the latest data
        $template->refresh();

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'account_fields' => 'nullable|array'
        ]);

        // Update basic fields
        $template->name = $request->input('name');
        $template->description = $request->input('description');

        // Update firefly_mapping with new account_fields structure
        $fireflyMapping = $template->firefly_mapping ?? [];
        
        $fireflyMapping['account_fields'] = $request->input('account_fields', []);
        $template->firefly_mapping = $fireflyMapping;
        $template->save();

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully'
        ]);
    }
}