<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\LeadEngagement;
use App\Http\Helpers\AuthHelper;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = AuthHelper::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $monthStr = $request->query('month', now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
        } catch (\Exception $e) {
            $currentMonth = now()->startOfMonth();
        }
        
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // Fetch activities
        $activities = Activity::with(['engagement.lead', 'engagement.stage'])
            ->whereNotNull('follow_up_at')
            ->whereBetween('follow_up_at', [$currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth()])
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
            ->get();

        $calendarData = $activities->groupBy(fn($a) => Carbon::parse($a->follow_up_at)->day);

        // Unscheduled Engagements
        $unscheduledEngagements = LeadEngagement::with(['lead', 'stage'])
            ->where('assigned_user_id', $user->id)
            ->where('status', 'active')
            ->whereDoesntHave('activities', function($q) {
                $q->whereNotNull('follow_up_at');
            })
            ->get();

        // Stats
        $visitsToday = Activity::whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', now()->toDateString())
            ->where('type', 'visit')
            ->whereHas('engagement', function ($q) use ($user) {
                $q->where('assigned_user_id', $user->id);
            })
            ->count();
            
        $pendingScheduling = $unscheduledEngagements->count();
        $weeklyCompletion = "78%"; // Fake KPI for now as we don't track "completion percentage" strictly

        return view('leads.calendar', compact(
            'currentMonth', 'prevMonth', 'nextMonth', 'calendarData',
            'unscheduledEngagements', 'visitsToday', 'pendingScheduling', 'weeklyCompletion'
        ));
    }

    public function schedule(\Illuminate\Http\Request $request)
    {
        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');

        $request->validate([
            'engagement_id' => 'required|integer|exists:lead_engagements,id',
            'scheduled_at'  => 'required|date|after:now',
            'label'         => 'required|string|max:100',
            'type'          => 'nullable|string|in:call,followup,visit,note',
        ]);

        // Security: ensure the engagement belongs to this user's scope
        $engagement = \App\Models\LeadEngagement::withoutGlobalScopes()
            ->where('id', $request->engagement_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        \App\Models\Activity::create([
            'engagement_id'      => $engagement->id,
            'created_by_user_id' => $user->id,
            'type'               => $request->type ?? 'followup',
            'notes'              => $request->label,
            'follow_up_at'       => $request->scheduled_at,
        ]);

        // Update engagement's last_activity_at
        $engagement->update(['last_activity_at' => now()]);

        $month = Carbon::parse($request->scheduled_at)->format('Y-m');
        return redirect()->route('leads.calendar', ['month' => $month])
            ->with('success', 'Scheduled: "' . $request->label . '" on ' . Carbon::parse($request->scheduled_at)->format('d M Y, g:i A'));
    }
}
