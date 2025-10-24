# Account Meta to Accounts Table Migration Instructions

This document provides step-by-step instructions for migrating from the `account_meta` table to storing all account fields directly in the `accounts` table.

## Overview

**Before**: Account data stored in two places:
- `accounts` table: Basic fields (id, name, active, etc.)
- `account_meta` table: Key-value pairs for additional fields

**After**: All account data stored in one place:
- `accounts` table: All fields including those from field definitions

## Pre-Migration Steps

### 1. Backup Your Database
```bash
# Create a full database backup
mysqldump -u username -p database_name > backup_before_migration.sql

# Or for PostgreSQL
pg_dump -U username database_name > backup_before_migration.sql
```

### 2. Run the Test Script
```bash
cd /Users/Cameron/Repos/firefly-iii
php test_migration.php
```

This will show you the current state of your data and help verify the migration worked correctly.

### 3. Check for Custom Code
Search your codebase for any direct usage of `account_meta` table:
```bash
grep -r "account_meta" app/
grep -r "AccountMeta" app/
grep -r "accountMetadata" app/
```

## Migration Steps

### 1. Run the Migration
```bash
cd /Users/Cameron/Repos/firefly-iii
php artisan migrate
```

This will:
- Add all field definition columns to the `accounts` table
- Migrate data from `account_meta` to the new columns
- Drop the `account_meta` table

### 2. Verify the Migration
```bash
# Run the test script again
php test_migration.php
```

### 3. Test the PFinance Integration
```bash
# Test that the pfinance endpoints still work
curl -X POST http://your-firefly-url/api/v1/accounts/123/consolidate-transactions \
  -H "Content-Type: application/json" \
  -d '{"account_id": "123"}'
```

## Post-Migration Steps

### 1. Update Any Custom Code
If you found any custom code using `account_meta`, update it to use direct field access:

**Before:**
```php
$account = Account::with('accountMetadata')->find($id);
$institution = $account->accountMetadata->where('name', 'institution')->first()->data;
```

**After:**
```php
$account = Account::find($id);
$institution = $account->institution;
```

### 2. Update Controllers
Any controllers that were using `accountMetadata` relationship should now use direct field access.

### 3. Update Views
Any views that were accessing metadata through the relationship should now use direct field access.

## Rollback Instructions

If you need to rollback the migration:

```bash
php artisan migrate:rollback --step=1
```

This will:
- Recreate the `account_meta` table
- Migrate data back from `accounts` to `account_meta`
- Remove the field definition columns from `accounts`

## Benefits After Migration

1. **Simplified Queries**: No more JOINs needed to get complete account data
2. **Better Performance**: Single table queries are faster
3. **Cleaner Code**: Direct field access instead of relationship queries
4. **Easier Debugging**: All account data in one place
5. **Better for PFinance**: Complete account data available immediately

## Troubleshooting

### Issue: Migration Fails
- Check that all field definitions are valid
- Ensure database has enough space for new columns
- Check for any custom columns that might conflict

### Issue: Data Missing After Migration
- Check the migration logs
- Verify that `account_meta` data was properly migrated
- Use the test script to compare before/after data

### Issue: PFinance Integration Broken
- Check that all required fields are present
- Verify the `getAccountMetadataForPfinance` method is working
- Check the pfinance microservice logs

## Field Definitions Reference

The migration adds columns for all fields defined in `FieldDefinitions::ACCOUNT_FIELDS`:

- **Basic Info**: institution, account_holder, product_name, account_status, etc.
- **Financial**: current_balance, interest_rate, minimum_balance, etc.
- **Features**: check_writing, debit_card, online_banking, etc.
- **Investment**: contribution_limit, investment_style, etc.
- **Fees**: monthly_fee, annual_fee, transaction_fee, etc.

All fields will be nullable unless marked as required in the field definitions.

