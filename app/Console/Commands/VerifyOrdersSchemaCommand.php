<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyOrdersSchemaCommand extends Command
{
    protected $signature = 'db:verify-orders-schema';
    protected $description = 'Tampilkan struktur kolom dan collation untuk tabel orders, order_items, shipments, payments';

    public function handle(): int
    {
        foreach (['orders', 'order_items', 'shipments', 'payments'] as $table) {
            $this->info("Schema {$table}:");
            $columns = DB::select("SHOW FULL COLUMNS FROM {$table}");
            foreach ($columns as $col) {
                $this->line(sprintf(
                    '%-20s | Type: %-60s | Null: %-4s | Collation: %s',
                    $col->Field,
                    $col->Type,
                    $col->Null,
                    $col->Collation ?? '-'
                ));
            }
        }

        return self::SUCCESS;
    }
}
