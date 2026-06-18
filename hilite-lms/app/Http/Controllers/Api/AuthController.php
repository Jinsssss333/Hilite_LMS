<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email','password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'errors'  => (object)[]
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user'  => [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'email'        => $user->email,
                    'role'         => $user->role,
                    'company_id'   => $user->company_id,
                    'company_name' => $user->company->name,
                    'branch_id'    => $user->branch_id,
                    'team_id'      => $user->team_id,
                    'is_available' => $user->is_available,
                ]
            ],
            'message' => 'Login successful'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'data' => (object)[], 'message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('company');
        return response()->json(['success' => true, 'data' => [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'role'         => $user->role,
            'company_id'   => $user->company_id,
            'company_name' => $user->company->name,
            'branch_id'    => $user->branch_id,
            'team_id'      => $user->team_id,
            'is_available' => $user->is_available,
        ]]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate(['is_available' => 'required|boolean']);
        $user = $request->user();
        $user->update(['is_available' => $request->is_available]);

        return response()->json([
            'success' => true,
            'data' => ['is_available' => $user->is_available],
            'message' => 'Availability updated'
        ]);
    }
}
