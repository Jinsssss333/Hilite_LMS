<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadEngagement;
use App\Models\PipelineStage;
use App\Services\PhoneNormalizationService;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class LeadIntakeService
{
    public function __construct(
        protected PhoneNormalizationService $phoneService,
        protected AuditLogService $auditService,
    ) {}

    /**
     * Main entry point — called by manual form, CSV queue job, webhook queue job.
     * Returns ['engagement' => LeadEngagement, 'is_duplicate' => bool, 'conflict' => bool]
     *
     * FIX #8c — Race Condition:
     *   The entire lead resolution is wrapped in a DB::transaction() with a
     *   pessimistic lockForUpdate() on the leads table row. This prevents two
     *   simultaneous requests (double-click, duplicate webhook) from each seeing
     *   "no record exists" at the same millisecond and both creating a new master lead.
     *
     * FIX #8a — Generic/Shared Phone Number Pollution:
     *   After resolving the master lead, we check the is_shared_number flag.
     *   If TRUE, it means this phone (e.g., a corporate switchboard) is known
     *   to be used by multiple distinct people. In that case, we SKIP the normal
     *   duplicate-check and always create a fresh engagement, preserving each
     *   unique person's data separately.
     */
    public function intake(array $data, int $companyId, int $actorUserId): array
    {
        $phone = $this->phoneService->normalize($data['phone'] ?? '');

        if (!$phone) {
            throw new \InvalidArgumentException('Invalid phone number: ' . ($data['phone'] ?? ''));
        }

        return DB::transaction(function () use ($data, $phone, $companyId, $actorUserId) {

            // ---------------------------------------------------------------
            // FIX #8c: Pessimistic lock — prevent race condition on simultaneous
            // inserts for the same phone number. The SELECT FOR UPDATE forces all
            // parallel requests to queue behind the first one that acquires the lock.
            // ---------------------------------------------------------------
            $lead = Lead::where('phone_e164', $phone)->lockForUpdate()->first();

            if (!$lead) {
                $lead = Lead::create([
                    'phone_e164'       => $phone,
                    'name'             => $data['name'],
                    'email'            => $data['email'] ?? null,
                    'status'           => 'active',
                    'is_shared_number' => false,
                ]);
            }

            // ---------------------------------------------------------------
            // FIX #8a: Shared Number Guard
            // If this phone is flagged as a known shared/generic number
            // (e.g., info@company.com, a switchboard), skip deduplication entirely.
            // Each intake creates a FRESH engagement to preserve unique buyer intent.
            // The is_shared_number flag is set via the PATCH /api/leads/{id}/flag-shared endpoint.
            // ---------------------------------------------------------------
            if (!$lead->is_shared_number) {
                // Normal flow: check if this company already has an engagement for this lead
                $existing = LeadEngagement::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('lead_id', $lead->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $conflict = $existing->assigned_user_id
                        && $existing->assigned_user_id !== $actorUserId
                        && $existing->status === 'active';

                    $this->auditService->log(
                        companyId: $companyId,
                        engagementId: $existing->id,
                        actorUserId: $actorUserId,
                        action: 'duplicate_attached',
                        before: null,
                        after: ['phone' => $phone]
                    );

                    return [
                        'engagement'   => $existing->load(['lead', 'stage', 'assignedTo']),
                        'is_duplicate' => true,
                        'conflict'     => $conflict,
                    ];
                }
            }

            // Either a new lead, or a shared-number bypass — create fresh engagement
            $defaultStage = PipelineStage::where('company_id', $companyId)
                ->orderBy('order')
                ->first();

            $assignment = $this->autoAssign($companyId, clone $lead);
            $assignedUserId   = $data['assigned_user_id']   ?? $assignment['user_id']   ?? null;
            $assignedTeamId   = $data['assigned_team_id']   ?? $assignment['team_id']   ?? null;
            $assignedBranchId = $data['assigned_branch_id'] ?? $assignment['branch_id'] ?? null;

            // If a user is given but no team/branch, try to backfill
            if ($assignedUserId && (!$assignedTeamId || !$assignedBranchId)) {
                $userObj = \App\Models\User::find($assignedUserId);
                if ($userObj) {
                    $assignedTeamId   = $assignedTeamId   ?? $userObj->team_id;
                    $assignedBranchId = $assignedBranchId ?? $userObj->branch_id;
                }
            }

            $engagement = LeadEngagement::create([
                'company_id'         => $companyId,
                'lead_id'            => $lead->id,
                'assigned_branch_id' => $assignedBranchId,
                'assigned_team_id'   => $assignedTeamId,
                'assigned_user_id'   => $assignedUserId,
                'stage_id'           => $defaultStage->id,
                'source'             => $data['source'] ?? 'manual',
                'status'             => 'active',
            ]);

            // Create SCD2 Ownership Assignment record
            $level    = $assignedUserId ? 'sp' : ($assignedTeamId ? 'team' : ($assignedBranchId ? 'branch' : null));
            $targetId = $assignedUserId ?? $assignedTeamId ?? $assignedBranchId;

            if ($level && $targetId) {
                \App\Models\OwnershipAssignment::create([
                    'engagement_id'       => $engagement->id,
                    'level'               => $level,
                    'target_id'           => $targetId,
                    'assigned_to_user_id' => $assignedUserId,
                    'assigned_by_user_id' => $actorUserId,
                    'reason'              => 'Auto-assignment on intake',
                    'assigned_at'         => now(),
                    'valid_from'          => now(),
                    'valid_to'            => null,
                ]);
            }

            $this->auditService->log(
                companyId: $companyId,
                engagementId: $engagement->id,
                actorUserId: $actorUserId,
                action: 'lead_created',
                before: null,
                after: [
                    'phone'            => $phone,
                    'name'             => $lead->name,
                    'source'           => $engagement->source,
                    'is_shared_number' => $lead->is_shared_number,
                ]
            );

            return [
                'engagement'   => $engagement->load(['lead', 'stage', 'assignedTo']),
                'is_duplicate' => false,
                'conflict'     => false,
            ];
        });
    }

    private const fallbackMaxLeads = 10;

    /**
     * Auto-assign lead using routing policies, falling back to basic capacity check.
     */
    private function autoAssign(int $companyId, Lead $lead): array
    {
        $policy = \App\Models\RoutingPolicy::where('company_id', $companyId)->first();

        if (!$policy) {
            $spId = $this->fallbackCapacityAssignment($companyId);
            return ['user_id' => $spId, 'team_id' => null, 'branch_id' => null];
        }

        $targets = $policy->targets ?? [];
        if (empty($targets)) {
            return ['user_id' => null, 'team_id' => null, 'branch_id' => null];
        }

        if ($policy->mode === 'round_robin') {
            $cursor = $policy->round_robin_cursor;
            if (!isset($targets[$cursor])) {
                $cursor = 0;
            }
            $selected = $targets[$cursor];

            $policy->update(['round_robin_cursor' => ($cursor + 1) % count($targets)]);

            return $this->buildAssignmentArray($selected['target_type'], $selected['target_id']);
        }

        return ['user_id' => null, 'team_id' => null, 'branch_id' => null];
    }

    private function buildAssignmentArray(string $type, int $id): array
    {
        if ($type === 'user')   return ['user_id' => $id, 'team_id' => null, 'branch_id' => null];
        if ($type === 'team')   return ['user_id' => null, 'team_id' => $id, 'branch_id' => null];
        if ($type === 'branch') return ['user_id' => null, 'team_id' => null, 'branch_id' => $id];
        return ['user_id' => null, 'team_id' => null, 'branch_id' => null];
    }

    private function fallbackCapacityAssignment(int $companyId): ?int
    {
        $salespersons = \App\Models\User::where('company_id', $companyId)
            ->where('role', 'salesperson')
            ->where('is_active', true)
            ->pluck('id');

        if ($salespersons->isEmpty()) return null;

        $loadCounts = LeadEngagement::where('company_id', $companyId)
            ->whereIn('assigned_user_id', $salespersons)
            ->where('status', 'active')
            ->select('assigned_user_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('assigned_user_id')
            ->pluck('total', 'assigned_user_id')
            ->toArray();

        $leastLoadedId = null;
        $minLoad       = PHP_INT_MAX;

        foreach ($salespersons as $spId) {
            $load = $loadCounts[$spId] ?? 0;
            if ($load < $minLoad) {
                $minLoad       = $load;
                $leastLoadedId = $spId;
            }
        }

        if ($minLoad < self::fallbackMaxLeads) {
            return $leastLoadedId;
        }

        return null;
    }
}
