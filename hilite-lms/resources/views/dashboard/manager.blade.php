@extends('layouts.app')

@section('content')
<div class="p-margin-page">

    {{-- KPI Ribbon --}}
    @php $pipelineTotal = collect($pipelineVolumes)->sum('count'); @endphp
    <div class="mt-2 mb-8 grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- KPI 1: Total Leads --}}
        <div class="bg-white rounded-2xl border border-border-subtle p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-primary/60 to-primary/10 rounded-t-2xl"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 rounded-xl bg-primary/8 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] text-primary" style="font-variation-settings:'FILL' 1">group</span>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-stage-interested/10 text-stage-interested text-[10px] font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[10px]">circle</span> Live
                </span>
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-on-surface-variant mb-1">Total Active</p>
            <p class="text-3xl font-bold text-on-surface tracking-tight">{{ number_format($totalActive) }}</p>
            <p class="text-[11px] text-on-surface-variant mt-2">{{ number_format($pipelineTotal) }} total in pipeline</p>
        </div>

        {{-- KPI 2: SLA Breached --}}
        <div class="bg-white rounded-2xl border {{ $slaBreaches > 0 ? 'border-error/20' : 'border-border-subtle' }} p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 left-0 right-0 h-0.5 {{ $slaBreaches > 0 ? 'bg-gradient-to-r from-error/70 to-error/10' : 'bg-gradient-to-r from-stage-interested/60 to-stage-interested/10' }} rounded-t-2xl"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 rounded-xl {{ $slaBreaches > 0 ? 'bg-error/8' : 'bg-stage-interested/8' }} flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] {{ $slaBreaches > 0 ? 'text-error' : 'text-stage-interested' }}" style="font-variation-settings:'FILL' 1">{{ $slaBreaches > 0 ? 'warning' : 'verified' }}</span>
                </div>
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-on-surface-variant mb-1">SLA Breached</p>
            <p class="text-3xl font-bold {{ $slaBreaches > 0 ? 'text-error' : 'text-on-surface' }} tracking-tight">{{ $slaBreaches }}</p>
            <p class="text-[11px] {{ $slaBreaches > 0 ? 'text-error/70' : 'text-on-surface-variant' }} mt-2">
                {{ $slaBreaches > 0 ? 'Leads need immediate attention' : 'All leads within SLA' }}
            </p>
        </div>

        {{-- KPI 3: Follow-ups Today --}}
        <div class="bg-white rounded-2xl border border-border-subtle p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-stage-site-visit/60 to-stage-site-visit/10 rounded-t-2xl"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 rounded-xl bg-stage-site-visit/8 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] text-stage-site-visit" style="font-variation-settings:'FILL' 1">event_available</span>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                    Today
                </span>
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-on-surface-variant mb-1">Follow-ups</p>
            <p class="text-3xl font-bold text-on-surface tracking-tight">{{ $followupsToday ?? 0 }}</p>
            <p class="text-[11px] text-on-surface-variant mt-2">
                {{ ($followupsToday ?? 0) > 0 ? 'Scheduled for today' : 'No follow-ups due today' }}
            </p>
        </div>

        {{-- KPI 4: Win Rate --}}
        <div class="bg-white rounded-2xl border border-border-subtle p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-stage-negotiation/60 to-stage-negotiation/10 rounded-t-2xl"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 rounded-xl bg-stage-negotiation/8 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] text-stage-negotiation" style="font-variation-settings:'FILL' 1">military_tech</span>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $winRate >= 15 ? 'bg-stage-interested/10 text-stage-interested' : 'bg-stage-contacted/10 text-stage-contacted' }} text-[10px] font-bold uppercase tracking-wider">
                    {{ $winRate >= 15 ? '↑ High' : '→ Growing' }}
                </span>
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-on-surface-variant mb-1">Win Rate</p>
            <p class="text-3xl font-bold text-on-surface tracking-tight">{{ $winRate }}<span class="text-lg font-semibold text-on-surface-variant">%</span></p>
            <div class="mt-2 h-1 bg-surface-container-high rounded-full overflow-hidden">
                <div class="h-full bg-stage-negotiation rounded-full transition-all" style="width: {{ min($winRate, 100) }}%"></div>
            </div>
        </div>

    </div>

    {{-- Main 12-column Grid --}}
    <div class="grid grid-cols-12 gap-8">

        {{-- ===== LEFT COLUMN (col-span-8) ===== --}}
        <div class="col-span-12 lg:col-span-8 space-y-8">

            {{-- Priority Deals Table --}}
            <div class="bg-white rounded-[20px] border border-border-subtle p-8 soft-shadow">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="font-headline-sm text-headline-sm">Priority Deals</h3>
                    <span class="text-[11px] font-label-sm text-on-surface-variant px-3 py-1 bg-[#FFF1F1] text-error rounded-full">
                        {{ $slaBreaches }} SLA Breach{{ $slaBreaches !== 1 ? 'es' : '' }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border-subtle">
                                <th class="pb-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-widest text-[10px]">Lead Name</th>
                                <th class="pb-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-widest text-[10px]">Stage</th>
                                <th class="pb-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-widest text-[10px]">Assignee</th>
                                <th class="pb-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-widest text-[10px]">Last Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            @forelse($priorityEngagements ?? [] as $eng)
                            @php
                                $stageName = $eng->stage->name ?? 'Unknown';
                                $stageColor = $eng->stage->color ?? '#6366F1';
                                $assigneeName = $eng->assignedTo->name ?? '—';
                                $initials = collect(explode(' ', $assigneeName))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');
                                $lastAction = $eng->last_activity_at ? $eng->last_activity_at->diffForHumans() : 'No activity';
                            @endphp
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="py-4 pr-4">
                                    <div class="flex items-center gap-3">
                                        @if($eng->sla_breached)
                                            <span class="w-2 h-2 rounded-full bg-error flex-shrink-0"></span>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-stage-interested flex-shrink-0"></span>
                                        @endif
                                        <span class="font-label-md text-label-md text-on-surface">
                                            {{ $eng->lead->name ?? ('Lead #' . $eng->id) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 pr-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                          style="background-color: {{ $stageColor }}22; color: {{ $stageColor }};">
                                        {{ $stageName }}
                                    </span>
                                </td>
                                <td class="py-4 pr-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-primary-fixed-dim text-on-primary-fixed flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                            {{ $initials ?: '??' }}
                                        </div>
                                        <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $assigneeName }}</span>
                                    </div>
                                </td>
                                <td class="py-4 font-body-sm text-body-sm text-on-surface-variant">{{ $lastAction }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <span class="material-symbols-outlined text-[40px] text-stage-interested">check_circle</span>
                                        <p class="font-label-md text-label-md text-on-surface-variant">No priority breaches right now.</p>
                                        <a href="{{ route('leads.index') }}" class="text-primary font-label-sm text-label-sm hover:underline flex items-center gap-1">
                                            Browse all leads <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <a href="{{ route('leads.index') }}"
                   class="w-full mt-8 py-3.5 border border-border-subtle rounded-2xl font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low transition-all hover:text-on-surface block text-center">
                    View all leads
                </a>
            </div>

            {{-- Activity Timeline --}}
            <div class="bg-white rounded-[20px] border border-border-subtle p-8 soft-shadow">
                <h3 class="font-headline-sm text-headline-sm mb-8">Activity Timeline</h3>

                @php
                    $activityIconMap = [
                        'call'     => ['icon' => 'call',          'bg' => 'bg-blue-50',   'color' => 'text-blue-600'],
                        'email'    => ['icon' => 'mail',          'bg' => 'bg-purple-50', 'color' => 'text-purple-600'],
                        'followup' => ['icon' => 'event',         'bg' => 'bg-amber-50',  'color' => 'text-amber-600'],
                        'visit'    => ['icon' => 'place',         'bg' => 'bg-green-50',  'color' => 'text-green-600'],
                        'note'     => ['icon' => 'sticky_note_2', 'bg' => 'bg-gray-50',   'color' => 'text-gray-500'],
                        'default'  => ['icon' => 'radio_button_checked', 'bg' => 'bg-primary-fixed-dim', 'color' => 'text-primary'],
                    ];
                @endphp

                @forelse($recentActivities ?? [] as $activity)
                @php
                    $actType = $activity->type ?? 'default';
                    $iconMeta = $activityIconMap[$actType] ?? $activityIconMap['default'];
                    $title = ucfirst($actType) . ' — ' . ($activity->engagement->lead->name ?? 'Lead');
                    $subtitle = $activity->createdBy->name ?? 'Unknown user';
                    $time = $activity->occurred_at ? \Carbon\Carbon::parse($activity->occurred_at)->diffForHumans() : ($activity->created_at ? $activity->created_at->diffForHumans() : '');
                @endphp
                <div class="flex items-start gap-4 {{ !$loop->last ? 'pb-6 border-b border-border-subtle mb-6' : '' }}">
                    <div class="w-10 h-10 rounded-full {{ $iconMeta['bg'] }} {{ $iconMeta['color'] }} flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[18px]">{{ $iconMeta['icon'] }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-label-md text-label-md text-on-surface">{{ $title }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">by {{ $subtitle }}</p>
                        @if($activity->notes)
                            <p class="font-body-sm text-body-sm text-on-surface-variant/70 mt-1 truncate">{{ Str::limit($activity->notes, 80) }}</p>
                        @endif
                    </div>
                    <span class="font-label-sm text-label-sm text-on-surface-variant flex-shrink-0 text-[11px]">{{ $time }}</span>
                </div>
                @empty
                <div class="flex flex-col items-center gap-3 py-8">
                    <span class="material-symbols-outlined text-[40px] text-on-surface-variant/40">history</span>
                    <p class="font-body-md text-body-md text-on-surface-variant">No recent activity yet.</p>
                </div>
                @endforelse
            </div>

        </div>{{-- end left column --}}

        {{-- ===== RIGHT COLUMN (col-span-4) ===== --}}
        <div class="col-span-12 lg:col-span-4 space-y-8">

            {{-- Donut Chart Card: Leads Management --}}
            <div class="bg-white rounded-[20px] border border-border-subtle p-8 soft-shadow">
                <h3 class="font-headline-sm text-headline-sm mb-6">Pipeline Breakdown</h3>

                @php
                    $donutTotal = collect($pipelineVolumes)->sum('count');
                    $circumference = 2 * M_PI * 54; // r=54
                    $offset = 0;
                    $donutSegments = [];
                    foreach ($pipelineVolumes as $vol) {
                        $pct = $donutTotal > 0 ? ($vol['count'] / $donutTotal) : 0;
                        $dash = round($pct * $circumference, 2);
                        $gap  = round($circumference - $dash, 2);
                        $donutSegments[] = [
                            'color'  => $vol['color'],
                            'name'   => $vol['name'],
                            'count'  => $vol['count'],
                            'dash'   => $dash,
                            'gap'    => $gap,
                            'offset' => round($circumference - $offset, 2),
                            'pct'    => round($pct * 100),
                        ];
                        $offset += $dash;
                    }
                @endphp

                {{-- SVG Donut --}}
                <div class="flex items-center justify-center mb-6">
                    <div class="relative w-[140px] h-[140px]">
                        <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                            {{-- Track --}}
                            <circle cx="60" cy="60" r="54" fill="none" stroke="#F3F4F6" stroke-width="12"/>
                            @forelse($donutSegments as $seg)
                                @if($seg['dash'] > 0)
                                <circle cx="60" cy="60" r="54" fill="none"
                                        stroke="{{ $seg['color'] }}"
                                        stroke-width="12"
                                        stroke-dasharray="{{ $seg['dash'] }} {{ $seg['gap'] }}"
                                        stroke-dashoffset="{{ $seg['offset'] }}"
                                        stroke-linecap="butt"/>
                                @endif
                            @empty
                            @endforelse
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="font-headline-sm text-headline-sm text-on-surface">{{ number_format($donutTotal) }}</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant text-[10px]">Total</span>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="space-y-3">
                    @foreach($pipelineVolumes as $vol)
                    @php $pctVal = $donutTotal > 0 ? round(($vol['count'] / $donutTotal) * 100) : 0; @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $vol['color'] }};"></span>
                        <span class="flex-1 font-body-sm text-body-sm text-on-surface-variant truncate">{{ $vol['name'] }}</span>
                        <span class="font-label-sm text-label-sm text-on-surface font-semibold">{{ number_format($vol['count']) }}</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant text-[11px] w-8 text-right">{{ $pctVal }}%</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Branch / Team Leaderboard (Black Card) --}}
            <div class="bg-inverse-surface rounded-[20px] p-8 soft-shadow">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-headline-sm text-headline-sm text-on-primary">Team Leaderboard</h3>
                    <span class="material-symbols-outlined text-on-primary/60">emoji_events</span>
                </div>

                @php
                    $rankedTeams = collect($teams)->sortByDesc('closed_count')->values();
                    $medal = ['🥇', '🥈', '🥉'];
                @endphp

                <div class="space-y-4">
                    @forelse($rankedTeams as $idx => $team)
                    @php
                        $total = $team->active_count + $team->closed_count;
                        $teamWin = $total > 0 ? round(($team->closed_count / $total) * 100, 1) : 0;
                        $barWidth = $rankedTeams->first()->closed_count > 0
                            ? round(($team->closed_count / $rankedTeams->first()->closed_count) * 100)
                            : 0;
                    @endphp
                    <div class="flex items-center gap-3">
                        {{-- Rank badge --}}
                        <div class="w-7 h-7 flex items-center justify-center flex-shrink-0">
                            @if($idx < 3)
                                <span class="text-[18px] leading-none">{{ $medal[$idx] }}</span>
                            @else
                                <span class="font-label-md text-label-md text-on-primary/60 text-[12px]">#{{ $idx + 1 }}</span>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-label-md text-label-md text-on-primary truncate">{{ $team->name }}</span>
                                <span class="font-label-sm text-label-sm text-on-primary/70 text-[11px] flex-shrink-0 ml-2">{{ $teamWin }}%</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                     style="width: {{ $barWidth }}%; background-color: {{ ['#6366F1','#10B981','#F59E0B','#3B82F6','#8B5CF6'][$idx % 5] }};"></div>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0">
                            <div class="font-label-md text-label-md text-on-primary">{{ $team->closed_count }}</div>
                            <div class="font-label-sm text-label-sm text-on-primary/50 text-[10px]">closed</div>
                        </div>
                    </div>
                    @empty
                    <p class="font-body-sm text-body-sm text-on-primary/50 text-center py-4">No teams found.</p>
                    @endforelse
                </div>

                {{-- Footer summary --}}
                <div class="mt-6 pt-5 border-t border-white/10 flex justify-between items-center">
                    <div>
                        <p class="font-label-sm text-label-sm text-on-primary/50 text-[10px] uppercase tracking-wider">Global Win Rate</p>
                        <p class="font-headline-sm text-headline-sm text-on-primary">{{ $globalConversion }}%</p>
                    </div>
                    <div class="text-right">
                        <p class="font-label-sm text-label-sm text-on-primary/50 text-[10px] uppercase tracking-wider">Total Active</p>
                        <p class="font-headline-sm text-headline-sm text-on-primary">{{ number_format($totalActive) }}</p>
                    </div>
                </div>
            </div>

        </div>{{-- end right column --}}

    </div>{{-- end grid --}}

</div>{{-- end p-margin-page --}}

<style>
.soft-shadow { box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03); }
</style>

{{-- ─── Team Lead: Pending Reassignment Requests ──────────────────────────── --}}
@php $authUser = \App\Http\Helpers\AuthHelper::user(); @endphp

@if($authUser && $authUser->role === 'team_lead' && $pendingReassignments->isNotEmpty())
<div class="p-margin-page pb-10">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center">
            <span class="material-symbols-outlined text-amber-600 text-[20px]">assignment_ind</span>
        </div>
        <div>
            <h3 class="font-bold text-on-surface text-base">Reassignment Requests</h3>
            <p class="text-xs text-on-surface-variant">Submitted by your team members</p>
        </div>
        <span class="ml-auto inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
            {{ $pendingReassignments->count() }} pending
        </span>
    </div>

    <div class="space-y-4">
        @foreach($pendingReassignments as $req)
        <div class="bg-white rounded-2xl border border-border-subtle p-5 soft-shadow">
            <div class="flex flex-col md:flex-row md:items-start gap-4">
                {{-- Lead & requester info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-primary text-[18px]">person</span>
                        </div>
                        <div>
                            <p class="font-bold text-on-surface">{{ $req->engagement->lead->name ?? 'Lead #'.$req->engagement_id }}</p>
                            <p class="text-xs text-on-surface-variant">
                                Currently: <span class="font-medium text-on-surface">{{ $req->currentOwner->name ?? 'Unassigned' }}</span>
                                &nbsp;→&nbsp;
                                Requested by: <span class="font-medium text-primary">{{ $req->requester->name }}</span>
                            </p>
                        </div>
                        @if($req->is_cross_team)
                        <span class="ml-auto flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-[10px] font-bold uppercase tracking-wider">
                            <span class="material-symbols-outlined text-[10px]">warning</span>
                            Cross-team
                        </span>
                        @endif
                    </div>

                    <div class="bg-surface-container-low rounded-xl px-4 py-3 mb-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Reason</p>
                        <p class="text-sm text-on-surface leading-relaxed">{{ $req->reason }}</p>
                    </div>
                    <p class="text-xs text-on-surface-variant">Submitted {{ $req->created_at->diffForHumans() }}</p>
                </div>

                {{-- Review form --}}
                <div class="md:w-72 flex-shrink-0">
                    <form action="{{ route('leads.reassign-request.review', $req->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <textarea name="reviewer_notes" rows="2" placeholder="Optional notes to requester..."
                            class="w-full px-3 py-2.5 rounded-xl border border-border-subtle text-xs text-on-surface bg-surface-container-low focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" name="action" value="approve"
                                class="flex-1 py-2.5 bg-green-600 text-white rounded-xl text-xs font-bold hover:bg-green-700 active:scale-95 transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">check</span> Approve
                            </button>
                            <button type="submit" name="action" value="deny"
                                class="flex-1 py-2.5 bg-red-50 text-red-700 border border-red-200 rounded-xl text-xs font-bold hover:bg-red-100 active:scale-95 transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">close</span> Deny
                            </button>
                            @if($req->is_cross_team)
                            <button type="submit" name="action" value="escalate"
                                class="flex-1 py-2.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-xl text-xs font-bold hover:bg-purple-100 active:scale-95 transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">upload</span> Escalate
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ─── Branch Head: Escalated Requests ────────────────────────────────────── --}}
@if($authUser && in_array($authUser->role, ['branch_head', 'manager', 'admin']) && $escalatedReassignments->isNotEmpty())
<div class="p-margin-page pb-10">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-center">
            <span class="material-symbols-outlined text-purple-600 text-[20px]">escalator_warning</span>
        </div>
        <div>
            <h3 class="font-bold text-on-surface text-base">Escalated Cross-Team Requests</h3>
            <p class="text-xs text-on-surface-variant">Requires Branch Head review — cross-team conflicts</p>
        </div>
        <span class="ml-auto inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-xs font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
            {{ $escalatedReassignments->count() }} escalated
        </span>
    </div>

    <div class="space-y-4">
        @foreach($escalatedReassignments as $req)
        <div class="bg-white rounded-2xl border border-purple-100 p-5 soft-shadow">
            <div class="flex flex-col md:flex-row md:items-start gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 border border-purple-100">
                            <span class="material-symbols-outlined text-purple-600 text-[18px]">person</span>
                        </div>
                        <div>
                            <p class="font-bold text-on-surface">{{ $req->engagement->lead->name ?? 'Lead #'.$req->engagement_id }}</p>
                            <p class="text-xs text-on-surface-variant">
                                Owner: <span class="font-medium text-on-surface">{{ $req->currentOwner->name ?? 'Unassigned' }}</span>
                                &nbsp;·&nbsp;
                                Requester: <span class="font-medium text-primary">{{ $req->requester->name }}</span>
                            </p>
                            @if($req->reviewer)
                            <p class="text-xs text-on-surface-variant mt-0.5">Escalated by TL: <span class="font-medium">{{ $req->reviewer->name }}</span></p>
                            @endif
                        </div>
                    </div>

                    <div class="bg-purple-50 rounded-xl px-4 py-3 mb-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-purple-600 mb-1">Requester's Reason</p>
                        <p class="text-sm text-on-surface leading-relaxed">{{ $req->reason }}</p>
                    </div>
                    @if($req->reviewer_notes)
                    <div class="bg-surface-container-low rounded-xl px-4 py-2 mb-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-0.5">Team Lead's Note</p>
                        <p class="text-xs text-on-surface">{{ $req->reviewer_notes }}</p>
                    </div>
                    @endif
                    <p class="text-xs text-on-surface-variant">Submitted {{ $req->created_at->diffForHumans() }}</p>
                </div>

                <div class="md:w-64 flex-shrink-0">
                    <form action="{{ route('leads.reassign-request.branch-review', $req->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <textarea name="branch_notes" rows="2" placeholder="Your decision notes..."
                            class="w-full px-3 py-2.5 rounded-xl border border-border-subtle text-xs text-on-surface bg-surface-container-low focus:outline-none focus:ring-2 focus:ring-purple-200 focus:border-purple-400 transition-all resize-none"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" name="action" value="approve"
                                class="flex-1 py-2.5 bg-green-600 text-white rounded-xl text-xs font-bold hover:bg-green-700 active:scale-95 transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">check</span> Approve
                            </button>
                            <button type="submit" name="action" value="deny"
                                class="flex-1 py-2.5 bg-red-50 text-red-700 border border-red-200 rounded-xl text-xs font-bold hover:bg-red-100 active:scale-95 transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">close</span> Deny
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

