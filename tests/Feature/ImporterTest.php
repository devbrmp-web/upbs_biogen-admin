<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Tests\Traits\CreatesSeedClasses;

class ImporterTest extends TestCase
{
    use RefreshDatabase, CreatesSeedClasses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeedClasses();
    }

    /** @test */
    public function importer_parses_csv_and_creates_seed_lots()
    {
        // Prepare temporary CSV
        $tmp = storage_path('testing/tmp_seed_import.csv');
        File::ensureDirectoryExists(dirname($tmp));
        File::put($tmp, implode("\n", [
            'komoditas,varietas,kelas,kuantitas,harga,tahun,unit',
            'Rice,IR64,BS,100,65000,2025,kg',
            'Rice,IR64,FS,80,60000,2025,kg',
            'Soybean,Grobogan,PL,20,75000,2025,botol',
            'Unknown,,BS,50,50000,2025,kg' // invalid row (missing variety)
        ]));

        $exitCode = Artisan::call('wub:import:seed-stock', [
            '--file' => $tmp,
        ]);

        $this->assertEquals(0, $exitCode, 'Importer should finish successfully');

        // Verify data was created
        $this->assertDatabaseHas('commodities', ['name' => 'Rice']);
        $this->assertDatabaseHas('varieties', ['name' => 'IR64']);
        $this->assertDatabaseHas('seed_lots', ['unit' => 'kg']);
        $this->assertDatabaseHas('seed_lots', ['unit' => 'botol']);

        // Clean up
        File::delete($tmp);
    }
}

