<?php

namespace App\Http\Controllers;

use App\Http\Helpers\AuthHelper;
use App\Models\LeadEngagement;
use App\Models\ReassignmentRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class ReassignmentRequestController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // POST /leads/reassign-request
    // Salesperson submits a reassignment request after hitting a duplicate.
    // ────────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'engagement_id' => 'required|integer|exists:lead_engagements,id',
            'reason'        => 'required|string|min:10|max:1000',
        ]);

        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');

        $engagement = LeadEngagement::withoutGlobalScopes()->findOrFail($request->engagement_id);
        $currentOwnerId = $engagement->assigned_user_id;
        $currentOwner   = $currentOwnerId ? User::find($currentOwnerId) : null;

        // Determine if cross-team
        $isCrossTeam = false;
        if ($currentOwner && $currentOwner->team_id && $user->team_id) {
            $isCrossTeam = $currentOwner->team_id !== $user->team_id;
        }

        // Find the requester's team lead
        $teamLead = null;
        if ($user->team_id) {
            $teamLead = User::where('team_id', $user->team_id)
                ->where('role', 'team_lead')
                ->first();
        }
        // Fallback: branch head if no team lead
        if (!$teamLead && $user->branch_id) {
            $teamLead = User::where('branch_id', $user->branch_id)
                ->where('role', 'branch_head')
                ->first();
        }

        // Check for existing pending request for same engagement by same requester
        $existing = ReassignmentRequest::where('engagement_id', $engagement->id)
            ->where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'escalated'])
            ->first();

        if ($existing) {
            return back()->with('error', 'You already have an active reassignment request for this lead. Please wait for it to be reviewed.');
        }

        ReassignmentRequest::create([
            'engagement_id'    => $engagement->id,
            'requester_id'     => $user->id,
            'current_owner_id' => $currentOwnerId,
            'reviewer_id'      => $teamLead?->id,
            'is_cross_team'    => $isCrossTeam,
            'reason'           => $request->reason,
            'status'           => 'pending',
        ]);

        return back()->with('success', 'Your reassignment request has been submitted and is now under review by your team lead.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /leads/reassign-request/{id}/review
    // Team Lead approves, denies, or escalates to Branch Head.
    // ────────────────────────────────────────────────────────────────────────
    public function review(Request $request, $id, AuditLogService $audit)
    {
        $request->validate([
            'action'         => 'required|in:approve,deny,escalate',
            'reviewer_notes' => 'nullable|string|max:500',
        ]);

        $user = AuthHelper::user();
        if (!$user || !in_array($user->role, ['team_lead', 'branch_head', 'manager', 'admin'])) {
            abort(403);
        }

        $reassignment = ReassignmentRequest::with([
            'engagement.lead',
            'requester',
            'currentOwner',
        ])->findOrFail($id);

        // Guard: only the designated reviewer can act on a pending request
        if ($reassignment->reviewer_id !== $user->id) {
            abort(403, 'You are not the designated reviewer for this request.');
        }
        if (!$reassignment->isPending()) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $now = now();

        if ($request->action === 'approve') {
            // Reassign the lead
            $engagement = $reassignment->engagement;
            $engagement->update([
                'assigned_user_id' => $reassignment->requester_id,
            ]);

            $audit->log(
                companyId:     $engagement->company_id,
                engagementId:  $engagement->id,
                actorUserId:   $user->id,
                action:        'reassignment_approved',
                before:        ['assigned_user_id' => $reassignment->current_owner_id],
                after:         ['assigned_user_id' => $reassignment->requester_id]
            );

            $reassignment->update([
                'status'         => 'approved',
                'reviewer_notes' => $request->reviewer_notes,
                'reviewed_at'    => $now,
            ]);

            return back()->with('success', 'Request approved. Lead has been reassigned to ' . $reassignment->requester->name . '.');

        } elseif ($request->action === 'deny') {
            $reassignment->update([
                'status'         => 'denied',
                'reviewer_notes' => $request->reviewer_notes,
                'reviewed_at'    => $now,
            ]);

            return back()->with('success', 'Request denied.');

        } elseif ($request->action === 'escalate') {
            // Find branch head of the requester's branch
            $branchHead = null;
            if ($reassignment->requester->branch_id) {
                $branchHead = User::where('branch_id', $reassignment->requester->branch_id)
                    ->where('role', 'branch_head')
                    ->first();
            }

            $reassignment->update([
                'status'             => 'escalated',
                'reviewer_notes'     => $request->reviewer_notes,
                'reviewed_at'        => $now,
                'branch_reviewer_id' => $branchHead?->id,
            ]);

            return back()->with('success', 'Request escalated to Branch Head for final review.');
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /leads/reassign-request/{id}/branch-review
    // Branch Head approves or denies an escalated request.
    // ────────────────────────────────────────────────────────────────────────
    public function branchReview(Request $request, $id, AuditLogService $audit)
    {
        $request->validate([
            'action'       => 'required|in:approve,deny',
            'branch_notes' => 'nullable|string|max:500',
        ]);

        $user = AuthHelper::user();
        if (!$user || !in_array($user->role, ['branch_head', 'manager', 'admin'])) {
            abort(403);
        }

        $reassignment = ReassignmentRequest::with([
            'engagement.lead',
            'requester',
        ])->findOrFail($id);

        if ($reassignment->branch_reviewer_id !== $user->id) {
            abort(403, 'You are not the designated branch reviewer for this request.');
        }
        if (!$reassignment->isEscalated()) {
            return back()->with('error', 'This request is not in an escalated state.');
        }

        $now = now();

        if ($request->action === 'approve') {
            $engagement = $reassignment->engagement;
            $engagement->update([
                'assigned_user_id' => $reassignment->requester_id,
            ]);

            $audit->log(
                companyId:     $engagement->company_id,
                engagementId:  $engagement->id,
                actorUserId:   $user->id,
                action:        'reassignment_approved_branch',
                before:        ['assigned_user_id' => $reassignment->current_owner_id],
                after:         ['assigned_user_id' => $reassignment->requester_id]
            );

            $reassignment->update([
                'status'               => 'approved',
                'branch_notes'         => $request->branch_notes,
                'branch_reviewed_at'   => $now,
            ]);

            return back()->with('success', 'Escalated request approved. Lead reassigned to ' . $reassignment->requester->name . '.');
        }

        $reassignment->update([
            'status'             => 'denied',
            'branch_notes'       => $request->branch_notes,
            'branch_reviewed_at' => $now,
        ]);

        return back()->with('success', 'Escalated request denied.');
    }
}
