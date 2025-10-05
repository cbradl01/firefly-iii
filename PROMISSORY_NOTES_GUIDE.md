# Promissory Notes in Firefly III

This guide explains how to set up and manage promissory notes in Firefly III using the existing liability system with credit direction.

## Overview

A promissory note is essentially a loan where you are the lender. Firefly III handles this perfectly using:
- **Account Type**: Loan (liability account)
- **Liability Direction**: Credit (I am owed this amount)
- **Automatic Balance Calculation**: The system automatically tracks the remaining principal

## Account Setup

### 1. Create Promissory Note Account

Create a **Loan** account with the following settings:

```
Account Name: "Promissory Note - [Borrower Name]"
Account Type: Loan
Liability Direction: Credit (I am owed this amount)
Interest Rate: [Your note's interest rate]
Interest Period: [Monthly/Quarterly/etc.]
Opening Balance: Principal amount
```

### 2. Create Interest Income Account

Create a **Revenue** account to track interest income:

```
Account Name: "Interest Income - [Borrower Name]"
Account Type: Revenue account
```

### 3. Bank Account

Use your existing bank account where payments are received.

## Transaction Flow

### Initial Loan Disbursement

When you lend the money:

```
Transaction Type: Opening Balance
From: Initial Balance Account
To: Promissory Note Account
Amount: Principal amount
```

### Interest Payments

When you receive interest payments:

```
Transaction Type: Deposit
From: Interest Income Account
To: Bank Account
Amount: Interest payment amount
```

**Important**: The interest payment should NOT go directly to the promissory note account. It goes through the revenue account to your bank account.

### Principal Payments

When you receive principal payments:

```
Transaction Type: Deposit
From: Bank Account
To: Promissory Note Account
Amount: Principal payment amount
```

This reduces the "current_debt" balance (amount owed to you).

## How the Credit Liability Direction Works

With `liability_direction = 'credit'`:

- **Opening Balance**: Sets the amount owed to you
- **Interest Payments**: Increase the amount owed (Case 4 in CreditRecalculateService)
- **Principal Payments**: Decrease the amount owed (Case 3 in CreditRecalculateService)
- **Current Debt**: Shows remaining principal balance

## Example Setup

### Accounts Created

1. **Promissory Note - John Doe** (Loan, Credit)
   - Principal: $50,000
   - Interest: 5% monthly
   - Current Balance: $50,000 (remaining principal)

2. **Interest Income - John Doe** (Revenue)
   - Tracks total interest earned

3. **Checking Account** (Asset)
   - Receives actual payments

### Sample Transactions

#### Month 1: Interest Payment
```
Deposit: $208.33
From: Interest Income - John Doe
To: Checking Account
Description: Monthly interest payment
```

#### Month 12: Principal Payment
```
Deposit: $5,000
From: Checking Account  
To: Promissory Note - John Doe
Description: Principal payment
```

#### Final Month: Balloon Payment
```
Deposit: $45,000
From: Checking Account
To: Promissory Note - John Doe
Description: Final principal payment
```

## Benefits of This Approach

1. **Automatic Balance Calculation**: The `CreditRecalculateService` automatically maintains correct balances
2. **Proper Accounting**: Follows double-entry bookkeeping principles
3. **Interest Tracking**: Separate revenue account tracks interest income
4. **Reporting Integration**: Works with all Firefly III reports
5. **No Custom Code**: Uses existing, tested functionality

## Monitoring Your Promissory Notes

### Key Metrics to Track

1. **Current Debt Balance**: Shows remaining principal owed to you
2. **Interest Income**: Total interest earned from each note
3. **Payment History**: All transactions related to each note
4. **Net Worth Impact**: How notes affect your overall net worth

### Reports to Use

- **Account Overview**: See current balances
- **Income Reports**: Track interest income by note
- **Net Worth Reports**: See impact on overall wealth
- **Transaction Reports**: Detailed payment history

## Advanced Features

### Multiple Promissory Notes

Create separate accounts for each borrower:

- Promissory Note - John Doe
- Promissory Note - Jane Smith
- Promissory Note - ABC Company

Each gets its own:
- Loan account (credit liability)
- Interest income revenue account

### Interest-Only vs. Amortizing Notes

**Interest-Only Notes** (like yours):
- Principal balance stays constant until balloon payment
- Interest payments go through revenue account
- Principal payments reduce the balance

**Amortizing Notes**:
- Each payment includes both principal and interest
- Split each payment between principal and interest portions
- Principal portion reduces the balance

### Tags and Categories

Use tags to group related transactions:
- Tag: "PromissoryNote-JohnDoe"
- Tag: "InterestPayment"
- Tag: "PrincipalPayment"

## Troubleshooting

### Common Issues

1. **Balance Not Updating**: Ensure `liability_direction` is set to 'credit'
2. **Interest Not Tracking**: Make sure interest payments go through revenue account
3. **Wrong Balance**: Check that principal payments go to the promissory note account

### Verification Steps

1. Check `liability_direction` meta field is 'credit'
2. Verify opening balance is set correctly
3. Confirm transaction types are correct
4. Review `current_debt` meta field for current balance

## Using the Setup Script

The included `setup_promissory_notes.php` script helps automate account creation:

```bash
php setup_promissory_notes.php
```

This will:
1. Create a promissory note account with credit liability direction
2. Create a corresponding interest income account
3. Set up proper meta fields
4. Display account summary

## Best Practices

1. **Consistent Naming**: Use "Promissory Note - [Borrower]" format
2. **Separate Accounts**: One promissory note account per borrower
3. **Regular Monitoring**: Check balances monthly
4. **Documentation**: Keep notes about terms and conditions
5. **Backup**: Regular database backups for important financial data

## Integration with Automation

If you're using the PFinance automation system, you can:

1. Create accounts via API
2. Automate transaction recording
3. Generate reports programmatically
4. Monitor balances automatically

This approach gives you a robust, maintainable system for tracking promissory notes using Firefly III's proven liability management system.
