<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use App\Services\TenantUserAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function store(
        LoginRequest $request,
        TenantUserAuthenticator $authenticator,
    ): JsonResponse {
        $validated = $request->validated();
        $user = $authenticator->authenticate(
            (string) $validated['email'],
            (string) $validated['password'],
            $request->getHost(),
        );

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas são inválidas.'],
            ]);
        }

        $token = $user->createToken(
            (string) ($validated['device_name'] ?? 'api'),
            ['transactions:read', 'transactions:write'],
        );

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken,
            'user' => $this->userData($user),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->userData($request->user()),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * @return array{id: int, name: string, email: string, tenant_id: string}
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tenant_id' => $user->tenant_id,
        ];
    }
}
