<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user with role_id 1 (Super Admin)
        $this->admin = User::factory()->create([
            'role_id' => 1,
        ]);
    }

    public function test_automatic_logging_on_model_events()
    {
        $this->actingAs($this->admin);

        // 1. Create - Should log
        $variety = \App\Models\Variety::factory()->create([
            'name' => 'Auto Log Variety',
            'description' => 'Testing auto log',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_CREATE,
            'table_name' => 'varieties',
            'record_id' => $variety->id,
        ]);

        // 2. Update - Should log
        $variety->update(['name' => 'Updated Variety Name']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_UPDATE,
            'table_name' => 'varieties',
            'record_id' => $variety->id,
        ]);

        // 3. Delete - Should log
        $variety->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_DELETE,
            'table_name' => 'varieties',
            'record_id' => $variety->id,
        ]);
    }

    public function test_admin_can_view_audit_logs()
    {
        // Create dummy logs
        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => AuditLog::ACTION_CREATE,
            'table_name' => 'varieties',
            'record_id' => 1,
            'new_data' => ['name' => 'Test Variety'],
            'description' => 'Created variety test',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Audit Logs');
        $response->assertSee('Created variety test');
    }

    public function test_admin_can_filter_audit_logs()
    {
        // Create log 1 (Create Variety)
        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => AuditLog::ACTION_CREATE,
            'table_name' => 'varieties',
            'record_id' => 1,
            'description' => 'Log Variety Create',
        ]);

        // Create log 2 (Update Order)
        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => AuditLog::ACTION_UPDATE,
            'table_name' => 'orders',
            'record_id' => 2,
            'description' => 'Log Order Update',
        ]);

        // Filter by action CREATE
        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index', ['action' => AuditLog::ACTION_CREATE]));

        $response->assertStatus(200);
        $response->assertSee('Log Variety Create');
        $response->assertDontSee('Log Order Update');

        // Filter by table name 'orders'
        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index', ['table_name' => 'orders']));
        
        $response->assertStatus(200);
        $response->assertSee('Log Order Update');
        $response->assertDontSee('Log Variety Create');
    }
}
