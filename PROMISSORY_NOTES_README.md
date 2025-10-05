# Promissory Notes Implementation for Firefly III

This implementation provides a complete solution for managing promissory notes in Firefly III using the existing liability system with credit direction.

## 🎯 Overview

Instead of creating a new account type, this solution leverages Firefly III's proven liability management system by using:
- **Account Type**: Loan (liability account)
- **Liability Direction**: Credit (I am owed this amount)
- **Automatic Balance Calculation**: The `CreditRecalculateService` handles all balance calculations

## 📁 Files Included

### Core Implementation
- `setup_promissory_notes.php` - Automated account creation script
- `test_promissory_note_balances.php` - Balance calculation verification
- `promissory_note_api_example.php` - API integration examples

### Documentation
- `PROMISSORY_NOTES_GUIDE.md` - Comprehensive user guide
- `PROMISSORY_NOTES_README.md` - This file

### Configuration
- `config/promissory_notes.php` - Configuration settings

## 🚀 Quick Start

### 1. Manual Setup (Recommended for First Time)

1. **Create Promissory Note Account**:
   ```
   Account Type: Loan
   Name: "Promissory Note - [Borrower Name]"
   Liability Direction: Credit (I am owed this amount)
   Interest Rate: [Your rate]%
   Interest Period: [Monthly/Quarterly/etc.]
   Opening Balance: Principal amount
   ```

2. **Create Interest Income Account**:
   ```
   Account Type: Revenue
   Name: "Interest Income - [Borrower Name]"
   ```

3. **Record Transactions**:
   - **Interest Payments**: Deposit from Interest Income → Bank Account
   - **Principal Payments**: Deposit from Bank Account → Promissory Note

### 2. Automated Setup

Run the setup script:
```bash
php setup_promissory_notes.php
```

### 3. API Integration

Use the API examples for automated management:
```php
$api = new PromissoryNoteAPI('https://your-firefly-instance.com', 'your-token');
$api->createPromissoryNoteAccount($data);
```

## 🔧 How It Works

### Credit Liability Direction Logic

The `CreditRecalculateService` automatically handles balance calculations:

- **Opening Balance**: Sets amount owed to you
- **Interest Payments** (Case 4): Increase amount owed
- **Principal Payments** (Case 3): Decrease amount owed
- **Current Debt**: Shows remaining principal

### Transaction Flow

```
Initial Loan:
Initial Balance Account → Promissory Note Account ($50,000)

Interest Payment:
Interest Income Account → Bank Account ($208.33)
[This increases the amount owed to you]

Principal Payment:
Bank Account → Promissory Note Account ($5,000)
[This decreases the amount owed to you]
```

## 📊 Benefits

1. **No Custom Code**: Uses existing, tested functionality
2. **Automatic Calculations**: Balance updates happen automatically
3. **Full Integration**: Works with all Firefly III features
4. **Proper Accounting**: Follows double-entry bookkeeping
5. **Reporting Ready**: Integrates with all reports and analytics

## 🧪 Testing

Run the balance calculation tests:
```bash
php test_promissory_note_balances.php
```

This verifies that the `CreditRecalculateService` correctly handles:
- Initial loan setup
- Interest payments
- Principal payments
- Multiple transactions

## 📈 Monitoring

### Key Metrics
- **Current Debt Balance**: Remaining principal owed to you
- **Interest Income**: Total interest earned per note
- **Payment History**: Complete transaction history
- **Net Worth Impact**: How notes affect your wealth

### Reports to Use
- Account Overview
- Income Reports
- Net Worth Reports
- Transaction Reports

## 🔄 Automation

### Monthly Interest Payments
```php
$api->recordInterestPayment($interestAccountId, $bankAccountId, $amount, $currencyId);
```

### Principal Payments
```php
$api->recordPrincipalPayment($bankAccountId, $promissoryNoteId, $amount, $currencyId);
```

### Balance Monitoring
```php
$balance = $api->getPromissoryNoteBalance($promissoryNoteId);
```

## 🏗️ Architecture

### Account Structure
```
Promissory Note Account (Loan, Credit)
├── Opening Balance: Principal amount
├── Interest Payments: Increase balance
├── Principal Payments: Decrease balance
└── Current Debt: Remaining principal

Interest Income Account (Revenue)
├── Tracks total interest earned
└── Source for interest payment transactions

Bank Account (Asset)
├── Receives interest payments
└── Source for principal payments
```

### Transaction Types
- **Opening Balance**: Initial principal setup
- **Deposit**: Interest and principal payments
- **Transfer**: Moving money between accounts

## 🔍 Troubleshooting

### Common Issues

1. **Balance Not Updating**
   - Verify `liability_direction` is set to 'credit'
   - Check that transactions are properly categorized

2. **Interest Not Tracking**
   - Ensure interest payments go through revenue account
   - Verify transaction types are correct

3. **Wrong Balance**
   - Confirm principal payments go to promissory note account
   - Check opening balance is set correctly

### Verification Steps

1. Check account meta fields:
   ```sql
   SELECT * FROM account_meta WHERE account_id = [ID] AND name = 'liability_direction';
   ```

2. Verify current debt balance:
   ```sql
   SELECT * FROM account_meta WHERE account_id = [ID] AND name = 'current_debt';
   ```

3. Review transaction history:
   ```sql
   SELECT * FROM transactions WHERE account_id = [ID] ORDER BY created_at;
   ```

## 🚀 Future Enhancements

### Potential Improvements
1. **Custom Account Type**: Create dedicated "Promissory Note" account type
2. **Automated Interest Calculation**: Built-in interest calculation engine
3. **Payment Reminders**: Automated notifications for due payments
4. **Advanced Reporting**: Specialized promissory note reports
5. **Integration Hooks**: Webhooks for external system integration

### Migration Path
If you later want a custom account type:
1. Create new `PROMISSORY_NOTE` enum value
2. Add to `valid_liabilities` configuration
3. Update `CreditRecalculateService` if needed
4. Migrate existing accounts

## 📚 Additional Resources

- [Firefly III Documentation](https://docs.firefly-iii.org/)
- [Liability Management Guide](https://docs.firefly-iii.org/tutorials/finances/mortgage/)
- [API Documentation](https://api-docs.firefly-iii.org/)
- [Community Forum](https://github.com/firefly-iii/firefly-iii/discussions)

## 🤝 Contributing

This implementation is designed to work with standard Firefly III installations. If you find issues or have improvements:

1. Test with the provided scripts
2. Verify against the balance calculation tests
3. Document any custom modifications needed
4. Share your findings with the community

## 📄 License

This implementation follows the same license as Firefly III (GNU Affero General Public License v3.0).

---

**Note**: This solution leverages Firefly III's existing liability system rather than creating new functionality. This approach provides maximum compatibility, reliability, and maintainability while giving you all the features needed for promissory note management.
