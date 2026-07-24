<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('transactions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_with_active_tenant_can_access_transactions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('transactions/index'));

        $this->assertTrue(tenancy()->initialized);
        $this->assertSame($user->tenant_id, tenant()->getTenantKey());
    }

    public function test_authenticated_user_with_inactive_tenant_is_forbidden(): void
    {
        $tenant = Tenant::factory()->create(['active' => false]);
        $user = User::factory()->for($tenant)->create();

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertForbidden();
    }
}
