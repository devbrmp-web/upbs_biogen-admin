<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        
        // Create roles directly to avoid seeder issues
        if (Schema::hasTable('roles')) {
            Role::firstOrCreate(['id' => 1], ['name' => 'Super Admin']);
            Role::firstOrCreate(['id' => 2], ['name' => 'Admin']);
        }
    }
}
