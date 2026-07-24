<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;

class TenantUserAuthenticator
{
    public function authenticate(string $email, string $password, string $host): ?User
    {
        $email = Str::lower(trim($email));
        $domain = Domain::query()->where('domain', $host)->first();
        $tenant = $domain
            ? Tenant::query()->find($domain->tenant_id)
            : null;

        $query = User::query()
            ->withoutGlobalScopes()
            ->where('email', $email);

        if ($tenant) {
            $query->where('tenant_id', $tenant->getTenantKey());
        } elseif ((clone $query)->count() !== 1) {
            return null;
        }

        $user = $query->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        $userTenant = $user->tenant()->first();

        return $userTenant?->active ? $user : null;
    }
}
