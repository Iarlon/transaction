<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyFromUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $tenant = $user->tenant()->first();

        abort_unless($tenant?->active, 403, 'Usuário sem acesso a um tenant válido.');

        if (tenancy()->initialized && tenant()->getTenantKey() !== $tenant->getTenantKey()) {
            abort(403);
        }

        if (! tenancy()->initialized) {
            tenancy()->initialize($tenant);
        }

        app()->instance(Tenant::class, $tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
