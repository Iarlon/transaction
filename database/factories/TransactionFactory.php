<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => fn (array $attributes) => User::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'amount' => fake()->randomFloat(2, 1, 100_000),
            'cpf' => fake()->numerify('###########'),
            'document_path' => null,
            'document_original_name' => null,
            'document_mime_type' => null,
            'status' => fake()->randomElement(TransactionStatus::cases()),
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => TransactionStatus::Processing,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => TransactionStatus::Approved,
        ]);
    }

    public function denied(): static
    {
        return $this->state(fn () => [
            'status' => TransactionStatus::Denied,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
        ]);
    }
}
