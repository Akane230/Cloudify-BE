<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PDO;
use PDOException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'display_name' => $validated['display_name'],
                'phone_number' => $validated['phone_number'] ?? '',
                'password' => Hash::make($validated['password']),
                'profile_picture_url' => '',
                'status' => 'active',
            ]);

            event(new Registered($user));

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        } catch (PDOException $e) {
            return response()->json([
                'message' => 'User registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse {
        try {
            $credentials = $request->only('email', 'password');
            
            $user = User::where('email', $credentials['email'])->first();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Invalid credentials',
                ], 401);
            }
            
            if (!Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                ], 401);
            }
            
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse {
        try {
            /** @var User $user */
            $user = $request->user();
            
            if ($user) {
                $user->tokens()->delete();
            }

            return response()->json([
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->tokens()->delete();

        $token = $request->user()->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
