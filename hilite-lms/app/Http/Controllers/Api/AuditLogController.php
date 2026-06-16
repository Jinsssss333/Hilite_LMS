<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * GET /api/audit-logs
     * Admin / Manager / Branch Head / Super Admin only.
     * Always company-scoped.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('view-audit-logs')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'errors'  => [],
            ], 403);
        }

        $companyId = app('current_company_id');

        $logs = AuditLog::with(['actor', 'engagement'])
            ->where('company_id', $companyId)
            ->when($request->engagement_id, fn($q) => $q->where('engagement_id', $request->engagement_id))
            ->when($request->user_id,       fn($q) => $q->where('actor_user_id', $request->user_id))
            ->when($request->action,        fn($q) => $q->where('action', $request->action))
            ->when($request->from_date,     fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date,       fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->orderByDesc('created_at')
            ->paginate((int) ($request->per_page ?? 25));

        return response()->json([
            'success' => true,
            'data'    => $logs->map(fn($l) => [
                'id'            => $l->id,
                'engagement_id' => $l->engagement_id,
                'action'        => $l->action,
                'actor'         => $l->actor
                    ? ['id' => $l->actor->id, 'name' => $l->actor->name]
                    : null,
                'before'     => $l->before,
                'after'      => $l->after,
                'created_at' => $l->created_at->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ], 200);
    }
}
