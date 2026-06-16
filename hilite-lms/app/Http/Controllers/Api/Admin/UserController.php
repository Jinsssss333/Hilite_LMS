<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadEngagement;
use App\Models\User;
use Illuminate\Http\Request;

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
}
