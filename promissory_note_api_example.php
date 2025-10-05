<?php

/*
 * Promissory Note API Integration Example
 * 
 * This example shows how to integrate promissory note management
 * with Firefly III's API for automated transaction recording.
 */

class PromissoryNoteAPI
{
    private string $baseUrl;
    private string $accessToken;
    private array $headers;

    public function __construct(string $baseUrl, string $accessToken)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->accessToken = $accessToken;
        $this->headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
    }

    /**
     * Create a promissory note account via API
     */
    public function createPromissoryNoteAccount(array $data): array
    {
        $accountData = [
            'name' => "Promissory Note - {$data['borrower_name']}",
            'type' => 'liabilities',
            'account_type' => 'loan',
            'currency_id' => $data['currency_id'],
            'active' => true,
            'include_net_worth' => true,
            'account_number' => $data['account_number'] ?? null,
            'iban' => $data['iban'] ?? null,
            'bic' => $data['bic'] ?? null,
            'liability_direction' => 'credit', // I am owed this amount
            'interest' => (string) $data['interest_rate'],
            'interest_period' => $data['interest_period'],
        ];

        return $this->makeApiCall('POST', '/api/v1/accounts', $accountData);
    }

    /**
     * Create an interest income revenue account
     */
    public function createInterestIncomeAccount(string $borrowerName, int $currencyId): array
    {
        $accountData = [
            'name' => "Interest Income - {$borrowerName}",
            'type' => 'revenue',
            'currency_id' => $currencyId,
            'active' => true,
            'include_net_worth' => false,
        ];

        return $this->makeApiCall('POST', '/api/v1/accounts', $accountData);
    }

    /**
     * Set opening balance for promissory note
     */
    public function setOpeningBalance(int $accountId, float $amount, int $currencyId): array
    {
        $transactionData = [
            'type' => 'opening_balance',
            'date' => date('Y-m-d'),
            'transactions' => [
                [
                    'amount' => (string) $amount,
                    'currency_id' => $currencyId,
                    'source_id' => 'initial-balance',
                    'destination_id' => $accountId,
                ]
            ]
        ];

        return $this->makeApiCall('POST', '/api/v1/transactions', $transactionData);
    }

    /**
     * Record an interest payment
     */
    public function recordInterestPayment(int $interestIncomeAccountId, int $bankAccountId, float $amount, int $currencyId, string $description = null): array
    {
        $description = $description ?? 'Interest payment received';
        
        $transactionData = [
            'type' => 'deposit',
            'date' => date('Y-m-d'),
            'description' => $description,
            'transactions' => [
                [
                    'amount' => (string) $amount,
                    'currency_id' => $currencyId,
                    'source_id' => $interestIncomeAccountId,
                    'destination_id' => $bankAccountId,
                ]
            ]
        ];

        return $this->makeApiCall('POST', '/api/v1/transactions', $transactionData);
    }

    /**
     * Record a principal payment
     */
    public function recordPrincipalPayment(int $bankAccountId, int $promissoryNoteAccountId, float $amount, int $currencyId, string $description = null): array
    {
        $description = $description ?? 'Principal payment received';
        
        $transactionData = [
            'type' => 'deposit',
            'date' => date('Y-m-d'),
            'description' => $description,
            'transactions' => [
                [
                    'amount' => (string) $amount,
                    'currency_id' => $currencyId,
                    'source_id' => $bankAccountId,
                    'destination_id' => $promissoryNoteAccountId,
                ]
            ]
        ];

        return $this->makeApiCall('POST', '/api/v1/transactions', $transactionData);
    }

    /**
     * Get current balance of a promissory note
     */
    public function getPromissoryNoteBalance(int $accountId): array
    {
        return $this->makeApiCall('GET', "/api/v1/accounts/{$accountId}");
    }

    /**
     * Get all promissory note accounts
     */
    public function getPromissoryNoteAccounts(): array
    {
        return $this->makeApiCall('GET', '/api/v1/accounts?type=liabilities');
    }

    /**
     * Get transactions for a specific account
     */
    public function getAccountTransactions(int $accountId, string $startDate = null, string $endDate = null): array
    {
        $params = [];
        if ($startDate) $params['start'] = $startDate;
        if ($endDate) $params['end'] = $endDate;
        
        $queryString = $params ? '?' . http_build_query($params) : '';
        return $this->makeApiCall('GET', "/api/v1/accounts/{$accountId}/transactions{$queryString}");
    }

    /**
     * Make API call to Firefly III
     */
    private function makeApiCall(string $method, string $endpoint, array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("API Error {$httpCode}: " . $response);
        }
        
        return json_decode($response, true);
    }
}

/**
 * Example usage of the PromissoryNoteAPI class
 */
class PromissoryNoteExample
{
    private PromissoryNoteAPI $api;

    public function __construct()
    {
        // Initialize with your Firefly III instance
        $this->api = new PromissoryNoteAPI(
            'https://your-firefly-instance.com',
            'your-access-token'
        );
    }

    /**
     * Complete example: Set up a promissory note and record transactions
     */
    public function setupCompletePromissoryNote(): void
    {
        echo "🚀 Setting up complete promissory note example\n";
        echo "=============================================\n\n";

        try {
            // Step 1: Create promissory note account
            echo "1. Creating promissory note account...\n";
            $promissoryNote = $this->api->createPromissoryNoteAccount([
                'borrower_name' => 'John Doe',
                'currency_id' => 1, // USD
                'interest_rate' => 5.0,
                'interest_period' => 'monthly',
                'account_number' => 'PN-001',
            ]);
            echo "   ✅ Created: {$promissoryNote['data']['attributes']['name']}\n";

            // Step 2: Create interest income account
            echo "\n2. Creating interest income account...\n";
            $interestIncome = $this->api->createInterestIncomeAccount('John Doe', 1);
            echo "   ✅ Created: {$interestIncome['data']['attributes']['name']}\n";

            // Step 3: Set opening balance
            echo "\n3. Setting opening balance...\n";
            $openingBalance = $this->api->setOpeningBalance(
                $promissoryNote['data']['id'],
                50000.00,
                1
            );
            echo "   ✅ Opening balance set: $50,000\n";

            // Step 4: Record monthly interest payments
            echo "\n4. Recording monthly interest payments...\n";
            for ($month = 1; $month <= 3; $month++) {
                $interestPayment = $this->api->recordInterestPayment(
                    $interestIncome['data']['id'],
                    1, // Bank account ID
                    208.33, // Monthly interest
                    1, // Currency ID
                    "Monthly interest payment - Month {$month}"
                );
                echo "   ✅ Month {$month} interest: $208.33\n";
            }

            // Step 5: Record principal payment
            echo "\n5. Recording principal payment...\n";
            $principalPayment = $this->api->recordPrincipalPayment(
                1, // Bank account ID
                $promissoryNote['data']['id'],
                5000.00, // Principal payment
                1, // Currency ID
                "Principal payment"
            );
            echo "   ✅ Principal payment: $5,000\n";

            // Step 6: Check current balance
            echo "\n6. Checking current balance...\n";
            $balance = $this->api->getPromissoryNoteBalance($promissoryNote['data']['id']);
            $currentDebt = $balance['data']['attributes']['current_debt'] ?? 'N/A';
            echo "   📊 Current balance: {$currentDebt}\n";

            echo "\n🎉 Promissory note setup complete!\n";

        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example: Automated monthly interest payment
     */
    public function automatedMonthlyInterestPayment(int $promissoryNoteId, int $interestIncomeId, int $bankAccountId): void
    {
        echo "🤖 Automated monthly interest payment\n";
        echo "====================================\n";

        try {
            // Calculate monthly interest (5% annual, monthly)
            $annualRate = 0.05;
            $monthlyRate = $annualRate / 12;
            $principal = 50000.00; // This should be retrieved from current balance
            $monthlyInterest = $principal * $monthlyRate;

            $payment = $this->api->recordInterestPayment(
                $interestIncomeId,
                $bankAccountId,
                $monthlyInterest,
                1, // Currency ID
                'Automated monthly interest payment - ' . date('F Y')
            );

            echo "✅ Recorded monthly interest payment: $" . number_format($monthlyInterest, 2) . "\n";

        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    echo "Promissory Note API Integration Example\n";
    echo "=====================================\n\n";
    
    echo "This example shows how to:\n";
    echo "1. Create promissory note accounts via API\n";
    echo "2. Record interest and principal payments\n";
    echo "3. Automate monthly interest payments\n";
    echo "4. Track current balances\n\n";
    
    echo "To use this example:\n";
    echo "1. Update the base URL and access token\n";
    echo "2. Ensure you have API access enabled in Firefly III\n";
    echo "3. Run the setupCompletePromissoryNote() method\n\n";
    
    echo "API Endpoints Used:\n";
    echo "- POST /api/v1/accounts (create accounts)\n";
    echo "- POST /api/v1/transactions (record transactions)\n";
    echo "- GET /api/v1/accounts/{id} (get account details)\n";
    echo "- GET /api/v1/accounts/{id}/transactions (get transaction history)\n";
}
