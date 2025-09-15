<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:15|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone_verified' => 'boolean',
                'role' => 'in:admin,partner,user',
                'status' => 'in:active,inactive',
                'fcm_token' => 'string|nullable',
            ])->validate();

            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'phone_verified' => $validated['phone_verified'] ?? false,
                'role' => $validated['role'] ?? 'user',
                'status' => $validated['status'] ?? 'active',
                'fcm_token' => $validated['fcm_token'] ?? null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'phone' => 'required|string|max:15',
                'password' => 'required|string|min:8',
                'fcm_token' => 'string|nullable',
            ])->validate();

            $user = \App\Models\User::where('phone', $validated['phone'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone or password',
                ], 401);
            }

            if (isset($validated['fcm_token'])) {
                $user->fcm_token = $validated['fcm_token'];
                $user->save();
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User logged in successfully',
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
