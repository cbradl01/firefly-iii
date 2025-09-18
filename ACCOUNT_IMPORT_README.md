# Account Import Feature

This feature allows you to import multiple asset accounts from a CSV file into Firefly III.

## How to Use

1. **Navigate to Asset Accounts**: Go to `http://localhost/accounts/asset`
2. **Click Import Button**: Click the "Import from CSV" button
3. **Upload CSV File**: Select a CSV file with your account data
4. **Review Results**: The system will show you how many accounts were imported

## CSV File Format

Your CSV file should have the following columns (first row should contain headers):

### Required Columns
- `name` or `account_name` - The account name (required)

### Optional Columns
- `institution` - Financial institution name
- `opening_balance` - Opening balance amount
- `opening_balance_date` - Opening balance date (YYYY-MM-DD format)
- `currency_code` - Currency code (defaults to your default currency)
- `iban` - IBAN number
- `account_number` - Account number
- `notes` - Account notes
- `active` - Active status (1/true/yes/active for active, 0/false/no/inactive for inactive)

## Converting Excel to CSV

If you have an Excel file (.xlsx or .xls), you can convert it to CSV using the provided Python script:

```bash
python3 convert_excel_to_csv.py your_file.xlsx
```

This will create a `your_file.csv` file that you can upload to Firefly III.

## Example CSV

```csv
name,institution,opening_balance,opening_balance_date,iban,notes,active
"Checking Account","Bank of America",1500.00,2024-01-01,"US123456789","Primary checking account",1
"Savings Account","Bank of America",5000.00,2024-01-01,"US987654321","Emergency fund",1
"Investment Account","Vanguard",10000.00,2024-01-01,"","Long-term investments",1
```

## Notes

- Account names must be unique - duplicates will be skipped
- Empty rows are automatically skipped
- The system will show you how many accounts were successfully imported
- Any errors will be displayed in the results message
- Maximum file size is 10MB

## Troubleshooting

- **"Required column not found"**: Make sure your CSV has a `name` or `account_name` column
- **"File is empty"**: Check that your CSV file has data beyond the header row
- **Import errors**: Check the error messages for specific row issues
