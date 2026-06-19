<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Services\LeadIntakeService;
use App\Services\PhoneNormalizationService;
use App\Models\LeadEngagement;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(
        protected LeadIntakeService $intakeService,
        protected PhoneNormalizationService $phoneService,
    ) {}

    public function index(Request $request)
    {
        $companyId = app('current_company_id');
        $actor     = $request->user();

        $visibilityRoles = ['manager','branch_head','admin','super_admin'];

        $rawSearch = trim((string) $request->search);
        $searchPhone = null;
        if ($rawSearch !== '') {
            $normalized = $this->phoneService->normalize($rawSearch);
            if ($normalized) {
                $searchPhone = $normalized;
            }
        }

        $query = LeadEngagement::with(['lead','stage','assignedTo'])
            ->when($searchPhone, fn($q) =>
                $q->whereHas('lead', fn($lq) => $lq->where('phone_e164', $searchPhone))
            )
            ->when(!$searchPhone && $rawSearch !== '', fn($q) =>
                $q->whereHas('lead', fn($lq) =>
                    $lq->where('name', 'like', $rawSearch.'%')
                       ->orWhere('email', $rawSearch)
                )
            )
            ->when($request->stage_id, fn($q) => $q->where('stage_id', $request->stage_id))
            ->when($request->source, fn($q) => $q->where('source', $request->source))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        if ($actor->role === 'salesperson') {
            $query->where(fn($q) =>
                $q->where('assigned_user_id', $actor->id)
                  ->orWhereNull('assigned_user_id')
            );
        } elseif ($actor->role === 'team_lead') {
            $teamUserIds = \App\Models\User::where('company_id', $companyId)
                ->where('team_id', $actor->team_id)
                ->pluck('id');
            $query->where(fn($q) =>
                $q->whereIn('assigned_user_id', $teamUserIds)
                  ->orWhereNull('assigned_user_id')
            );
            if ($request->assigned_to) {
                $query->where('assigned_user_id', $request->assigned_to);
            }
        } elseif (in_array($actor->role, $visibilityRoles)) {
            $query->when($request->assigned_to, fn($q) => $q->where('assigned_user_id', $request->assigned_to));
        }

        $query->orderByRaw('last_activity_at IS NULL, last_activity_at DESC');

        $perPage = min((int) ($request->per_page ?? 25), 100);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $results->map(fn($e) => $this->formatEngagementSummary($e)),
            'meta'    => [
                'current_page' => $results->currentPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ]
        ]);
    }

    public function store(StoreLeadRequest $request)
    {
        $companyId   = app('current_company_id');
        $actor       = $request->user();
        $actorUserId = $actor->id;

        try {
            $result = $this->intakeService->intake($request->validated(), $companyId, $actorUserId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => (object)[]], 422);
        }

        $engagement = $result['engagement'];

        if ($result['conflict']) {
            return response()->json([
                'success' => false,
                'message' => 'This lead is currently assigned to ' . optional($engagement->assignedTo)->name . '. Contact your Team Lead to reassign.',
                'errors'  => (object)[]
            ], 409);
        }

        $assignedToPayload = null;
        if ($engagement->assignedTo && $engagement->assignedTo->id === $actorUserId) {
            $assignedToPayload = ['id' => $engagement->assignedTo->id, 'name' => $engagement->assignedTo->name];
        }

        $status = $result['is_duplicate'] ? 200 : 201;
        return response()->json([
            'success' => true,
            'data'    => [
                'lead_id'       => $engagement->lead_id,
                'engagement_id' => $engagement->id,
                'phone_e164'    => $engagement->lead->phone_e164,
                'is_duplicate'  => $result['is_duplicate'],
                'assigned_to'   => $assignedToPayload,
                'stage'         => $engagement->stage->name,
            ],
            'message' => $result['is_duplicate'] ? 'Duplicate lead attached' : 'Lead created',
        ], $status);
    }

    public function show(Request $request, int $id)
    {
        $engagement = LeadEngagement::with([
            'lead','stage','assignedTo',
            'activities.createdBy','activities.disposition',
            'assignments.assignedTo','assignments.assignedBy'
        ])->findOrFail($id);

        $actor = $request->user();

        if ($actor->role === 'salesperson') {
            $isOwner = $engagement->assigned_user_id === $actor->id;
            $isUnassigned = $engagement->assigned_user_id === null;
            if (!$isOwner && !$isUnassigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'This lead is assigned to another team member.',
                    'errors'  => (object)[]
                ], 403);
            }
        } elseif ($actor->role === 'team_lead') {
            $isOwner = $engagement->assigned_user_id === $actor->id;
            $isUnassigned = $engagement->assigned_user_id === null;
            $isOwnTeam = $engagement->assignedTo && $engagement->assignedTo->team_id === $actor->team_id;
            if (!$isOwner && !$isUnassigned && !$isOwnTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'This lead belongs to another team.',
                    'errors'  => (object)[]
                ], 403);
            }
        }

        return response()->json(['success' => true, 'data' => $this->formatEngagementDetail($engagement)]);
    }

    public function checkDuplicate(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        $companyId = app('current_company_id');
        $actor     = $request->user();
        $phone     = $this->phoneService->normalize($request->phone);

        if (!$phone) {
            return response()->json(['success' => true, 'data' => ['exists' => false, 'engagement_exists_in_company' => false]]);
        }

        $lead = \App\Models\Lead::where('phone_e164', $phone)->first();
        if (!$lead) {
            return response()->json(['success' => true, 'data' => ['exists' => false, 'engagement_exists_in_company' => false]]);
        }

        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->with(['assignedTo','stage'])
            ->first();

        $privileged = ['manager','branch_head','admin','super_admin','team_lead'];
        $canSeeOwner = $engagement
            && ($engagement->assigned_user_id === $actor->id || in_array($actor->role, $privileged));

        return response()->json(['success' => true, 'data' => [
            'exists'                       => true,
            'engagement_exists_in_company' => (bool) $engagement,
            'assigned_to'                  => $canSeeOwner && $engagement->assignedTo
                ? ['id' => $engagement->assignedTo->id, 'name' => $engagement->assignedTo->name]
                : null,
            'stage'                        => $canSeeOwner ? $engagement?->stage?->name : null,
            'is_assigned'                  => $engagement ? (bool) $engagement->assigned_user_id : false,
        ]]);
    }

    private function formatEngagementSummary(LeadEngagement $e): array
    {
        return [
            'engagement_id'    => $e->id,
            'lead_id'          => $e->lead_id,
            'name'             => $e->lead->name,
            'phone_e164'       => $e->lead->phone_e164,
            'email'            => $e->lead->email,
            'source'           => $e->source,
            'stage'            => ['id' => $e->stage->id, 'name' => $e->stage->name, 'color' => $e->stage->color],
            'assigned_to'      => $e->assignedTo ? ['id' => $e->assignedTo->id, 'name' => $e->assignedTo->name] : null,
            'last_activity_at' => $e->last_activity_at?->toIso8601String(),
            'sla_due_at'       => $e->sla_due_at?->toIso8601String(),
            'sla_breached'     => $e->sla_breached,
            'created_at'       => $e->created_at->toIso8601String(),
        ];
    }

    private function formatEngagementDetail(LeadEngagement $e): array
    {
        $summary = $this->formatEngagementSummary($e);
        $summary['activities'] = $e->activities->map(fn($a) => [
            'id'          => $a->id,
            'type'        => $a->type,
            'disposition' => $a->disposition ? ['id' => $a->disposition->id, 'label' => $a->disposition->label] : null,
            'notes'       => $a->notes,
            'follow_up_at'=> $a->follow_up_at?->toIso8601String(),
            'created_by'  => ['id' => $a->createdBy->id, 'name' => $a->createdBy->name],
            'created_at'  => $a->created_at->toIso8601String(),
        ])->toArray();
        $summary['assignment_history'] = $e->assignments->map(fn($a) => [
            'assigned_to' => ['id' => $a->assignedTo->id, 'name' => $a->assignedTo->name],
            'assigned_by' => ['id' => $a->assignedBy->id, 'name' => $a->assignedBy->name],
            'reason'      => $a->reason,
            'assigned_at' => $a->assigned_at->toIso8601String(),
        ])->toArray();
        return $summary;
    }
    /**
     * PATCH /api/leads/{id}/flag-shared
     *
     * FIX #8a — Marks a lead's phone number as a known shared/generic number
     * (e.g., a corporate switchboard). Once flagged, subsequent intakes for this
     * phone number bypass deduplication and create fresh engagements per unique person.
     *
     * Body: { "is_shared": true|false }
     * Roles allowed: admin, super_admin, manager, branch_head only.
     */
    public function flagShared(Request $request, int $id)
    {
        $actor = $request->user();
        $allowedRoles = ['admin', 'super_admin', 'manager', 'branch_head'];

        if (!in_array($actor->role, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to flag shared numbers.',
                'errors'  => (object)[]
            ], 403);
        }

        $request->validate(['is_shared' => 'required|boolean']);

        $engagement = LeadEngagement::with('lead')->findOrFail($id);
        $lead = $engagement->lead;

        $lead->update(['is_shared_number' => $request->boolean('is_shared')]);

        return response()->json([
            'success' => true,
            'message' => $request->boolean('is_shared')
                ? 'Phone number flagged as shared. Future intakes on this number will create separate leads.'
                : 'Phone number unflagged. Deduplication is now active again for this number.',
            'data' => [
                'lead_id'          => $lead->id,
                'phone_e164'       => $lead->phone_e164,
                'is_shared_number' => $lead->is_shared_number,
            ]
        ]);
    }
}
