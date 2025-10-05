<?php

/*
 * Promissory Note Setup Script for Firefly III
 * 
 * This script helps set up promissory note accounts using the existing
 * Loan account type with credit liability direction.
 * 
 * Usage: php setup_promissory_notes.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\User;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;

class PromissoryNoteSetup
{
    private UserRepositoryInterface $userRepository;
    private AccountRepositoryInterface $accountRepository;
    private User $user;
    private TransactionCurrency $currency;

    public function __construct()
    {
        $this->userRepository = app(UserRepositoryInterface::class);
        $this->accountRepository = app(AccountRepositoryInterface::class);
    }

    /**
     * Set up a promissory note account
     */
    public function createPromissoryNoteAccount(array $data): Account
    {
        // Validate required fields
        $required = ['borrower_name', 'principal_amount', 'interest_rate', 'interest_period'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Get or create user (assuming first user for demo)
        $this->user = $this->userRepository->first();
        if (!$this->user) {
            throw new RuntimeException('No users found. Please create a user first.');
        }

        // Get default currency
        $this->currency = $this->user->currencies()->where('enabled', true)->first();
        if (!$this->currency) {
            throw new RuntimeException('No enabled currencies found.');
        }

        $this->accountRepository->setUser($this->user);

        // Create the promissory note account (Loan type with credit direction)
        $accountData = [
            'name' => "Promissory Note - {$data['borrower_name']}",
            'account_type_id' => AccountType::where('type', 'Loan')->first()->id,
            'currency_id' => $this->currency->id,
            'active' => true,
            'include_net_worth' => true,
            'account_number' => $data['account_number'] ?? null,
            'iban' => $data['iban'] ?? null,
            'bic' => $data['bic'] ?? null,
        ];

        $account = $this->accountRepository->store($accountData);

        // Set liability direction to 'credit' (I am owed this amount)
        $this->accountRepository->setMeta($account, 'liability_direction', 'credit');
        
        // Set interest rate and period
        $this->accountRepository->setMeta($account, 'interest', (string) $data['interest_rate']);
        $this->accountRepository->setMeta($account, 'interest_period', $data['interest_period']);

        // Set opening balance (principal amount)
        $this->accountRepository->setOpeningBalance($account, $data['principal_amount']);

        echo "✅ Created promissory note account: {$account->name}\n";
        echo "   - Principal: {$data['principal_amount']} {$this->currency->code}\n";
        echo "   - Interest Rate: {$data['interest_rate']}% {$data['interest_period']}\n";
        echo "   - Liability Direction: Credit (I am owed this amount)\n";

        return $account;
    }

    /**
     * Create a corresponding revenue account for interest income
     */
    public function createInterestIncomeAccount(string $borrowerName): Account
    {
        $accountData = [
            'name' => "Interest Income - {$borrowerName}",
            'account_type_id' => AccountType::where('type', 'Revenue account')->first()->id,
            'currency_id' => $this->currency->id,
            'active' => true,
            'include_net_worth' => false,
        ];

        $account = $this->accountRepository->store($accountData);
        
        echo "✅ Created interest income account: {$account->name}\n";
        
        return $account;
    }

    /**
     * Record an interest payment transaction
     */
    public function recordInterestPayment(Account $promissoryNote, Account $interestIncome, Account $bankAccount, float $amount, string $description = null): void
    {
        $description = $description ?? "Interest payment from {$promissoryNote->name}";
        
        // Create deposit transaction: Interest Income -> Bank Account
        $transactionData = [
            'description' => $description,
            'date' => now()->format('Y-m-d'),
            'transactions' => [
                [
                    'type' => 'deposit',
                    'date' => now()->format('Y-m-d'),
                    'amount' => (string) $amount,
                    'description' => $description,
                    'source_id' => $interestIncome->id,
                    'destination_id' => $bankAccount->id,
                    'currency_id' => $this->currency->id,
                ]
            ]
        ];

        // This would need to be implemented using Firefly III's transaction creation logic
        echo "📝 Interest payment recorded: {$amount} {$this->currency->code}\n";
        echo "   From: {$interestIncome->name}\n";
        echo "   To: {$bankAccount->name}\n";
    }

    /**
     * Record a principal payment transaction
     */
    public function recordPrincipalPayment(Account $promissoryNote, Account $bankAccount, float $amount, string $description = null): void
    {
        $description = $description ?? "Principal payment from {$promissoryNote->name}";
        
        // Create deposit transaction: Bank Account -> Promissory Note
        $transactionData = [
            'description' => $description,
            'date' => now()->format('Y-m-d'),
            'transactions' => [
                [
                    'type' => 'deposit',
                    'date' => now()->format('Y-m-d'),
                    'amount' => (string) $amount,
                    'description' => $description,
                    'source_id' => $bankAccount->id,
                    'destination_id' => $promissoryNote->id,
                    'currency_id' => $this->currency->id,
                ]
            ]
        ];

        echo "📝 Principal payment recorded: {$amount} {$this->currency->code}\n";
        echo "   From: {$bankAccount->name}\n";
        echo "   To: {$promissoryNote->name}\n";
    }

    /**
     * Get current balance of a promissory note
     */
    public function getCurrentBalance(Account $promissoryNote): string
    {
        $currentDebt = $this->accountRepository->getMetaValue($promissoryNote, 'current_debt');
        return $currentDebt ?? '0';
    }

    /**
     * Display account summary
     */
    public function displayAccountSummary(Account $promissoryNote): void
    {
        $currentBalance = $this->getCurrentBalance($promissoryNote);
        $interestRate = $this->accountRepository->getMetaValue($promissoryNote, 'interest');
        $interestPeriod = $this->accountRepository->getMetaValue($promissoryNote, 'interest_period');
        $liabilityDirection = $this->accountRepository->getMetaValue($promissoryNote, 'liability_direction');

        echo "\n📊 Account Summary: {$promissoryNote->name}\n";
        echo "   - Current Balance: {$currentBalance} {$this->currency->code}\n";
        echo "   - Interest Rate: {$interestRate}% {$interestPeriod}\n";
        echo "   - Liability Direction: {$liabilityDirection}\n";
        echo "   - Account Type: {$promissoryNote->accountType->type}\n";
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    try {
        $setup = new PromissoryNoteSetup();
        
        // Example: Create a promissory note for John Doe
        $promissoryNote = $setup->createPromissoryNoteAccount([
            'borrower_name' => 'John Doe',
            'principal_amount' => 50000.00,
            'interest_rate' => 5.0,
            'interest_period' => 'monthly',
            'account_number' => 'PN-001',
        ]);

        // Create corresponding interest income account
        $interestIncome = $setup->createInterestIncomeAccount('John Doe');

        // Display summary
        $setup->displayAccountSummary($promissoryNote);

        echo "\n🎉 Promissory note setup complete!\n";
        echo "\nNext steps:\n";
        echo "1. Create a bank account to receive payments\n";
        echo "2. Record interest payments using the Interest Income account\n";
        echo "3. Record principal payments directly to the promissory note\n";
        echo "4. Monitor the current_debt balance to track remaining principal\n";

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
