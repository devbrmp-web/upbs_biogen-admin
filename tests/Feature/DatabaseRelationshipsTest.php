<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\Variety;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSeedClasses;

class DatabaseRelationshipsTest extends TestCase
{
    use RefreshDatabase, CreatesSeedClasses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeedClasses();
    }

    public function test_commodity_has_many_varieties(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $variety1 = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Variety 1',
            'sku' => 'TEST-VAR-001',
            'description' => 'Test variety 1 description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $variety2 = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Variety 2',
            'sku' => 'TEST-VAR-002',
            'description' => 'Test variety 2 description',
            'price' => 15000,
            'stock_bs_kg' => 80,
            'stock_fs_kg' => 40,
            'minimum_limit' => 5,
            'is_active' => true,
        ]);

        $this->assertCount(2, $commodity->varieties);
        $this->assertTrue($commodity->varieties->contains($variety1));
        $this->assertTrue($commodity->varieties->contains($variety2));
    }

    public function test_variety_belongs_to_commodity(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $variety = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-003',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $this->assertEquals($commodity->id, $variety->commodity->id);
        $this->assertEquals('Test Commodity', $variety->commodity->name);
    }

    public function test_variety_has_many_seed_lots(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $variety = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-004',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $seedClass = SeedClass::where('code', 'BS')->first();

        $seedLot1 = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $seedLot2 = SeedLot::create([
            'lot_code' => 'BS-2024-002',
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'production_year' => 2024,
            'quantity' => 150,
            'unit' => 'kg',
            'price_per_unit' => 55000,
            'is_sellable' => true,
        ]);

        $this->assertCount(2, $variety->seedLots);
        $this->assertTrue($variety->seedLots->contains($seedLot1));
        $this->assertTrue($variety->seedLots->contains($seedLot2));
    }

    public function test_seed_lot_belongs_to_variety(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $variety = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-005',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $seedClass = SeedClass::where('code', 'BS')->first();

        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $this->assertEquals($variety->id, $seedLot->variety->id);
        $this->assertEquals('Test Variety', $seedLot->variety->name);
    }

    public function test_seed_lot_belongs_to_seed_class(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $variety = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-006',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $seedClass = SeedClass::where('code', 'BS')->first();

        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $this->assertEquals($seedClass->id, $seedLot->seedClass->id);
        $this->assertEquals($seedClass->name, $seedLot->seedClass->name);
    }

    public function test_seed_class_has_many_seed_lots(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $variety = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-007',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $seedClass = SeedClass::where('code', 'BS')->first();

        $seedLot1 = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $seedLot2 = SeedLot::create([
            'lot_code' => 'BS-2024-002',
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'production_year' => 2024,
            'quantity' => 150,
            'unit' => 'kg',
            'price_per_unit' => 55000,
            'is_sellable' => true,
        ]);

        $this->assertCount(2, $seedClass->seedLots);
        $this->assertTrue($seedClass->seedLots->contains($seedLot1));
        $this->assertTrue($seedClass->seedLots->contains($seedLot2));
    }

    public function test_user_belongs_to_role(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $role = Role::firstOrCreate(
            ['id' => 2],
            [
                'name' => 'Admin',
                'description' => 'Administrator dengan akses terbatas'
            ]
        );
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => $role->id,
        ]);

        $this->assertEquals($role->id, $user->role->id);
        $this->assertEquals('Admin', $user->role->name);
    }

    public function test_role_has_many_users(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $role = Role::firstOrCreate(
            ['id' => 2],
            [
                'name' => 'admin',
                'description' => 'Administrator dengan akses terbatas'
            ]
        );
        
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'role_id' => $role->id,
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'role_id' => $role->id,
        ]);

        $this->assertCount(2, $role->users);
        $this->assertTrue($role->users->contains($user1));
        $this->assertTrue($role->users->contains($user2));
    }

    public function test_cascade_delete_commodity_varieties(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
            
        ]);

        $variety = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-001',
            'description' => 'Test variety description',
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('varieties', ['id' => $variety->id]);

        $commodity->delete();

        $this->assertDatabaseMissing('commodities', ['id' => $commodity->id]);
        $this->assertDatabaseMissing('varieties', ['id' => $variety->id]);
    }

    public function test_cascade_delete_variety_seed_lots(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
            
        ]);

        $variety = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-002',
            'description' => 'Test variety description',
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $seedClass = SeedClass::where('code', 'BS')->first();

        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $this->assertDatabaseHas('seed_lots', ['id' => $seedLot->id]);

        $variety->forceDelete();

        $this->assertDatabaseMissing('varieties', ['id' => $variety->id]);
        $this->assertDatabaseMissing('seed_lots', ['id' => $seedLot->id]);
    }
}
