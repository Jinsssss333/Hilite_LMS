<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadEngagement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /api/admin/users
     * Managers and above see everyone. TLs see their own team.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('view-admin-users')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'errors'  => [],
            ], 403);
        }

        $actor     = $request->user();
        $companyId = app('current_company_id');

        $query = User::where('company_id', $companyId)
            ->where('is_active', true);

        // TL can only see their own team
        if ($actor->role === 'team_lead') {
            $query->where('team_id', $actor->team_id);
        }

        $users = $query->get()->map(fn($u) => [
            'id'           => $u->id,
            'name'         => $u->name,
            'email'        => $u->email,
            'role'         => $u->role,
            'branch_id'    => $u->branch_id,
            'team_id'      => $u->team_id,
            'active_leads' => LeadEngagement::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('assigned_user_id', $u->id)
                ->where('status', 'active')
                ->count(),
            'company_id'   => $u->company_id,
        ]);

        return response()->json(['success' => true, 'data' => $users], 200);
    }

    /**
     * POST /api/admin/users
     * Admins can create lower-level users for their company.
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('manage-users')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'role'      => ['required', Rule::in(['manager', 'branch_head', 'team_lead', 'salesperson'])],
            'branch_id' => 'nullable|integer',
            'team_id'   => 'nullable|integer',
        ]);

        $companyId = app('current_company_id');

        $user = User::create([
            'company_id' => $companyId,
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'branch_id'  => $validated['branch_id'] ?? null,
            'team_id'    => $validated['team_id'] ?? null,
            'is_active'  => true,
        ]);

        // Attempt to process any waiting leads in case the new user has capacity
        app(\App\Services\Routing\AssignmentEngine::class)->processQueue($companyId);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data'    => $user,
        ], 201);
    }

    /**
     * DELETE /api/admin/users/{id}
     * Admins can remove lower-level users in their company.
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('manage-users')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $companyId = app('current_company_id');

        $user = User::where('company_id', $companyId)->findOrFail($id);

        // Prevent admin from deleting themselves or other admins/super_admins
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin users',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ], 200);
    }
}
