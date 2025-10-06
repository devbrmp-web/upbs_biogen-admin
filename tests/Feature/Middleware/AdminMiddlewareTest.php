<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 1 = super_admin, 2 = admin
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_non_admin_gets_403(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne(['role_id' => null]);

        $this->actingAs($user);
        $this->get(route('admin.dashboard'))->assertForbidden(); // 403
    }

    public function test_admin_can_access_dashboard(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne(['role_id' => 2]);

        $this->actingAs($user);
        $this->get(route('admin.dashboard'))->assertOk(); // 200
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne(['role_id' => 1]);

        $this->actingAs($user);
        $this->get(route('admin.dashboard'))->assertOk(); // 200
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
