<?php

namespace FireflyIII\Services;

use Illuminate\Support\Facades\Log;

class AccountClassificationService
{
    private array $classificationData;

    public function __construct()
    {
        $this->loadClassificationData();
    }

    private function loadClassificationData(): void
    {
        $configPath = config_path('account_classification.json');
        
        if (!file_exists($configPath)) {
            Log::error('Account classification file not found at: ' . $configPath);
            $this->classificationData = [];
            return;
        }

        $jsonContent = file_get_contents($configPath);
        $this->classificationData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Error parsing account classification JSON: ' . json_last_error_msg());
            $this->classificationData = [];
        }
    }

    /**
     * Get Firefly III account type and role for a given account type
     */
    public function getFireflyMapping(string $accountType): ?array
    {
        if (empty($this->classificationData)) {
            return null;
        }

        // Search through all categories to find the account type
        foreach ($this->classificationData as $category => $subcategories) {
            $result = $this->searchInCategory($subcategories, $accountType);
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    private function searchInCategory(array $data, string $accountType): ?array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Check if this is a leaf node with firefly_type and firefly_role
                if (isset($value['firefly_type']) && isset($value['firefly_role'])) {
                    if ($key === $accountType) {
                        return [
                            'firefly_type' => $value['firefly_type'],
                            'firefly_role' => $value['firefly_role'],
                            'description' => $value['description'] ?? ''
                        ];
                    }
                } else {
                    // Recursively search in subcategories
                    $result = $this->searchInCategory($value, $accountType);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get all available account types
     */
    public function getAllAccountTypes(): array
    {
        $types = [];
        
        if (empty($this->classificationData)) {
            return $types;
        }

        foreach ($this->classificationData as $category => $subcategories) {
            $this->extractAccountTypes($subcategories, $types);
        }

        return $types;
    }

    private function extractAccountTypes(array $data, array &$types): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value['firefly_type']) && isset($value['firefly_role'])) {
                    $types[] = [
                        'name' => $key,
                        'firefly_type' => $value['firefly_type'],
                        'firefly_role' => $value['firefly_role'],
                        'description' => $value['description'] ?? ''
                    ];
                } else {
                    $this->extractAccountTypes($value, $types);
                }
            }
        }
    }

    /**
     * Check if account classification data is loaded
     */
    public function isLoaded(): bool
    {
        return !empty($this->classificationData);
    }
}
