<?php

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateAccountFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        
        $templateName = $this->input('template');
        $template = AccountType::where('name', $templateName)->where('active', true)->first();
        
        $rules = [
            'template' => 'required|string',
            'name' => 'required|string|max:255',
            'currency_id' => 'required|integer|exists:transaction_currencies,id',
            'institution' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'active' => 'boolean',
            'virtual_balance' => 'nullable|numeric',
            'iban' => 'nullable|string|max:255',
        ];

        // Add template-specific validation rules if template exists
        if ($template && $template->accountType) {
            $metadataSchema = $template->accountType->metadata_schema;
            if ($metadataSchema) {
                $schema = is_string($metadataSchema) ? json_decode($metadataSchema, true) : $metadataSchema;
                
                if ($schema && isset($schema['required_fields'])) {
                    foreach ($schema['required_fields'] as $field) {
                        $rules[$field] = 'required|string|max:255';
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'template.required' => 'Template is required.',
            'name.required' => 'Account name is required.',
            'currency_id.required' => 'Currency is required.',
            'institution.required' => 'Institution is required.',
            'account_holder.required' => 'Account holder is required.',
            'product_name.required' => 'Product name is required.',
        ];
    }

    /**
     * Get the validated data from the request.
     */
    public function getAccountData(): array
    {
        return $this->validated();
    }
}
