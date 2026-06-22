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
        $leadsByStage = LeadEngagement::with(['lead', 'activities' => function($q) {
                $q->latest()->limit(1);
            }])
            ->where('assigned_user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->groupBy('stage_id');

        // --- NEW DATA FOR STITCH DASHBOARD ---

        // 1. Total Leads & Trend
        $totalLeads = LeadEngagement::where('assigned_user_id', $user->id)->count();
        $totalLeadsLastMonth = LeadEngagement::where('assigned_user_id', $user->id)
            ->where('created_at', '<', now()->subMonth())->count();
        $totalLeadsTrend = $totalLeadsLastMonth > 0 ? round((($totalLeads - $totalLeadsLastMonth) / $totalLeadsLastMonth) * 100) : 0;

        // 2. Conversion Rate
        $startOfQuarter = now()->startOfQuarter();
        $assignedThisQuarter = LeadEngagement::where('assigned_user_id', $user->id)
            ->where('created_at', '>=', $startOfQuarter)->count();
        $closedStages = PipelineStage::where('is_closed', true)->pluck('id')->toArray();
        if(empty($closedStages)) {
             $lastStage = PipelineStage::orderByDesc('order')->first();
             $closedStages = $lastStage ? [$lastStage->id] : [];
        }
        $closedThisQuarter = LeadEngagement::where('assigned_user_id', $user->id)
            ->where('created_at', '>=', $startOfQuarter)
            ->whereIn('stage_id', $closedStages)
            ->count();
        $conversionRate = $assignedThisQuarter > 0 ? round(($closedThisQuarter / $assignedThisQuarter) * 100) : 0;

        // 3. Avg Close Time
        $closedEngagements = LeadEngagement::where('assigned_user_id', $user->id)
            ->whereIn('stage_id', $closedStages)
            ->get();
        $totalDays = 0;
        foreach($closedEngagements as $eng) {
            $totalDays += $eng->created_at->diffInDays($eng->updated_at);
        }
        $avgCloseTime = $closedEngagements->count() > 0 ? round($totalDays / $closedEngagements->count()) : 0;

        // 4. Activity Trends (7 Days)
        $activityTrends = [];
        $maxActivity = 0;
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = Activity::whereHas('engagement', function($q) use ($user) {
                $q->where('assigned_user_id', $user->id);
            })->whereDate('created_at', $date)->count();
            $activityTrends[] = ['day' => now()->subDays($i)->format('D'), 'count' => $count];
            if ($count > $maxActivity) $maxActivity = $count;
        }
        foreach($activityTrends as &$trend) {
            $trend['percent'] = $maxActivity > 0 ? round(($trend['count'] / $maxActivity) * 100) : 5; // min 5% for visual
        }

        // 5. Lead Distribution
        $leadDistribution = [];
        $colors = ['#6366F1', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6'];
        $colorIdx = 0;
        foreach($stages as $stage) {
            $count = LeadEngagement::where('assigned_user_id', $user->id)->where('status', 'active')->where('stage_id', $stage->id)->count();
            $percent = $activeLeads > 0 ? round(($count / $activeLeads) * 100) : 0;
            if ($count > 0) {
                $leadDistribution[] = [
                    'name' => $stage->name,
                    'count' => $count,
                    'percent' => $percent,
                    'color' => $colors[$colorIdx % count($colors)]
                ];
            }
            $colorIdx++;
        }

        // 6. Priority Engagements (Overdue or SLA Breached)
        $priorityEngagements = LeadEngagement::with(['lead', 'stage', 'activities' => function($q) {
                $q->latest()->limit(1);
            }])
            ->where('assigned_user_id', $user->id)
            ->where('status', 'active')
            ->where(function($q) {
                $q->where('sla_breached', true)
                  ->orWhereHas('activities', function($sq) {
                      $sq->where('type', 'followup')->where('follow_up_at', '<', now());
                  });
            })
            ->take(5)
            ->get();

        return view('dashboard.salesperson', compact(
            'activeLeads', 'pendingFollowups', 'slaBreaches', 'stages', 'leadsByStage',
            'conversionRate', 'totalLeads', 'totalLeadsTrend', 'avgCloseTime',
            'activityTrends', 'leadDistribution', 'priorityEngagements'
        ));
    }

    public function manager()
    {
        $user = AuthHelper::user();
        if (!$user) {
            return redirect()->route('login');
        }
        
        $query = LeadEngagement::query();
        
        // If branch head, scope to branch
        if ($user->role === 'branch_head') {
            $query->where('assigned_branch_id', $user->branch_id);
        }
        
        $totalActive = (clone $query)->where('status', 'active')->count();
        
        $closedStages = PipelineStage::where('is_closed', true)->pluck('id');
        $totalClosed = (clone $query)->whereIn('stage_id', $closedStages)->count();
        $winRate = $totalActive + $totalClosed > 0 ? round(($totalClosed / ($totalActive + $totalClosed)) * 100, 1) : 0;
        
        $slaBreaches = (clone $query)->where('status', 'active')->where('sla_breached', true)->count();
        
        $teamsQuery = Team::withCount([
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
