<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/login'));
    }

    public function test_user_can_authenticate_and_is_redirected_to_transactions(): void
    {
        $user = User::factory()->create();
        $sessionId = session()->getId();

        $response = $this->post(route('login.store'), [
            'email' => strtoupper($user->email),
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $response->assertRedirect(route('transactions.index', absolute: false));
    }

    public function test_domain_selects_the_tenant_when_email_exists_in_more_than_one_tenant(): void
    {
        $email = 'admin@example.test';
        $firstUser = User::factory()->create(['email' => $email]);
        $secondUser = User::factory()->create(['email' => $email]);
        $secondUser->tenant->domains()->create(['domain' => 'empresa-b.localhost']);

        $this->post('http://empresa-b.localhost/login', [
            'email' => $email,
            'password' => 'password',
        ])
            ->assertRedirect('/transactions');

        $this->assertAuthenticatedAs($secondUser);
        $this->assertFalse(auth()->user()->is($firstUser));
    }

    public function test_ambiguous_email_is_rejected_without_a_tenant_domain(): void
    {
        $email = 'admin@example.test';
        User::factory()->count(2)->create(['email' => $email]);

        $this->post(route('login.store'), [
            'email' => $email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_invalid_password_is_rejected_with_a_generic_error(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_from_an_inactive_tenant_cannot_authenticate(): void
    {
        $tenant = Tenant::factory()->create(['active' => false]);
        $user = User::factory()->for($tenant)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_ignores_client_supplied_tenant_id(): void
    {
        $user = User::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'tenant_id' => $otherTenant->getTenantKey(),
        ])->assertRedirect('/transactions');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_users_are_rate_limited(): void
    {
        $user = User::factory()->create();

        RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }
}
