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
     * Role priority map — higher number = higher authority.
     * A role can only override an assignment made by a role with equal or lower priority.
     */
    private const ROLE_PRIORITY = [
        'salesperson'  => 0,
        'team_lead'    => 1,
        'manager'      => 2,
        'branch_head'  => 3,
        'admin'        => 4,
        'super_admin'  => 5,
    ];

    /**
     * Throws \Exception if the actor is not allowed to assign to the target.
     *
     * Checks:
     *  1. Salespersons cannot assign at all.
     *  2. Team leads can only assign within their own team.
     *  3. Company-wide roles require same company.
     *  4. Role-priority check: if a previous assignment was made by a
     *     higher-ranked role, the current actor cannot override it.
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
        }

        // manager, branch_head, admin, super_admin — same company required
        if ($actor->role !== 'team_lead' && $assignTo->company_id !== $actor->company_id) {
            throw new \Exception('Cannot assign leads to a user from another company.');
        }

        // --- Role-priority check on reassignments ---
        // If the lead already has an owner, check who made the last assignment
        if ($engagement->assigned_user_id) {
            $lastAssignment = OwnershipAssignment::where('engagement_id', $engagement->id)
                ->orderByDesc('assigned_at')
                ->first();

            if ($lastAssignment) {
                $previousAssigner = User::find($lastAssignment->assigned_by_user_id);

                if ($previousAssigner) {
                    $actorPriority    = self::ROLE_PRIORITY[$actor->role] ?? 0;
                    $previousPriority = self::ROLE_PRIORITY[$previousAssigner->role] ?? 0;

                    if ($actorPriority < $previousPriority) {
                        $previousRoleLabel = ucwords(str_replace('_', ' ', $previousAssigner->role));
                        throw new \Exception(
                            "This lead was assigned by a {$previousRoleLabel} ({$previousAssigner->name}). "
                            . "Your role does not have sufficient priority to override this assignment. "
                            . "Please contact a {$previousRoleLabel} or higher to reassign."
                        );
                    }
                }
            }
        }
    }
}
