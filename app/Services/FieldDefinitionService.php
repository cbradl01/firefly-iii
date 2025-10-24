<?php

declare(strict_types=1);

namespace FireflyIII\Services;

use FireflyIII\FieldDefinitions\FieldDefinitions;

class FieldDefinitionService
{
    /**
     * Get fields for account creation/editing
     */
    public function getAccountFields(): array
    {
        return Account::getAccountFields();
    }

    /**
     * Get fields for institution entity creation/editing
     */
    public function getInstitutionFields(): array
    {
        return FieldDefinitions::getFieldsForTargetType('institution');
    }

    /**
     * Get fields for trust entity creation/editing
     */
    public function getTrustFields(): array
    {
        return FieldDefinitions::getFieldsForTargetType('trust');
    }

    /**
     * Get fields for business entity creation/editing
     */
    public function getBusinessFields(): array
    {
        return FieldDefinitions::getFieldsForTargetType('business');
    }

    /**
     * Get fields for individual entity creation/editing
     */
    public function getIndividualFields(): array
    {
        return FieldDefinitions::getFieldsForTargetType('individual');
    }

    /**
     * Get fields for advisor entity creation/editing
     */
    public function getAdvisorFields(): array
    {
        return FieldDefinitions::getFieldsForTargetType('advisor');
    }

    /**
     * Get fields for custodian entity creation/editing
     */
    public function getCustodianFields(): array
    {
        return FieldDefinitions::getFieldsForTargetType('custodian');
    }

    /**
     * Get fields for plan administrator entity creation/editing
     */
    public function getPlanAdministratorFields(): array
    {
        return FieldDefinitions::getFieldsForTargetType('plan_administrator');
    }

    /**
     * Get fields for any entity type
     */
    public function getFieldsForEntityType(string $entityType): array
    {
        return FieldDefinitions::getFieldsForTargetType($entityType);
    }

    /**
     * Get fields grouped by category for a specific target type
     */
    public function getFieldsByCategory(string $targetType): array
    {
        return FieldDefinitions::getFieldsByCategory($targetType);
    }

    /**
     * Get a specific field definition
     */
    public function getField(string $fieldName, string $targetType): ?array
    {
        return FieldDefinitions::getField($fieldName, $targetType);
    }

    /**
     * Get validation rules for a specific target type
     */
    public function getValidationRules(string $targetType): array
    {
        return FieldDefinitions::getValidationRules($targetType);
    }

    /**
     * Get field options for form rendering
     */
    public function getFieldOptions(string $targetType): array
    {
        return FieldDefinitions::getFieldsForTargetType($targetType);
    }

    /**
     * Get fields for form rendering grouped by category
     */
    public function getFieldsForForm(string $targetType): array
    {
        return FieldDefinitions::getFieldsByCategoryWithTranslations($targetType);
    }

    /**
     * Get fields with translations for any target type
     */
    public function getFieldsWithTranslations(string $targetType): array
    {
        return FieldDefinitions::getFieldsWithTranslations($targetType);
    }

    /**
     * Get fields grouped by category with translations
     */
    public function getFieldsByCategoryWithTranslations(string $targetType): array
    {
        return FieldDefinitions::getFieldsByCategoryWithTranslations($targetType);
    }

    /**
     * Get input types for form rendering
     */
    public function getInputTypes(): array
    {
        return [
            'text' => 'Text Input',
            'textarea' => 'Text Area',
            'email' => 'Email Input',
            'tel' => 'Telephone Input',
            'url' => 'URL Input',
            'number' => 'Number Input',
            'date' => 'Date Input',
            'checkbox' => 'Checkbox',
            'select' => 'Select Dropdown',
            'json' => 'JSON Input',
        ];
    }

    /**
     * Get data types for validation
     */
    public function getDataTypes(): array
    {
        return [
            'string' => 'String',
            'integer' => 'Integer',
            'decimal' => 'Decimal',
            'boolean' => 'Boolean',
            'date' => 'Date',
            'json' => 'JSON',
        ];
    }
}
