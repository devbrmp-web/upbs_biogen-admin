<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use App\Models\SeedClass;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles directly to avoid seeder issues
        Role::firstOrCreate(['id' => 1], ['name' => 'Super Admin']);
        Role::firstOrCreate(['id' => 2], ['name' => 'Admin']);
        
        // Create seed classes directly using firstOrCreate to avoid duplicates
        SeedClass::firstOrCreate(['code' => 'BS'], ['name' => 'Basic Seed', 'description' => 'Basic Seed', 'is_active' => true]);
        SeedClass::firstOrCreate(['code' => 'FS'], ['name' => 'Foundation Seed', 'description' => 'Foundation Seed', 'is_active' => true]);
        SeedClass::firstOrCreate(['code' => 'NS'], ['name' => 'Nucleus Seed', 'description' => 'Nucleus Seed', 'is_active' => true]);
        SeedClass::firstOrCreate(['code' => 'PL'], ['name' => 'Planlet', 'description' => 'Planlet', 'is_active' => true]);
    }
}
