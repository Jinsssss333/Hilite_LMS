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
            
        $stages = PipelineStage::orderBy('order')->get();
        
        // Group leads by stage for the kanban/accordion
        // We also want to load the latest activity to show what was the last interaction
        $leadsByStage = LeadEngagement::with(['lead', 'activities' => function($q) {
                $q->latest()->limit(1);
            }])
            ->where('assigned_user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->groupBy('stage_id');

        return view('dashboard.salesperson', compact('activeLeads', 'pendingFollowups', 'slaBreaches', 'stages', 'leadsByStage'));
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
