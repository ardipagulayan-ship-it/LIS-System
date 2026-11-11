<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User; // Assuming your User model is in App\\Models

class AuthController extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            //$request->session()->regenerate();

            $user = Auth::user();
            $user->load('roles');
            $roleTypes = $user->roles->where('is_active', true)->pluck('role_type')->values();
            // Create a token for the user (e.g., named 'api-token')
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'role_types' => $roleTypes,
                'token' => $token,
            ]);
        }

        // If authentication fails
        throw ValidationException::withMessages([
            'email' => [__('auth.failed')], // Use Laravel's standard auth failed message
        ]);
    }

    /**
     * Get the authenticated User.
     */
    public function user(Request $request)
    {
        // Already authenticated via Sanctum middleware
        $user = $request->user();
        if ($user) {
            $user->load('roles');
            $roleTypes = $user->roles->where('is_active', true)->pluck('role_type')->values();
            return response()->json([
                'user' => $user,
                'role_types' => $roleTypes,
            ]);
        }
        return response()->json(null, 401);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(Request $request)
    {
        // Get the current user's token and delete it
        $request->user()->currentAccessToken()->delete();

        // Standard logout methods if also using web sessions (might not be needed)
        // Auth::guard('web')->logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
