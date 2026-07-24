<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_user_can_create_a_transaction_with_a_private_document(): void
    {
        $user = User::factory()->create();
        tenancy()->initialize($user->tenant);
        Storage::fake('local');

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'amount' => '1250.90',
            'cpf' => '529.982.247-25',
            'status' => TransactionStatus::Processing->value,
            'document' => UploadedFile::fake()->create('comprovante.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('transactions.index'));

        $transaction = Transaction::query()->sole();

        $this->assertSame($user->tenant_id, $transaction->tenant_id);
        $this->assertSame($user->id, $transaction->user_id);
        $this->assertSame('1250.90', $transaction->amount);
        $this->assertSame('52998224725', $transaction->cpf);
        $this->assertSame('comprovante.pdf', $transaction->document_original_name);
        Storage::disk('local')->assertExists($transaction->document_path);
    }

    public function test_transaction_validation_rejects_invalid_and_system_controlled_fields(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'amount' => '0',
                'cpf' => '111.111.111-11',
                'status' => 'unknown',
                'tenant_id' => $otherUser->tenant_id,
                'user_id' => $otherUser->id,
            ])
            ->assertSessionHasErrors([
                'amount',
                'cpf',
                'status',
                'document',
                'tenant_id',
                'user_id',
            ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_index_lists_only_active_transactions_from_the_authenticated_tenant(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $visible = Transaction::factory()->forUser($user)->create();
        Transaction::factory()->forUser($user)->create()->delete();
        Transaction::factory()->forUser($otherUser)->create();

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('transactions/index')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id', $visible->id));
    }

    public function test_user_cannot_access_any_operation_from_another_tenant(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignTransaction = Transaction::factory()->forUser($otherUser)->create();

        $this->actingAs($user)
            ->get(route('transactions.show', $foreignTransaction->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('transactions.edit', $foreignTransaction->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('transactions.update', $foreignTransaction->id), [
                'amount' => '10.00',
                'cpf' => '52998224725',
                'status' => TransactionStatus::Approved->value,
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $foreignTransaction->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('transactions.document', $foreignTransaction->id))
            ->assertNotFound();
    }

    public function test_user_can_update_editable_fields_without_changing_ownership(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->forUser($user)->processing()->create();
        $createdAt = $transaction->created_at;

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction->id), [
                'amount' => '99.95',
                'cpf' => '529.982.247-25',
                'status' => TransactionStatus::Approved->value,
            ])
            ->assertRedirect(route('transactions.index'));

        $transaction->refresh();

        $this->assertSame('99.95', $transaction->amount);
        $this->assertSame(TransactionStatus::Approved, $transaction->status);
        $this->assertSame($user->id, $transaction->user_id);
        $this->assertSame($user->tenant_id, $transaction->tenant_id);
        $this->assertTrue($createdAt?->equalTo($transaction->created_at) ?? false);
    }

    public function test_soft_delete_preserves_the_document(): void
    {
        $user = User::factory()->create();
        tenancy()->initialize($user->tenant);
        Storage::fake('local');
        Storage::disk('local')->put('transactions/document.pdf', 'document');
        $transaction = Transaction::factory()->forUser($user)->create([
            'document_path' => 'transactions/document.pdf',
            'document_original_name' => 'document.pdf',
            'document_mime_type' => 'application/pdf',
        ]);

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction->id))
            ->assertRedirect(route('transactions.index'));

        $this->assertSoftDeleted($transaction);
        Storage::disk('local')->assertExists('transactions/document.pdf');
    }

    public function test_authorized_user_can_download_the_private_document(): void
    {
        $user = User::factory()->create();
        tenancy()->initialize($user->tenant);
        Storage::fake('local');
        Storage::disk('local')->put('transactions/receipt.pdf', 'document');
        $transaction = Transaction::factory()->forUser($user)->create([
            'document_path' => 'transactions/receipt.pdf',
            'document_original_name' => 'receipt.pdf',
            'document_mime_type' => 'application/pdf',
        ]);

        $this->actingAs($user)
            ->get(route('transactions.document', $transaction->id))
            ->assertOk()
            ->assertDownload('receipt.pdf');
    }

    public function test_api_contract_returns_paginated_transactions(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->forUser($user)->count(2)->create();

        $this->actingAs($user)
            ->getJson(route('api.transactions.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.total', 2);
    }
}
