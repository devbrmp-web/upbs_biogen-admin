<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles directly to avoid seeder issues
        Role::firstOrCreate(['id' => 1], ['name' => 'Super Admin']);
        Role::firstOrCreate(['id' => 2], ['name' => 'Admin']);
    }
}
