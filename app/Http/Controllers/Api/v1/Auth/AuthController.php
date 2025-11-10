<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Auth;

use App\Data\User\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * Endpoints for user authentication and account management
 */
class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * Creates a new user account and returns an authentication token.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuário registrado com sucesso',
            'user' => UserData::from($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     *
     * Authenticate a user and return an authentication token.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user' => UserData::from($user),
            'token' => $token,
        ]);
    }

    /**
     * Logout user
     *
     * Revoke the current user's authentication token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    /**
     * Get current user
     *
     * Retrieve the authenticated user's profile information.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => UserData::from($request->user()),
        ]);
    }
}
