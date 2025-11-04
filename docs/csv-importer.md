# CSV Importer for Seed Stock

This document explains the CSV importer that maps seed stock data into Commodities, Varieties, Seed Classes, and Seed Lots with enhanced validation and error handling.

## Command

Run the importer with:

```bash
php artisan wub:import:seed-stock --file="/path/to/your/seed-stock.csv"
```

### Options

- `--file` (required): Path to the CSV file to import
- `--dry-run`: Preview the import without making changes to the database
- `--force`: Skip confirmation prompts (useful for automated scripts)

If the file path does not exist, the importer will exit gracefully with a warning.

## Expected Columns

The CSV should include a header row with at least these columns (case-insensitive):

- `komoditas` — Commodity name (required, max 255 characters)
- `varietas` — Variety name (required, max 255 characters)
- `kelas` — Seed class code (required: BS, FS, PL)
- `kuantitas` — Quantity (required, positive integer)
- `harga` — Price per unit (optional, positive numeric)
- `tahun` — Production year (optional, integer, defaults to current year)
- `unit` — Unit (required: `kg`, `botol`, `bottle`, `piece`)

### Validation Rules

- **Commodity**: Must not be empty, maximum 255 characters
- **Variety**: Must not be empty, maximum 255 characters  
- **Seed Class**: Must be one of: BS, FS, PL (case-insensitive)
- **Quantity**: Must be a positive integer (> 0)
- **Price**: If provided, must be a positive number (> 0)
- **Production Year**: If provided, must be between 2020 and current year + 1
- **Unit**: Must be one of: kg, botol, bottle, piece (normalized automatically)

## Mapping Rules

- Commodities: `updateOrCreate` by `name`, set `is_active = true`
- Varieties: `updateOrCreate` by `name` + `commodity_id`, set `sku`, `description`, and `price` (from CSV if provided)
- Seed Classes: expected codes `BS`, `FS`, `PL` (case-insensitive); invalid codes are skipped
- Seed Lots:
  - `update` existing lot by matching `variety_id`, `seed_class_id`, `production_year`, and `unit`
  - otherwise `create` new lot with generated `lot_code`
  - lots set `is_sellable = true`

## Error Handling & Reporting

### Validation & Safety
- **Row-level validation**: Each row is validated before processing
- **Graceful error handling**: Invalid rows are skipped with detailed error messages
- **Transaction safety**: Uses database transactions to ensure data consistency
- **Idempotent operations**: Uses `updateOrCreate` for safe re-imports

### Error Reporting
- **Detailed validation errors**: Shows specific validation failures for each row
- **Progress tracking**: Displays progress bar during import
- **Comprehensive summary**: Reports created, updated, and skipped records
- **Error log**: Invalid rows are logged with specific error reasons

### Example Error Output
```
Row 5: Validation failed
  - Quantity must be a positive integer (got: -10)
  - Seed class must be one of: BS, FS, PL (got: XX)

Row 12: Validation failed  
  - Commodity name is required
  - Unit must be one of: kg, botol, bottle, piece (got: liter)
```

## Example

```
komoditas,varietas,kelas,kuantitas,harga,tahun,unit
Rice,IR64,BS,100,65000,2025,kg
Rice,IR64,FS,80,60000,2025,kg
Soybean,Grobogan,PL,20,75000,2025,botol
```

## Performance & Best Practices

### Large File Handling
- **Memory efficient**: Processes files row-by-row to handle large datasets
- **Batch processing**: Groups database operations for better performance
- **Progress tracking**: Shows real-time progress for long-running imports

### Recommendations
- **File size**: Tested with files up to 10,000 rows
- **Encoding**: Use UTF-8 encoding for proper character support
- **Format**: Ensure CSV uses comma separators and proper quoting

## Troubleshooting

### Common Issues

**File not found**
```bash
Error: File not found: /path/to/file.csv
```
Solution: Check file path and permissions

**Missing required columns**
```bash
Error: Missing required columns: komoditas, varietas
```
Solution: Ensure CSV has proper header row with required columns

**Seed classes not found**
```bash
Error: Seed class 'BS' not found in database
```
Solution: Run seed class seeder first:
```bash
php artisan db:seed --class=Database\\Seeders\\SeedClassSeeder
```

**Memory issues with large files**
```bash
Fatal error: Allowed memory size exhausted
```
Solution: Increase PHP memory limit or split large files

## Prerequisites

Before running the importer, ensure:

1. **Seed Classes exist**: Run `php artisan db:seed --class=Database\Seeders\SeedClassSeeder`
2. **Database connection**: Verify database is accessible
3. **File permissions**: Ensure CSV file is readable
4. **PHP memory**: Adequate memory for file size (recommended: 512MB+)

## Notes

- The importer is designed for demo/test data population and production use
- Units are normalized automatically: `bottle` -> `botol`
- Price defaults if not provided: `PL` -> 75000, others -> 50000
- All operations are logged for audit purposes
- Safe to run multiple times (idempotent operations)

