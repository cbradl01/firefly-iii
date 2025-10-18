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
     * Store the new account from template
     */
    public function store(Request $request)
    {
        // Get the template
        $templateName = $request->input('template');
        $template = AccountType::where('name', $templateName)
            ->where('active', true)
            ->firstOrFail();

        // Validate the request
        $request->validate([
            'template' => 'required|string',
            'name' => 'required|string|max:255',
            'currency_id' => 'required|integer|exists:transaction_currencies,id',
            'institution' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'active' => 'boolean',
            'virtual_balance' => 'nullable|numeric',
            'iban' => 'nullable|string|max:255',
        ], [
            'institution.required' => 'The institution field is required.',
            'owner.required' => 'The owner field is required.',
            'product_name.required' => 'The product name field is required.',
        ]);

        // Get the validated data
        $data = $request->only([
            'name', 'currency_id', 'institution', 'owner', 'product_name', 
            'active', 'virtual_balance', 'iban'
        ]);
        
        $data['template'] = $templateName;
        $data['category_id'] = $template->category_id;
        $data['behavior_id'] = $template->behavior_id;

        // Create the account using AccountFactory
        $accountFactory = app(AccountFactory::class);
        $accountFactory->setUser(Auth::user());
        $account = $accountFactory->create($data);

        // Flash success message and redirect
        session()->flash('success', 'Account "' . $account->name . '" created successfully from template "' . $templateName . '"');
        
        return redirect()->route('accounts.show', $account->id);
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
                'category' => $fieldData['category'],
                'description' => $fieldData['description'],
                'validation_rules' => json_encode(['required' => $fieldData['required'] ?? false]),
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