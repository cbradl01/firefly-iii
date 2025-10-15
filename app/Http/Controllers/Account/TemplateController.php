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
use FireflyIII\Models\AccountTemplate;
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
        $query = AccountTemplate::active()->with(['accountType.category', 'accountType.behavior']);
        
        // Search functionality
        $search = $request->get('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }
        
        // Filter by account type
        $accountType = $request->get('account_type');
        if ($accountType) {
            $query->whereHas('accountType', function($q) use ($accountType) {
                $q->where('name', $accountType);
            });
        }
        
        // Filter by category
        $category = $request->get('category');
        if ($category) {
            $query->whereHas('accountType.category', function($q) use ($category) {
                $q->where('name', $category);
            });
        }
        
        // Sort options
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        
        switch ($sort) {
            case 'category':
                $query->join('account_types', 'account_templates.account_type_id', '=', 'account_types.id')
                      ->join('account_categories', 'account_types.category_id', '=', 'account_categories.id')
                      ->orderBy('account_categories.name', $direction)
                      ->orderBy('account_templates.name', 'asc');
                break;
            case 'type':
                $query->join('account_types', 'account_templates.account_type_id', '=', 'account_types.id')
                      ->orderBy('account_types.name', $direction)
                      ->orderBy('account_templates.name', 'asc');
                break;
            default:
                $query->orderBy('name', $direction);
        }
        
        $templates = $query->get();
        
        // Group by category for display
        $templatesByCategory = $templates->groupBy('accountType.category.name');
        
        // Get filter options
        $accountTypes = AccountTemplate::active()
            ->with('accountType')
            ->get()
            ->pluck('accountType.name')
            ->unique()
            ->sort()
            ->values();
            
        $categories = AccountCategory::orderBy('name')->get();

        return view('accounts.templates.index', compact(
            'templatesByCategory', 
            'templates', 
            'search', 
            'accountType', 
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
        
        $template = AccountTemplate::where('name', $decodedTemplateName)
            ->where('active', true)
            ->with(['accountType.category', 'accountType.behavior'])
            ->firstOrFail();

        // Get suggested fields from template
        $suggestedFields = $template->suggested_fields ?? [];
        
        // Get required fields from template's field_requirements
        $requiredFields = [];
        if ($template->field_requirements) {
            foreach ($template->field_requirements as $field => $requirements) {
                if (isset($requirements['required']) && $requirements['required'] === true) {
                    $requiredFields[] = $field;
                }
            }
        }
        
        // Get metadata preset from template
        $metadataPreset = $template->metadata_preset ?? [];

        // Get user's default currency
        $defaultCurrency = Amount::getPrimaryCurrency();
        
        // Get all currencies for the dropdown
        $allCurrencies = TransactionCurrency::orderBy('code', 'ASC')->get();
        
        // Get current user's email (used as name)
        $userName = Auth::user()->email ?? '';
        
        // Get field definitions from config for all suggested fields
        $fieldDefinitions = [];
        foreach ($suggestedFields as $field) {
            $fieldDefinitions[$field] = config("account_fields.{$field}");
        }

        return view('accounts.templates.create', compact(
            'template', 
            'suggestedFields', 
            'requiredFields',
            'metadataPreset', 
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
        $template = AccountTemplate::where('name', $templateName)
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
        $data['account_type_id'] = $template->account_type_id;

        // Create the account using AccountFactory
        $accountFactory = app(AccountFactory::class);
        $accountFactory->setUser(Auth::user());
        $account = $accountFactory->create($data);

        // Flash success message and redirect
        session()->flash('success', 'Account "' . $account->name . '" created successfully from template "' . $templateName . '"');
        
        return redirect()->route('accounts.show', $account->id);
    }
}