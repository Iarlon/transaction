<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_user_can_authenticate_with_json_and_use_the_bearer_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.login'), [
            'email' => strtoupper($user->email),
            'password' => 'password',
            'device_name' => 'postman',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.tenant_id', $user->tenant_id)
            ->assertJsonStructure(['token']);

        $this->withToken((string) $response->json('token'))
            ->getJson(route('api.user.show'))
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.tenant_id', $user->tenant_id);
    }

    public function test_invalid_credentials_are_rejected_without_creating_a_token(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_client_cannot_choose_the_tenant_during_login(): void
    {
        $user = User::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $this->postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password',
            'tenant_id' => $otherTenant->getTenantKey(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tenant_id');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_domain_selects_the_tenant_for_an_ambiguous_email(): void
    {
        $email = 'admin@example.test';
        User::factory()->create(['email' => $email]);
        $expectedUser = User::factory()->create(['email' => $email]);
        $expectedUser->tenant->domains()->create(['domain' => 'empresa-b.localhost']);

        $this->postJson('http://empresa-b.localhost/api/login', [
            'email' => $email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $expectedUser->id)
            ->assertJsonPath('user.tenant_id', $expectedUser->tenant_id);
    }

    public function test_protected_api_routes_require_a_token(): void
    {
        $this->getJson(route('api.user.show'))->assertUnauthorized();
        $this->getJson(route('api.transactions.index'))->assertUnauthorized();
    }

    public function test_token_initializes_tenant_and_only_exposes_its_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $visible = Transaction::factory()->forUser($user)->create();
        Transaction::factory()->forUser($otherUser)->create();

        $login = $this->postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->withToken((string) $login->json('token'))
            ->getJson(route('api.transactions.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current');
        $otherToken = $user->createToken('other');

        $this->withToken($currentToken->plainTextToken)
            ->postJson(route('api.logout'))
            ->assertOk()
            ->assertJsonPath('message', 'Logout realizado com sucesso.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $currentToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->id,
        ]);

    }
}
