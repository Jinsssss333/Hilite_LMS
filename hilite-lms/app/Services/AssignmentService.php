<?php

namespace App\Services;

use App\Models\LeadEngagement;
use App\Models\OwnershipAssignment;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class AssignmentService
{
    public function __construct(protected AuditLogService $auditService) {}

    /**
     * Assigns or reassigns a lead engagement to a user.
     * Enforces role-based scope:
     *   - salesperson : cannot assign at all
     *   - team_lead   : can only assign within their own team
     *   - manager / admin / super_admin : can assign anyone in the company
     *
     * @throws \Exception on authorization failure
     */
    public function assign(
        LeadEngagement $engagement,
        int $assignToUserId,
        int $actorUserId,
        ?string $reason = null
    ): OwnershipAssignment {
        $actor    = User::findOrFail($actorUserId);
        $assignTo = User::findOrFail($assignToUserId);

        $this->validateAssignmentPermission($actor, $assignTo, $engagement);

        $previousOwnerId = $engagement->assigned_user_id;

        $engagement->update(['assigned_user_id' => $assignToUserId]);

        // Always insert a new history row — never update
        $assignment = OwnershipAssignment::create([
            'engagement_id'       => $engagement->id,
            'assigned_to_user_id' => $assignToUserId,
            'assigned_by_user_id' => $actorUserId,
            'reason'              => $reason,
            'assigned_at'         => Carbon::now(),
        ]);

        $this->auditService->log(
            companyId:    $engagement->company_id,
            engagementId: $engagement->id,
            actorUserId:  $actorUserId,
            action:       $previousOwnerId ? 'reassigned' : 'assigned',
            before:       $previousOwnerId ? ['assigned_user_id' => $previousOwnerId] : null,
            after:        ['assigned_user_id' => $assignToUserId, 'reason' => $reason],
        );

        return $assignment->load(['assignedTo', 'assignedBy']);
    }

    /**
     * Returns users the actor is permitted to assign leads to.
     * Salespersons get an empty collection (they cannot assign).
     */
    public function getAssignableUsers(User $actor): Collection
    {
        $query = User::where('company_id', $actor->company_id)
            ->where('is_active', true)
            ->where('role', 'salesperson');

        if (in_array($actor->role, ['admin', 'super_admin', 'manager', 'branch_head'])) {
            return $query->get();
        }

        if ($actor->role === 'team_lead') {
            return $query->where('team_id', $actor->team_id)->get();
        }

        // salesperson — cannot assign
        return User::whereRaw('1 = 0')->get(); // empty Eloquent Collection
    }

    /**
     * Throws \Exception if the actor is not allowed to assign to the target.
     */
    private function validateAssignmentPermission(
        User $actor,
        User $assignTo,
        LeadEngagement $engagement
    ): void {
        if ($actor->role === 'salesperson') {
            throw new \Exception('Salespersons cannot reassign leads.');
        }

        if ($actor->role === 'team_lead') {
            if ($assignTo->team_id !== $actor->team_id) {
                throw new \Exception('You can only assign leads within your team.');
            }
            return;
        }

        // manager, branch_head, admin, super_admin — same company required
        if ($assignTo->company_id !== $actor->company_id) {
            throw new \Exception('Cannot assign leads to a user from another company.');
        }
    }
}
