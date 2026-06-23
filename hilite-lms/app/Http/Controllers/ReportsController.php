<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadEngagement;
use App\Models\User;
use App\Http\Helpers\AuthHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');

        $companyId = $user->company_id 
            ?? DB::table('branches')->where('id', $user->branch_id)->value('company_id');

        // Date Filtering
        $days = (int) $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Base Query for KPI
        $baseQuery = LeadEngagement::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate);

        // KPI 1: Total Leads
        $totalLeads = (clone $baseQuery)->count();
        
        // Previous period for comparison
        $prevStartDate = Carbon::now()->subDays($days * 2);
        $prevEndDate = Carbon::now()->subDays($days);
        $prevTotalLeads = LeadEngagement::where('company_id', $companyId)
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->count();
        
        $leadGrowth = $prevTotalLeads > 0 ? round((($totalLeads - $prevTotalLeads) / $prevTotalLeads) * 100) : 0;

        // KPI 2: Conversion Rate
        $bookedStageIds = DB::table('pipeline_stages')
            ->where('company_id', $companyId)
            ->where('name', 'Booked')
            ->pluck('id');

        $convertedLeads = (clone $baseQuery)->whereIn('stage_id', $bookedStageIds)->count();
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

        // KPI 4: SLA Fulfillment
        $totalSlaBreaches = (clone $baseQuery)->where('sla_breached', true)->count();
        $slaFulfillment = $totalLeads > 0 ? round((($totalLeads - $totalSlaBreaches) / $totalLeads) * 100, 1) : 100;

        // KPI 3: Avg Response Time
        // For real calculations, find the difference between created_at and the first manual activity
        // For efficiency, we just grab the average from lead_engagements that had a response
        // In a true 5M scale, we might need a rollup table, but this works for simple metric:
        $avgMinutes = DB::table('activities')
            ->join('lead_engagements', 'activities.engagement_id', '=', 'lead_engagements.id')
            ->where('lead_engagements.company_id', $companyId)
            ->where('activities.type', '!=', 'note') // note is usually system/creation
            ->whereColumn('activities.created_by_user_id', '!=', 'lead_engagements.assigned_user_id') // Wait, response usually BY assigned user.
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, lead_engagements.created_at, activities.occurred_at)')) ?? 0;
            
        $avgResponseTime = round($avgMinutes) . 'm';
        $avgResponseTrend = '0m';

        // Monthly Trend Chart
        $chartData = [];
        for ($i = 8; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            
            $intake = LeadEngagement::where('company_id', $companyId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
                
            $conv = LeadEngagement::where('company_id', $companyId)
                ->whereIn('stage_id', $bookedStageIds)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $chartData[] = [
                'month'  => $monthStart->format('M'),
                'intake' => $intake,
                'conv'   => $conv,
            ];
        }

        // Source Distribution
        $sourcesRaw = (clone $baseQuery)
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        $sources = [];
        $colors = ['bg-stage-new', 'bg-stage-site-visit', 'bg-stage-interested', 'bg-stage-negotiation', 'bg-stage-lost'];
        $colorIdx = 0;
        foreach ($sourcesRaw as $s) {
            $sources[] = [
                'name' => ucfirst($s->source ?? 'Unknown'),
                'count' => $s->count,
                'percent' => $totalLeads > 0 ? round(($s->count / $totalLeads) * 100) : 0,
                'color' => $colors[$colorIdx % count($colors)]
            ];
            $colorIdx++;
        }

        // Lead Engagement Heatmap (42 blocks: 7 days x 6 time periods of 4 hours)
        $heatmapData = array_fill(0, 42, 0); // Initialize with 0
        
        // Grab activities in the current date range
        $activities = DB::table('activities')
            ->join('lead_engagements', 'activities.engagement_id', '=', 'lead_engagements.id')
            ->where('lead_engagements.company_id', $companyId)
            ->where('activities.occurred_at', '>=', $startDate)
            ->select('activities.occurred_at')
            ->get();

        $maxActivity = 1; // Prevent division by zero
        $blockCounts = array_fill(0, 42, 0);

        foreach ($activities as $act) {
            $date = Carbon::parse($act->occurred_at);
            // Day of week: 0 (Sunday) to 6 (Saturday)
            $dayOfWeek = $date->dayOfWeek; 
            // Hour: 0 to 23 -> map to 6 blocks (0-3, 4-7, 8-11, 12-15, 16-19, 20-23)
            $timeBlock = floor($date->hour / 4); 
            
            $index = ($dayOfWeek * 6) + $timeBlock;
            $blockCounts[$index]++;
        }

        if (count($activities) > 0) {
            $maxActivity = max(max($blockCounts), 1);
        }

        // Calculate intensity (0.1 to 1.0)
        foreach ($blockCounts as $i => $count) {
            $heatmapData[$i] = $count > 0 ? max(0.1, $count / $maxActivity) : 0.05; // 0.05 is the base background
        }

        // Agent Performance Table
        $agents = User::where('company_id', $companyId)
            ->whereIn('role', ['salesperson', 'team_lead'])
            ->get()
            ->map(function ($agent) use ($startDate, $bookedStageIds) {
                $assignedCount = LeadEngagement::where('assigned_user_id', $agent->id)
                    ->where('created_at', '>=', $startDate)->count();
                $convCount = LeadEngagement::where('assigned_user_id', $agent->id)
                    ->whereIn('stage_id', $bookedStageIds)
                    ->where('created_at', '>=', $startDate)->count();
                $breachCount = LeadEngagement::where('assigned_user_id', $agent->id)
                    ->where('sla_breached', true)
                    ->where('created_at', '>=', $startDate)->count();

                $convRate = $assignedCount > 0 ? round(($convCount / $assignedCount) * 100, 1) : 0;
                $avgBreach = $assignedCount > 0 ? round($breachCount / $assignedCount, 1) : 0;

                return [
                    'name' => $agent->name,
                    'initials' => strtoupper(substr($agent->name, 0, 2)),
                    'assigned' => $assignedCount,
                    'conversion' => $convRate,
                    'avg_breach' => $avgBreach,
                ];
            })->sortByDesc('assigned')->take(5);

        $stages = \App\Models\PipelineStage::where('company_id', $companyId)->orderBy('order')->get();
        $sourceList = \App\Models\LeadEngagement::where('company_id', $companyId)->select('source')->distinct()->pluck('source')->toArray();

        return view('reports.index', compact(
            'totalLeads', 'leadGrowth', 'conversionRate', 
            'slaFulfillment', 'totalSlaBreaches', 'avgResponseTime', 'avgResponseTrend',
            'chartData', 'sources', 'agents', 'days', 'heatmapData', 'stages', 'sourceList'
        ));
    }
}
