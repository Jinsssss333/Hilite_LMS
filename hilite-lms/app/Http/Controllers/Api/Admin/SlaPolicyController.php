<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlaPolicy;
use Illuminate\Http\Request;

class SlaPolicyController extends Controller
{
    /**
     * GET /api/admin/sla-policies
     * Admin / Super Admin only.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('manage-sla-policies')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'errors'  => [],
            ], 403);
        }

        $companyId = app('current_company_id');

        $policies = SlaPolicy::with('stage')
            ->where('company_id', $companyId)
            ->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'stage_id'         => $p->stage_id,
                'stage_name'       => $p->stage?->name,
                'sla_days'         => $p->sla_days,
                'escalate_to_role' => $p->escalate_to_role,
                'company_id'       => $p->company_id,
            ]);

        return response()->json(['success' => true, 'data' => $policies], 200);
    }

    /**
     * PATCH /api/admin/sla-policies/{id}
     * Admin / Super Admin only.
     */
    public function update(Request $request, int $id)
    {
        if (!$request->user()->can('manage-sla-policies')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'errors'  => [],
            ], 403);
        }

        $request->validate([
            'sla_days'         => 'required|integer|min:1|max:365',
            'escalate_to_role' => 'nullable|in:team_lead,manager,admin',
        ]);

        $policy = SlaPolicy::where('company_id', app('current_company_id'))->findOrFail($id);
        $policy->update($request->only('sla_days', 'escalate_to_role'));

        return response()->json([
            'success' => true,
            'data'    => $policy->fresh('stage'),
            'message' => 'SLA policy updated',
        ], 200);
    }
}
