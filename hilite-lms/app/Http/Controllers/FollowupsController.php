<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Http\Helpers\AuthHelper;
use Illuminate\Support\Carbon;

class FollowupsController extends Controller
{
    public function index(Request $request)
    {
        $user = AuthHelper::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $activities = Activity::with(['engagement.lead', 'engagement.stage'])
            ->whereNotNull('follow_up_at')
            ->whereHas('engagement', function ($q) use ($user) {
                if ($user->role === 'salesperson') {
                    $q->where('assigned_user_id', $user->id);
                } elseif ($user->role === 'team_lead') {
                    $teamUserIds = \App\Models\User::where('team_id', $user->team_id)->pluck('id');
                    $q->whereIn('assigned_user_id', $teamUserIds);
                } elseif ($user->role === 'branch_head') {
                    $branchUserIds = \App\Models\User::where('branch_id', $user->branch_id)->pluck('id');
                    $q->whereIn('assigned_user_id', $branchUserIds);
                } else {
                    $q->where('company_id', $user->company_id);
                }
            })
            ->orderBy('follow_up_at', 'asc')
            ->get();

        $overdue = $activities->filter(fn($a) => Carbon::parse($a->follow_up_at)->isPast() && !Carbon::parse($a->follow_up_at)->isToday());
        $today = $activities->filter(fn($a) => Carbon::parse($a->follow_up_at)->isToday());
        $upcoming = $activities->filter(fn($a) => Carbon::parse($a->follow_up_at)->isFuture() && !Carbon::parse($a->follow_up_at)->isToday());

        return view('leads.followups', compact('overdue', 'today', 'upcoming'));
    }

    public function complete($id)
    {
        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');

        $activity = Activity::with('engagement')->findOrFail($id);
        
        // Ownership check: salespersons can only complete their own activities
        if ($user->role === 'salesperson' && $activity->engagement?->assigned_user_id !== $user->id) {
            abort(403, 'You do not have permission to modify this activity.');
        }
        
        // "Complete" by clearing the scheduled follow-up
        $activity->update(['follow_up_at' => null]);
        
        return redirect()->back()->with('success', 'Follow-up marked as completed.');
    }
}
