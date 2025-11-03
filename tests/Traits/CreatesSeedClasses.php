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
            'is_active' => true
        ]);
        
        SeedClass::firstOrCreate(['code' => 'FS'], [
            'name' => 'Foundation Seed',
            'description' => 'Foundation Seed',
            'is_active' => true
        ]);
        
        SeedClass::firstOrCreate(['code' => 'NS'], [
            'name' => 'Nucleus Seed',
            'description' => 'Nucleus Seed',
            'is_active' => true
        ]);
        
        SeedClass::firstOrCreate(['code' => 'PL'], [
            'name' => 'Planlet',
            'description' => 'Planlet',
            'is_active' => true
        ]);
    }
}