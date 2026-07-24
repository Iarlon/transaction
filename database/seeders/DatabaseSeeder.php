<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenants = [
            ['id' => 'empresa-a', 'domain' => 'empresa-a.localhost'],
            ['id' => 'empresa-b', 'domain' => 'empresa-b.localhost'],
        ];

        foreach ($tenants as $tenantData) {
            $tenant = Tenant::query()->firstOrCreate(['id' => $tenantData['id']]);

            $tenant->domains()->firstOrCreate([
                'domain' => $tenantData['domain'],
            ]);

            $user = User::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->getTenantKey(),
                    'email' => "admin@{$tenantData['id']}.test",
                ],
                [
                    'name' => "Administrador {$tenantData['id']}",
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->transactions()->exists()) {
                Transaction::factory()
                    ->count(5)
                    ->forUser($user)
                    ->create();
            }
        }
    }
}
