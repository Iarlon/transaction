<?php

namespace Tests\Feature\Models;

use App\Enums\TransactionStatus;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_it_casts_domain_values_and_exposes_relationships(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()
            ->forUser($user)
            ->approved()
            ->create(['amount' => '1234.50']);

        $this->assertSame('1234.50', $transaction->amount);
        $this->assertSame(TransactionStatus::Approved, $transaction->status);
        $this->assertTrue($transaction->user->is($user));
        $this->assertTrue($user->transactions->contains($transaction));
        $this->assertTrue($user->tenant->transactions->contains($transaction));
    }

    public function test_transactions_are_scoped_to_the_initialized_tenant(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $visible = Transaction::factory()->forUser($firstUser)->create();
        Transaction::factory()->forUser($secondUser)->create();

        tenancy()->initialize($firstUser->tenant);

        $this->assertTrue(Transaction::query()->sole()->is($visible));
        $this->assertTrue(User::query()->sole()->is($firstUser));
    }

    public function test_a_transaction_cannot_reference_a_user_from_another_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenantUser = User::factory()->create();

        $this->expectException(QueryException::class);

        Transaction::factory()->create([
            'tenant_id' => $tenant->getTenantKey(),
            'user_id' => $otherTenantUser->id,
        ]);
    }

    public function test_transactions_use_soft_deletes(): void
    {
        $transaction = Transaction::factory()->create();

        $transaction->delete();

        $this->assertSoftDeleted($transaction);
        $this->assertNull(Transaction::query()->find($transaction->id));
        $this->assertNotNull(Transaction::withTrashed()->find($transaction->id));
    }

    public function test_the_same_email_can_exist_in_different_tenants(): void
    {
        $email = 'admin@example.test';

        User::factory()->create(['email' => $email]);
        $secondUser = User::factory()->create(['email' => $email]);

        $this->assertSame($email, $secondUser->email);
        $this->assertSame(2, User::query()->where('email', $email)->count());
    }
}
