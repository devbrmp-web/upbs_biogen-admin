<?php

namespace Tests\Traits;

use App\Models\SeedClass;

trait CreatesSeedClasses
{
    protected function createSeedClasses(): void
    {
        SeedClass::firstOrCreate(['code' => 'BS'], [
            'name' => 'Breeder Seed',
            'description' => 'Breeder Seed',
            'is_active' => true,
            'stock_category' => 'weight',
            'default_unit' => 'kg'
        ]);
        
        SeedClass::firstOrCreate(['code' => 'FS'], [
            'name' => 'Foundation Seed',
            'description' => 'Foundation Seed',
            'is_active' => true,
            'stock_category' => 'weight',
            'default_unit' => 'kg'
        ]);
        
        SeedClass::firstOrCreate(['code' => 'NS'], [
            'name' => 'Nucleus Seed',
            'description' => 'Nucleus Seed',
            'is_active' => true,
            'stock_category' => 'weight',
            'default_unit' => 'kg'
        ]);
        
        SeedClass::firstOrCreate(['code' => 'ST'], [
            'name' => 'Starter',
            'description' => 'Starter',
            'is_active' => true,
            'stock_category' => 'unit',
            'default_unit' => 'bottle'
        ]);
    }
}