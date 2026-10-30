<?php

namespace App\Responsable\Api\Security;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Auth;

class AuthResponsable implements Responsable
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function toResponse($request)
    {
        $credentials = $request->validate([
            'email_username' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);


        $user = $this->login($credentials['email_username'], $credentials['password']);
        return $user;
    }

    private function login(string $username, string $password)
    {
        $provider = Auth::guard('web')->getProvider();
        $credentials = ['email' => $username, 'password' => $password];
        $user = $provider->retrieveByCredentials($credentials);

        if (!$user || !$provider->validateCredentials($user, $credentials)) {
            return response()->json([
                'message' => 'Login failed',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->name,
            'token' => $token,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'message' => 'Login successful',
        ], 200);
    }
}
