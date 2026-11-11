<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userRoles = UserRole::with('user')->get();
        return response()->json($userRoles);
    }

    /**
     * Get all users with a specific role type.
     */
    public function getUsersByRoleType($roleType)
    {
        $validRoleTypes = ['pathologist', 'verifier', 'performer', 'supervisor'];
        
        if (!in_array($roleType, $validRoleTypes)) {
            return response()->json([
                'message' => 'Invalid role type. Valid types are: pathologist, verifier, performer'
            ], 400);
        }
        
        // Get users with the specified role
        $users = User::whereHas('roles', function($query) use ($roleType) {
            $query->where('role_type', $roleType)
                  ->where('is_active', true);
        })->get(['id', 'name', 'email']);
        
        return response()->json($users);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_type' => 'required|in:pathologist,verifier,performer,supervisor',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Check if user already has this role
        $existingRole = UserRole::where('user_id', $request->user_id)
                                ->where('role_type', $request->role_type)
                                ->first();
                                
        if ($existingRole) {
            if (!$existingRole->is_active) {
                // If role exists but inactive, reactivate it
                $existingRole->is_active = true;
                $existingRole->save();
                return response()->json(['message' => 'User role reactivated', 'data' => $existingRole]);
            }
            
            return response()->json(['message' => 'User already has this role'], 409);
        }
        
        $userRole = UserRole::create([
            'user_id' => $request->user_id,
            'role_type' => $request->role_type,
            'is_active' => true,
        ]);
        
        return response()->json(['message' => 'User role created', 'data' => $userRole], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserRole $userRole)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserRole $userRole)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserRole $userRole)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $userRole->update($request->only(['is_active']));
        
        return response()->json(['message' => 'User role updated', 'data' => $userRole]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRole $userRole)
    {
        $userRole->delete();
        return response()->json(['message' => 'User role deleted']);
    }
}
