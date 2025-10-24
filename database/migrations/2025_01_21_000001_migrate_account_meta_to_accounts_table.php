<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use FireflyIII\FieldDefinitions\FieldDefinitions;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add all field definition columns to accounts table
        $this->addFieldDefinitionColumns();
        
        // Step 2: Migrate data from account_meta to accounts table
        $this->migrateAccountMetaData();
        
        // Step 3: Make required fields NOT NULL after data migration
        $this->makeRequiredFieldsNotNull();
        
        // Step 4: Drop account_meta table
        Schema::dropIfExists('account_meta');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Recreate account_meta table
        Schema::create('account_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('name', 100);
            $table->text('data');
            $table->timestamps();
            
            $table->unique(['account_id', 'name']);
            $table->index('name');
        });
        
        // Step 2: Migrate data back from accounts to account_meta
        $this->migrateDataBackToAccountMeta();
        
        // Step 3: Remove field definition columns from accounts table
        $this->removeFieldDefinitionColumns();
    }

    /**
     * Add all field definition columns to accounts table
     */
    private function addFieldDefinitionColumns(): void
    {
        $accountFields = FieldDefinitions::getFieldsForTargetType('account');
        
        Schema::table('accounts', function (Blueprint $table) use ($accountFields) {
            foreach ($accountFields as $fieldName => $fieldDef) {
                $this->addColumnForField($table, $fieldName, $fieldDef);
            }
        });
    }

    /**
     * Add a single column based on field definition
     */
    private function addColumnForField(Blueprint $table, string $fieldName, array $fieldDef): void
    {
        $dataType = $fieldDef['data_type'] ?? 'string';
        $isRequired = $fieldDef['required'] ?? false;
        $defaultValue = $fieldDef['default'] ?? null;
        
        // Skip if column already exists
        if (Schema::hasColumn('accounts', $fieldName)) {
            return;
        }
        
        switch ($dataType) {
            case 'string':
            case 'text':
                $column = $table->string($fieldName, 255);
                break;
            case 'decimal':
                $column = $table->decimal($fieldName, 15, 4);
                break;
            case 'integer':
                $column = $table->integer($fieldName);
                break;
            case 'boolean':
                $column = $table->boolean($fieldName);
                break;
            case 'date':
                $column = $table->date($fieldName);
                break;
            case 'json':
            case 'array':
                $column = $table->json($fieldName);
                break;
            default:
                $column = $table->string($fieldName, 255);
        }
        
        // Always add as nullable first to avoid NOT NULL constraint violations
        $column->nullable();
        
        // Set default value if provided
        if ($defaultValue !== null) {
            $column->default($defaultValue);
        }
    }

    /**
     * Migrate data from account_meta to accounts table
     */
    private function migrateAccountMetaData(): void
    {
        $accountFields = FieldDefinitions::getFieldsForTargetType('account');
        $fieldNames = array_keys($accountFields);
        
        // Get all accounts with their metadata
        $accounts = DB::table('accounts')->get();
        
        foreach ($accounts as $account) {
            $updateData = [];
            
            // Get all metadata for this account
            $metadata = DB::table('account_meta')
                ->where('account_id', $account->id)
                ->get()
                ->keyBy('name');
            
            // Process each field definition
            foreach ($fieldNames as $fieldName) {
                $fieldDef = $accountFields[$fieldName];
                $dataType = $fieldDef['data_type'] ?? 'string';
                
                if (isset($metadata[$fieldName])) {
                    $value = $metadata[$fieldName]->data;
                    
                    // Parse JSON data if needed
                    if ($dataType === 'json' || $dataType === 'array') {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value = $decoded;
                        }
                    } elseif ($dataType === 'boolean') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($dataType === 'integer') {
                        $value = (int) $value;
                    } elseif ($dataType === 'decimal') {
                        $value = (float) $value;
                    }
                    
                    $updateData[$fieldName] = $value;
                } else {
                    // Use default value if no metadata exists
                    $defaultValue = FieldDefinitions::getFieldDefault($fieldName, 'account');
                    if ($defaultValue !== null) {
                        $updateData[$fieldName] = $defaultValue;
                    }
                }
            }
            
            // Update the account with all the field data
            if (!empty($updateData)) {
                DB::table('accounts')
                    ->where('id', $account->id)
                    ->update($updateData);
            }
        }
    }

    /**
     * Make required fields NOT NULL after data migration
     */
    private function makeRequiredFieldsNotNull(): void
    {
        $accountFields = FieldDefinitions::getFieldsForTargetType('account');
        
        Schema::table('accounts', function (Blueprint $table) use ($accountFields) {
            foreach ($accountFields as $fieldName => $fieldDef) {
                $isRequired = $fieldDef['required'] ?? false;
                
                if ($isRequired && Schema::hasColumn('accounts', $fieldName)) {
                    // Check if there are any NULL values for this field
                    $nullCount = DB::table('accounts')->whereNull($fieldName)->count();
                    
                    if ($nullCount === 0) {
                        // No NULL values, safe to make NOT NULL
                        $table->string($fieldName, 255)->nullable(false)->change();
                    } else {
                        // There are NULL values, keep as nullable
                        echo "Warning: Field '{$fieldName}' has {$nullCount} NULL values, keeping as nullable\n";
                    }
                }
            }
        });
    }

    /**
     * Migrate data back from accounts to account_meta (for rollback)
     */
    private function migrateDataBackToAccountMeta(): void
    {
        $accountFields = FieldDefinitions::getFieldsForTargetType('account');
        $fieldNames = array_keys($accountFields);
        
        $accounts = DB::table('accounts')->get();
        
        foreach ($accounts as $account) {
            foreach ($fieldNames as $fieldName) {
                $value = $account->$fieldName ?? null;
                
                if ($value !== null) {
                    // Convert value to JSON string for storage
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value);
                    } else {
                        $value = (string) $value;
                    }
                    
                    DB::table('account_meta')->insert([
                        'account_id' => $account->id,
                        'name' => $fieldName,
                        'data' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Remove field definition columns from accounts table
     */
    private function removeFieldDefinitionColumns(): void
    {
        $accountFields = FieldDefinitions::getFieldsForTargetType('account');
        $fieldNames = array_keys($accountFields);
        
        Schema::table('accounts', function (Blueprint $table) use ($fieldNames) {
            $table->dropColumn($fieldNames);
        });
    }
};
