<?php

require_once '/var/www/html/vendor/autoload.php';

use Illuminate\Http\Request;
use FireflyIII\Api\V1\Controllers\Models\Account\PfinanceController;

// Test data from the JSON file
$testData = [
    'accounts' => [
        [
            'account_holders' => ['Kaitlyn Bradley'],
            'institution' => 'Bank of America',
            'product_name' => 'Adv Plus Banking',
            'account_number' => '9129',
            'account_type_name' => 'Checking Account',
            'opening_balance' => 493.39,
            'opening_balance_date' => '2017-09-09',
            'open_date' => 'unknown',
            'first_statement_date' => '2017-09-09',
            'overdraft_protection' => true,
            'overdraft_protection_account' => '5855',
            'notes' => 'Opening balance: 4134.79, Opening balance date: 2023-02-06. Product Names: BofA Core Checking > Adv Plus Banking'
        ]
    ]
];

echo "Testing opening balance fix...\n";
echo "Test data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";

// Test the date conversion logic
$accountData = $testData['accounts'][0];
$hasOpeningBalance = isset($accountData['opening_balance']) && !empty($accountData['opening_balance']);
$hasOpeningBalanceDate = isset($accountData['opening_balance_date']) && !empty($accountData['opening_balance_date']);

echo "\nValidation:\n";
echo "Has opening balance: " . ($hasOpeningBalance ? 'YES' : 'NO') . "\n";
echo "Has opening balance date: " . ($hasOpeningBalanceDate ? 'YES' : 'NO') . "\n";

if ($hasOpeningBalance && $hasOpeningBalanceDate) {
    try {
        $originalDate = $accountData['opening_balance_date'];
        $carbonDate = \Carbon\Carbon::parse($accountData['opening_balance_date']);
        echo "Date conversion successful:\n";
        echo "  Original: $originalDate\n";
        echo "  Carbon: " . $carbonDate->toDateString() . "\n";
        echo "  Is Carbon instance: " . ($carbonDate instanceof \Carbon\Carbon ? 'YES' : 'NO') . "\n";
    } catch (\Exception $e) {
        echo "Date conversion failed: " . $e->getMessage() . "\n";
    }
}

echo "\nTest completed.\n";
