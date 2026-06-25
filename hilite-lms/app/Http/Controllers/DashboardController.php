<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadEngagement;
use App\Models\Activity;
use App\Models\PipelineStage;
use App\Models\Team;
use App\Models\ReassignmentRequest;
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
            
        $stages = PipelineStage::where('company_id', $user->company_id)->orderBy('order')->take(5)->get();
        
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
        $closedStages = PipelineStage::where('company_id', $user->company_id)->where('is_closed', true)->pluck('id')->toArray();
        if(empty($closedStages)) {
             $lastStage = PipelineStage::where('company_id', $user->company_id)->orderByDesc('order')->first();
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

        // 4. Recent Activities (real data — last 15 logged by this user)
        $recentActivities = Activity::with(['engagement.lead', 'engagement.stage'])
            ->where('created_by_user_id', $user->id)
            ->latest()
            ->take(15)
            ->get();

        // Also keep a 7-day bar-chart array for the mini spark (uses real counts)
        $activityTrends = [];
        $maxActivity = 0;
        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i)->format('Y-m-d');
            $count = Activity::where('created_by_user_id', $user->id)
                ->whereDate('created_at', $date)
                ->count();
            $activityTrends[] = [
                'day'      => now()->subDays($i)->format('D'),
                'date'     => now()->subDays($i)->format('d M'),
                'count'    => $count,
                'is_today' => $i === 0,
            ];
            if ($count > $maxActivity) $maxActivity = $count;
        }
        foreach ($activityTrends as &$trend) {
            $trend['percent'] = $maxActivity > 0 ? max(8, round(($trend['count'] / $maxActivity) * 100)) : 8;
        }
        unset($trend);

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

        // Reassignment requests for this salesperson (their own requests)
        $myRequests = ReassignmentRequest::with(['engagement.lead', 'currentOwner'])
            ->where('requester_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('dashboard.salesperson', compact(
            'activeLeads', 'pendingFollowups', 'slaBreaches', 'stages', 'leadsByStage',
            'conversionRate', 'totalLeads', 'totalLeadsTrend', 'avgCloseTime',
            'activityTrends', 'recentActivities', 'leadDistribution', 'priorityEngagements', 'myRequests'
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
        
        $closedStages = PipelineStage::where('company_id', $user->company_id)->where('is_closed', true)->pluck('id');
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

        // NEW DATA FOR MANAGER DASHBOARD
        $projectedRevenue = 42.5; // Mock $42.5M
        $revenueGrowth = 12; // Mock +12%
        $globalConversion = $winRate;
        $avgCycle = 42; // Mock 42 Days
        $velocity = 1.2; // Mock +1.2x
        $topBranchName = 'Dubai Central';
        $topBranchAttainment = 118;

        // Macro Pipeline Volume
        $pipelineStages = PipelineStage::where('company_id', $user->company_id)->orderBy('order')->get();
        $pipelineVolumes = [];
        foreach ($pipelineStages as $stage) {
            $count = (clone $query)->where('stage_id', $stage->id)->where('status', 'active')->count();
            if ($stage->is_closed) {
                $count = (clone $query)->where('stage_id', $stage->id)->count(); // include non-active if closed
            }
            $pipelineVolumes[] = [
                'name' => $stage->name,
                'color' => $stage->color ?? '#6366F1',
                'count' => $count,
                'volume' => round($count * 0.05, 1), // Mock volume calculation in Millions
                'is_closed' => $stage->is_closed
            ];
        }

        // --- NEW QUERIES FOR STITCH REDESIGN ---

        // 1. Follow-ups Today: activities across all company leads with follow_up_at = today
        $followupsToday = Activity::whereHas('engagement', function ($q) use ($user) {
                if ($user->role === 'branch_head') {
                    $q->where('assigned_branch_id', $user->branch_id);
                }
            })
            ->whereDate('follow_up_at', today())
            ->count();

        // 2. Recent Activities: last 5 company-wide, with user, engagement, and lead names
        $recentActivities = Activity::with([
                'createdBy',
                'engagement.lead',
            ])
            ->whereHas('engagement', function ($q) use ($user) {
                if ($user->role === 'branch_head') {
                    $q->where('assigned_branch_id', $user->branch_id);
                }
            })
            ->latest()
            ->take(5)
            ->get();

        // 3. Priority Engagements: top 5 SLA-breached or overdue leads
        $priorityEngagements = LeadEngagement::with([
                'lead',
                'stage',
                'assignedTo',
            ])
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('sla_breached', true)
                  ->orWhereHas('activities', function ($sq) {
                      $sq->where('type', 'followup')->where('follow_up_at', '<', now());
                  });
            })
            ->when($user->role === 'branch_head', function ($q) use ($user) {
                $q->where('assigned_branch_id', $user->branch_id);
            })
            ->orderByDesc('sla_breached')
            ->orderBy('last_activity_at')
            ->take(5)
            ->get();

        // Reassignment requests — for team leads: pending requests they need to review
        $pendingReassignments = collect();
        $escalatedReassignments = collect();
        if ($user->role === 'team_lead') {
            $pendingReassignments = ReassignmentRequest::with([
                    'engagement.lead', 'requester', 'currentOwner'
                ])
                ->forReviewer($user->id)
                ->pending()
                ->orderByDesc('created_at')
                ->get();
        }
        // For branch heads: escalated requests routed to them
        if (in_array($user->role, ['branch_head', 'manager', 'admin'])) {
            $escalatedReassignments = ReassignmentRequest::with([
                    'engagement.lead', 'requester', 'currentOwner', 'reviewer'
                ])
                ->forBranchReviewer($user->id)
                ->escalated()
                ->orderByDesc('created_at')
                ->get();
        }

        return view('dashboard.manager', compact(
            'totalActive', 'winRate', 'slaBreaches', 'teams',
            'projectedRevenue', 'revenueGrowth', 'globalConversion',
            'avgCycle', 'velocity', 'topBranchName', 'topBranchAttainment',
            'pipelineVolumes', 'followupsToday', 'recentActivities', 'priorityEngagements',
            'pendingReassignments', 'escalatedReassignments'
        ));
    }
}
