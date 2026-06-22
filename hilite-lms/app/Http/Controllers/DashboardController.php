<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadEngagement;
use App\Models\Activity;
use App\Models\PipelineStage;
use App\Models\Team;
use App\Http\Helpers\AuthHelper;

class DashboardController extends Controller
{
    public function salesperson()
    {
        $user = AuthHelper::user();
        if (!$user) {
            return redirect()->route('login');
        }
        
        $activeLeads = LeadEngagement::where('assigned_user_id', $user->id)
            ->where('status', 'active')->count();
            
        $pendingFollowups = Activity::whereHas('engagement', function($q) use ($user) {
                $q->where('assigned_user_id', $user->id)->where('status', 'active');
            })
            ->where('type', 'followup')
            ->whereBetween('follow_up_at', [now()->startOfDay(), now()->addDays(7)])
            ->count();
            
        $slaBreaches = LeadEngagement::where('assigned_user_id', $user->id)
            ->where('status', 'active')
            ->where('sla_breached', true)->count();
            
        $stages = PipelineStage::orderBy('order')->take(5)->get();
        
        // Group leads by stage for the kanban/accordion
        // We also want to load the latest activity to show what was the last interaction
        $leadsByStage = LeadEngagement::with(['lead', 'activities' => function($q) {
                $q->latest()->limit(1);
            }])
            ->where('assigned_user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->groupBy('stage_id');

        // Calculate Data for Hero Strip Charts
        // 1. New Leads (last 5 days)
        $newLeadsData = [];
        $totalLast5 = 0;
        $totalPrev5 = 0;
        for ($i = 4; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = LeadEngagement::where('assigned_user_id', $user->id)
                ->whereDate('created_at', $date)
                ->count();
            $newLeadsData[] = [
                'day' => now()->subDays($i)->format('D'),
                'count' => $count
            ];
            $totalLast5 += $count;
        }
        for ($i = 9; $i >= 5; $i--) {
            $totalPrev5 += LeadEngagement::where('assigned_user_id', $user->id)
                ->whereDate('created_at', now()->subDays($i)->format('Y-m-d'))
                ->count();
        }
        $newLeadsTrend = $totalPrev5 > 0 ? round((($totalLast5 - $totalPrev5) / $totalPrev5) * 100) : 0;

        // 2. Conversion Rate (Closed Won vs Total Assigned this quarter)
        $startOfQuarter = now()->startOfQuarter();
        $assignedThisQuarter = LeadEngagement::where('assigned_user_id', $user->id)
            ->where('created_at', '>=', $startOfQuarter)->count();
        
        // Find the "closed" stage id (assume highest order is won/closed)
        $closedStages = PipelineStage::where('is_closed', true)->pluck('id')->toArray();
        if(empty($closedStages)) {
             // Fallback: take the last stage
             $lastStage = PipelineStage::orderByDesc('order')->first();
             $closedStages = $lastStage ? [$lastStage->id] : [];
        }
        
        $closedThisQuarter = LeadEngagement::where('assigned_user_id', $user->id)
            ->where('created_at', '>=', $startOfQuarter)
            ->whereIn('stage_id', $closedStages)
            ->count();
        $conversionRate = $assignedThisQuarter > 0 ? round(($closedThisQuarter / $assignedThisQuarter) * 100) : 0;

        // 3. Open Follow-ups & Overdue
        $openFollowups = Activity::whereHas('engagement', function($q) use ($user) {
                $q->where('assigned_user_id', $user->id)->where('status', 'active');
            })->where('type', 'followup')->count();
        $overdueFollowups = Activity::whereHas('engagement', function($q) use ($user) {
                $q->where('assigned_user_id', $user->id)->where('status', 'active');
            })->where('type', 'followup')->where('follow_up_at', '<', now())->count();

        // 4. Closed This Month
        $startOfMonth = now()->startOfMonth();
        $closedThisMonth = LeadEngagement::where('assigned_user_id', $user->id)
            ->where('updated_at', '>=', $startOfMonth)
            ->whereIn('stage_id', $closedStages)
            ->count();
        $closedGoal = 15;

        return view('dashboard.salesperson', compact(
            'activeLeads', 'pendingFollowups', 'slaBreaches', 'stages', 'leadsByStage',
            'newLeadsData', 'newLeadsTrend', 'conversionRate', 'openFollowups', 'overdueFollowups', 'closedThisMonth', 'closedGoal'
        ));
    }

    public function manager()
    {
        $user = AuthHelper::user();
        if (!$user) {
            return redirect()->route('login');
        }
        
        $query = LeadEngagement::where('company_id', $user->company_id);
        
        // If branch head, scope to branch
        if ($user->role === 'branch_head') {
            $query->where('assigned_branch_id', $user->branch_id);
        }
        
        $totalActive = (clone $query)->where('status', 'active')->count();
        
        $closedStages = PipelineStage::where('is_closed', true)->pluck('id');
        $totalClosed = (clone $query)->whereIn('stage_id', $closedStages)->count();
        $winRate = $totalActive + $totalClosed > 0 ? round(($totalClosed / ($totalActive + $totalClosed)) * 100, 1) : 0;
        
        $slaBreaches = (clone $query)->where('status', 'active')->where('sla_breached', true)->count();
        
        $teamsQuery = Team::where('company_id', $user->company_id)->withCount([
            'engagements as active_count' => function($q) {
                $q->where('status', 'active');
            },
            'engagements as closed_count' => function($q) use ($closedStages) {
                $q->whereIn('stage_id', $closedStages);
            }
        ]);
        
        if ($user->role === 'branch_head') {
            $teamsQuery->where('branch_id', $user->branch_id);
        }
        
        $teams = $teamsQuery->get();

        return view('dashboard.manager', compact('totalActive', 'winRate', 'slaBreaches', 'teams'));
    }
}
