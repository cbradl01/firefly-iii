<?php

/**
 * EditController.php
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

namespace FireflyIII\Http\Controllers\Account;

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\Location;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use Illuminate\Support\Facades\Auth;
use FireflyIII\Support\Amount;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
{
    use ModelInformation;

    private AttachmentHelperInterface  $attachments;
    private AccountRepositoryInterface $repository;

    /**
     * EditController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string) trans('firefly.accounts'));

                $this->repository  = app(AccountRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit account overview. It's complex, but it just has a lot of if/then/else.
     *
     * @SuppressWarnings("PHPMD.NPathComplexity")
     *
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function edit(Request $request, Account $account, AccountRepositoryInterface $repository)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }

        // Check if account has a template - if so, use template form
        if ($account->template_id) {
            return $this->editFromTemplate($request, $account, $repository);
        }

        // Get the category name from the new account classification system
        $categoryName = $account->accountType->category->name;
        $objectType = strtolower($categoryName);
        $subTitle             = (string) trans(sprintf('firefly.edit_%s_account', $objectType), ['name' => $account->name]);
        $subTitleIcon         = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));
        $roles                = $this->getRoles();
        $liabilityTypes       = $this->getLiabilityTypes();
        $location             = $repository->getLocation($account);
        $latitude             = $location instanceof Location ? $location->latitude : config('firefly.default_location.latitude');
        $longitude            = $location instanceof Location ? $location->longitude : config('firefly.default_location.longitude');
        $zoomLevel            = $location instanceof Location ? $location->zoom_level : config('firefly.default_location.zoom_level');
        $canEditCurrency      = 0 === $account->piggyBanks()->count();
        $hasLocation          = $location instanceof Location;
        $locations            = [
            'location' => [
                'latitude'     => old('location_latitude') ?? $latitude,
                'longitude'    => old('location_longitude') ?? $longitude,
                'zoom_level'   => old('location_zoom_level') ?? $zoomLevel,
                'has_location' => $hasLocation || 'true' === old('location_has_location'),
            ],
        ];

        $liabilityDirections  = [
            'debit'  => trans('firefly.liability_direction_debit'),
            'credit' => trans('firefly.liability_direction_credit'),
        ];

        // interest calculation periods:
        $interestPeriods      = [
            'daily'   => (string) trans('firefly.interest_calc_daily'),
            'monthly' => (string) trans('firefly.interest_calc_monthly'),
            'yearly'  => (string) trans('firefly.interest_calc_yearly'),
        ];

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('accounts.edit.fromUpdate')) {
            $this->rememberPreviousUrl('accounts.edit.url');
        }
        $request->session()->forget('accounts.edit.fromUpdate');

        $openingBalanceAmount = (string) $repository->getOpeningBalanceAmount($account, false);
        if ('0' === $openingBalanceAmount) {
            $openingBalanceAmount = '';
        }
        $openingBalanceDate   = $repository->getOpeningBalanceDate($account);
        $currency             = $this->repository->getAccountCurrency($account) ?? $this->primaryCurrency;

        // include this account in net-worth charts?
        $includeNetWorth      = $repository->getMetaValue($account, 'include_net_worth');
        $includeNetWorth      = null === $includeNetWorth ? true : '1' === $includeNetWorth;

        // issue #8321
        $showNetWorth         = true;
        if ('liabilities' !== $objectType && 'asset' !== $objectType) {
            $showNetWorth = false;
        }

        // code to handle active-checkboxes
        $hasOldInput          = null !== $request->old('_token');
        $virtualBalance       = $account->virtual_balance ?? '0';
        $preFilled            = [
            'account_number'          => $repository->getMetaValue($account, 'account_number'),
            'account_role'            => $repository->getMetaValue($account, 'account_role'),
            'cc_type'                 => $repository->getMetaValue($account, 'cc_type'),
            'cc_monthly_payment_date' => $repository->getMetaValue($account, 'cc_monthly_payment_date'),
            'BIC'                     => $repository->getMetaValue($account, 'BIC'),
            'opening_balance_date'    => substr((string) $openingBalanceDate, 0, 10),
            'liability_type_id'       => $account->account_type_id,
            'opening_balance'         => app('steam')->bcround($openingBalanceAmount, $currency->decimal_places),
            'liability_direction'     => $this->repository->getMetaValue($account, 'liability_direction'),
            'virtual_balance'         => app('steam')->bcround($virtualBalance, $currency->decimal_places),
            'currency_id'             => $currency->id,
            'include_net_worth'       => $hasOldInput ? (bool) $request->old('include_net_worth') : $includeNetWorth,
            'interest'                => $repository->getMetaValue($account, 'interest'),
            'interest_period'         => $repository->getMetaValue($account, 'interest_period'),
            'notes'                   => $this->repository->getNoteText($account),
            'active'                  => $hasOldInput ? (bool) $request->old('active') : $account->active,
        ];
        if ('' === $openingBalanceAmount) {
            $preFilled['opening_balance'] = '';
        }

        $request->session()->flash('preFilled', $preFilled);

        return view('accounts.edit', compact('account', 'currency', 'canEditCurrency', 'showNetWorth', 'subTitle', 'subTitleIcon', 'locations', 'liabilityDirections', 'objectType', 'roles', 'preFilled', 'liabilityTypes', 'interestPeriods'));
    }

    /**
     * Edit account from template form
     */
    private function editFromTemplate(Request $request, Account $account, AccountRepositoryInterface $repository)
    {
        // Load the template with relationships
        $template = $account->template()->with(['accountType.category', 'accountType.behavior'])->first();
        
        if (!$template) {
            // Fallback to regular edit if template not found
            return $this->edit($request, $account, $repository);
        }

        // Get suggested fields from template
        $suggestedFields = $template->suggested_fields ?? [];
        
        // Get metadata preset from template
        $metadataPreset = $template->metadata_preset ?? [];

        // Get user's default currency
        $defaultCurrency = app('amount')->getPrimaryCurrency();
        
        // Get all currencies for the dropdown
        $allCurrencies = TransactionCurrency::orderBy('code', 'ASC')->get();
        
        // Get current account data for pre-filling
        $currency = $this->repository->getAccountCurrency($account) ?? $defaultCurrency;
        
        // Get account metadata values
        $institution = $account->getMetadataValue('institution', '');
        $owner = $account->getMetadataValue('owner', '');
        $productName = $account->getMetadataValue('product_name', '');

        // Get user's manageable entities for the owner dropdown
        $user = Auth::user();
        $userEntityService = app(\FireflyIII\Services\UserEntityService::class);
        $userEntities = $userEntityService->getUserManageableEntities($user);
        
        // Get user's individual entity as default
        $userEntity = $userEntityService->getUserEntity($user);

        return view('accounts.templates.edit', compact(
            'account', 
            'template', 
            'suggestedFields', 
            'metadataPreset', 
            'defaultCurrency', 
            'allCurrencies', 
            'currency',
            'institution',
            'owner',
            'productName',
            'userEntities',
            'userEntity'
        ));
    }

    /**
     * Update the account.
     *
     * @return $this|Redirector|RedirectResponse
     */
    public function update(Request $request, Account $account)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }

        // Check if this is a template-based update (check for template field in request)
        if ($request->has('template')) {
            return $this->updateFromTemplate($request, $account);
        }

        // For regular updates, use AccountFormRequest validation
        $accountFormRequest = new \FireflyIII\Http\Requests\AccountFormRequest();
        $accountFormRequest->setContainer(app());
        $accountFormRequest->setRedirector(app('redirect'));
        $accountFormRequest->setRequest($request);
        
        // Validate using AccountFormRequest rules
        $validator = validator($request->all(), $accountFormRequest->rules());
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data     = $accountFormRequest->getAccountData();
        $this->repository->update($account, $data);
        Log::channel('audit')->info(sprintf('Updated account #%d.', $account->id), $data);
        $request->session()->flash('success', (string) trans('firefly.updated_account', ['name' => $account->name]));

        // store new attachment(s):
        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($account, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        // redirect
        $redirect = redirect($this->getPreviousUrl('accounts.edit.url'));
        if (1 === (int) $request->get('return_to_edit')) {
            // set value so edit routine will not overwrite URL:
            $request->session()->put('accounts.edit.fromUpdate', true);

            $redirect = redirect(route('accounts.edit', [$account->id]))->withInput(['return_to_edit' => 1]);
        }
        app('preferences')->mark();

        return $redirect;
    }

    /**
     * Update account from template form
     */
    private function updateFromTemplate(Request $request, Account $account)
    {
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

        // Update basic account fields
        $account->name = $request->input('name');
        $account->active = $request->boolean('active');
        $account->virtual_balance = $request->input('virtual_balance', '0');
        $account->iban = $request->input('iban');
        $account->save();

        // Update currency through metadata
        $account->setMetadataValue('currency_id', $request->input('currency_id'));

        // Update metadata fields
        $account->setMetadataValue('institution', $request->input('institution'));
        $account->setMetadataValue('owner', $request->input('owner'));
        $account->setMetadataValue('product_name', $request->input('product_name'));

        Log::channel('audit')->info(sprintf('Updated account #%d from template.', $account->id), $request->all());
        $request->session()->flash('success', (string) trans('firefly.updated_account', ['name' => $account->name]));

        return redirect()->route('accounts.show', $account->id);
    }

    /**
     * Show the modal form for editing the specified account
     *
     * @param Request $request
     * @param Account $account
     * @param AccountRepositoryInterface $repository
     * @return View
     */
    public function editModal(Request $request, Account $account, AccountRepositoryInterface $repository)
    {
        if (!$this->isEditableAccount($account)) {
            return response('<div class="alert alert-danger">Account is not editable</div>', 403);
        }

        // Check if account has a template - if so, use template form
        if ($account->template_id) {
            return $this->editFromTemplateModal($request, $account, $repository);
        }

        // Get the category name from the new account classification system
        $categoryName = $account->accountType->category->name;
        $objectType = strtolower($categoryName);
        $roles = $this->getRoles();
        $liabilityTypes = $this->getLiabilityTypes();
        $location = $repository->getLocation($account);
        $latitude = $location instanceof Location ? $location->latitude : config('firefly.default_location.latitude');
        $longitude = $location instanceof Location ? $location->longitude : config('firefly.default_location.longitude');
        $zoomLevel = $location instanceof Location ? $location->zoom_level : config('firefly.default_location.zoom_level');
        $canEditCurrency = 0 === $account->piggyBanks()->count();
        $hasLocation = $location instanceof Location;
        $locations = [
            'location' => [
                'latitude' => old('location_latitude') ?? $latitude,
                'longitude' => old('location_longitude') ?? $longitude,
                'zoom_level' => old('location_zoom_level') ?? $zoomLevel,
                'has_location' => $hasLocation || 'true' === old('location_has_location'),
            ],
        ];

        $liabilityDirections = [
            'debit' => (string) trans('firefly.i_owe_amount'),
            'credit' => (string) trans('firefly.i_am_owed_amount'),
        ];

        $interestPeriods = [
            'monthly' => (string) trans('firefly.interest_calculation_monthly'),
            'yearly' => (string) trans('firefly.interest_calculation_yearly'),
        ];

        $preFilled = [
            'account_role' => $account->accountRole,
            'opening_balance' => $account->openingBalance,
            'opening_balance_date' => $account->openingBalanceDate,
            'notes' => $account->notes,
        ];

        $showNetWorth = true;
        $uploadSize = config('firefly.max_file_upload_size');

        // Get currencies
        $currencies = TransactionCurrency::orderBy('code', 'ASC')->get();
        $currency = $account->currency;

        // Get CC types
        $ccTypes = config('firefly.ccTypes');

        // Debug: Log that we're returning the modal view
        Log::info('Returning accounts.edit-modal view for account: ' . $account->id);
        
        return view('accounts.edit-modal', compact(
            'account',
            'objectType',
            'roles',
            'liabilityTypes',
            'liabilityDirections',
            'interestPeriods',
            'preFilled',
            'showNetWorth',
            'uploadSize',
            'currencies',
            'currency',
            'canEditCurrency',
            'ccTypes'
        ));
    }

    /**
     * Update the account via modal
     *
     * @param Request $request
     * @param Account $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateModal(Request $request, Account $account)
    {
        if (!$this->isEditableAccount($account)) {
            return response()->json(['success' => false, 'message' => 'Account is not editable'], 403);
        }

        // Check if this is a template-based update (check for template field in request)
        if ($request->has('template')) {
            return $this->updateFromTemplateModal($request, $account);
        }

        // For regular updates, use AccountFormRequest validation
        $accountFormRequest = new \FireflyIII\Http\Requests\AccountFormRequest();
        $accountFormRequest->setContainer(app());
        $accountFormRequest->setRedirector(app('redirect'));
        $accountFormRequest->setRequest($request);
        
        // Validate using AccountFormRequest rules
        $validator = validator($request->all(), $accountFormRequest->rules());
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $accountFormRequest->getAccountData();
            $this->repository->update($account, $data);
            Log::channel('audit')->info(sprintf('Updated account #%d.', $account->id), $data);

            // store new attachment(s):
            $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
            if (null !== $files && !auth()->user()->hasRole('demo')) {
                $this->attachments->saveAttachmentsForModel($account, $files);
            }

            return response()->json([
                'success' => true,
                'message' => trans('firefly.updated_account', ['name' => $account->name]),
                'account' => $account->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating account: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating account'], 500);
        }
    }

    /**
     * Show the modal form for editing from template
     *
     * @param Request $request
     * @param Account $account
     * @param AccountRepositoryInterface $repository
     * @return View
     */
    private function editFromTemplateModal(Request $request, Account $account, AccountRepositoryInterface $repository)
    {
        // This would be similar to editFromTemplate but return modal view
        // For now, redirect to regular template edit
        return redirect()->route('accounts.edit', $account->id);
    }

    /**
     * Update from template via modal
     *
     * @param Request $request
     * @param Account $account
     * @return \Illuminate\Http\JsonResponse
     */
    private function updateFromTemplateModal(Request $request, Account $account)
    {
        // Validate the request
        $validator = validator($request->all(), [
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

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Update basic account fields
            $account->name = $request->input('name');
            $account->active = $request->boolean('active');
            $account->virtual_balance = $request->input('virtual_balance', '0');
            $account->iban = $request->input('iban');
            $account->save();

            // Update currency through metadata
            $account->setMetadataValue('currency_id', $request->input('currency_id'));

            // Update metadata fields
            $account->setMetadataValue('institution', $request->input('institution'));
            $account->setMetadataValue('owner', $request->input('owner'));
            $account->setMetadataValue('product_name', $request->input('product_name'));

            Log::channel('audit')->info(sprintf('Updated account #%d from template.', $account->id), $request->all());

            return response()->json([
                'success' => true,
                'message' => trans('firefly.updated_account', ['name' => $account->name]),
                'account' => $account->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating account from template: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating account'], 500);
        }
    }
}
