<?php

namespace App\Console\Commands;

use App\Models\Commodity;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SeedStockImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'wub:import:seed-stock {--file=}';

    /**
     * The console command description.
     */
    protected $description = 'Import seed stock from CSV and map to commodities, varieties, seed classes, and seed lots';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->option('file');
        if (!$path) {
            $this->error('Please provide --file=PATH to CSV');
            return self::FAILURE;
        }

        if (!File::exists($path)) {
            $this->warn("CSV file not found: {$path}");
            return self::FAILURE;
        }

        $fh = fopen($path, 'r');
        if (!$fh) {
            $this->error('Unable to open CSV file');
            return self::FAILURE;
        }

        $header = null;
        $createdLots = 0;
        $updatedLots = 0;
        $skipped = 0;

        // Expected columns: komoditas, varietas, kelas, kuantitas, harga, tahun, unit
        while (($row = fgetcsv($fh)) !== false) {
            if (!$header) {
                $header = array_map(fn($h) => strtolower(trim($h)), $row);
                continue;
            }
            // Map row
            $data = [];
            foreach ($header as $i => $col) {
                $data[$col] = $row[$i] ?? null;
            }

            $commodityName = trim((string)($data['komoditas'] ?? ''));
            $varietyName = trim((string)($data['varietas'] ?? ''));
            $classCodeRaw = strtoupper(trim((string)($data['kelas'] ?? '')));
            $quantityRaw = trim((string)($data['kuantitas'] ?? ''));
            $priceRaw = trim((string)($data['harga'] ?? ''));
            $yearRaw = trim((string)($data['tahun'] ?? date('Y')));
            $unitRaw = strtolower(trim((string)($data['unit'] ?? 'kg')));

            if (!$commodityName || !$varietyName || !$classCodeRaw) {
                $skipped++;
                continue;
            }

            // Validate class code mapping to BS/FS/PL
            $classCode = match ($classCodeRaw) {
                'BS' => 'BS',
                'FS' => 'FS',
                'PL', 'PLANLET', 'PLANLETS' => 'PL',
                default => null,
            };
            if (!$classCode) { $skipped++; continue; }

            $quantity = is_numeric($quantityRaw) ? (int) $quantityRaw : null;
            $price = is_numeric($priceRaw) ? (float) $priceRaw : null;
            $year = is_numeric($yearRaw) ? (int) $yearRaw : (int) date('Y');
            $unit = in_array($unitRaw, ['kg','botol','bottle','piece'], true) ? ($unitRaw === 'botol' ? 'bottle' : $unitRaw) : 'kg';

            // Upsert commodity
            $commodity = Commodity::updateOrCreate(['name' => $commodityName], [ 'is_active' => true ]);

            // Upsert variety (price snapshot from CSV if provided)
            $variety = Variety::updateOrCreate([
                'name' => $varietyName,
                'commodity_id' => $commodity->id,
            ], [
                'sku' => strtoupper('VAR-' . substr(md5($varietyName), 0, 8)),
                'description' => $varietyName . ' — imported from CSV',
                'price' => $price ?? ($classCode === 'PL' ? 75000 : 50000),
                'status' => 'available',
                'is_active' => true,
            ]);

            // Seed class
            $seedClass = SeedClass::where('code', $classCode)->first();
            if (!$seedClass) { $skipped++; continue; }

            // Upsert seed lot by unique lot_code constructed from class-year-name
            $lotCode = $classCode . '-' . $year . '-' . strtoupper(Str::slug(substr($varietyName, 0, 12))) . '-' . strtoupper(Str::random(4));
            $existing = SeedLot::where('variety_id', $variety->id)
                ->where('seed_class_id', $seedClass->id)
                ->where('production_year', $year)
                ->where('unit', $unit)
                ->first();

            if ($existing) {
                $existing->update([
                    'quantity' => max($existing->quantity, $quantity ?? $existing->quantity),
                    'price_per_unit' => $price ?? $existing->price_per_unit,
                    'is_sellable' => true,
                ]);
                $updatedLots++;
            } else {
                SeedLot::create([
                    'variety_id' => $variety->id,
                    'seed_class_id' => $seedClass->id,
                    'lot_code' => $lotCode,
                    'production_year' => $year,
                    'quantity' => $quantity ?? 0,
                    'unit' => $unit,
                    'price_per_unit' => $price ?? 0,
                    'description' => 'Imported from CSV',
                    'is_sellable' => true,
                ]);
                $createdLots++;
            }
        }

        fclose($fh);
        $this->info("Import finished. Created: {$createdLots}, Updated: {$updatedLots}, Skipped: {$skipped}");
        return self::SUCCESS;
    }
}

