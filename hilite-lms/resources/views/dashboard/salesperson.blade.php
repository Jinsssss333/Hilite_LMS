@extends('layouts.app')

@section('content')
<div class="p-margin-page space-y-8">

    {{-- KPI Ribbon --}}
    <div class="mb-8 grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- KPI 1: Total Leads --}}
        <div class="bg-white rounded-2xl border border-border-subtle p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-primary/60 to-primary/10 rounded-t-2xl"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 rounded-xl bg-primary/8 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] text-primary" style="font-variation-settings:'FILL' 1">group</span>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $totalLeadsTrend >= 0 ? 'bg-stage-interested/10 text-stage-interested' : 'bg-error/10 text-error' }} text-[10px] font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[10px]">{{ $totalLeadsTrend >= 0 ? 'trending_up' : 'trending_down' }}</span> {{ abs($totalLeadsTrend) }}%
                </span>
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-on-surface-variant mb-1">My Leads</p>
            <p class="text-3xl font-bold text-on-surface tracking-tight">{{ number_format($totalLeads) }}</p>
            <p class="text-[11px] text-on-surface-variant mt-2">Currently assigned to you</p>
        </div>

        {{-- KPI 2: SLA Breaches --}}
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
                {{ $slaBreaches > 0 ? 'Needs your attention' : 'You are within SLA' }}
            </p>
        </div>

        {{-- KPI 3: Follow-ups --}}
        <div class="bg-white rounded-2xl border border-border-subtle p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-stage-site-visit/60 to-stage-site-visit/10 rounded-t-2xl"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 rounded-xl bg-stage-site-visit/8 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] text-stage-site-visit" style="font-variation-settings:'FILL' 1">event_available</span>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                    This Week
                </span>
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-on-surface-variant mb-1">Follow-ups</p>
            <p class="text-3xl font-bold text-on-surface tracking-tight">{{ $pendingFollowups ?? 0 }}</p>
            <p class="text-[11px] text-on-surface-variant mt-2">
                {{ ($pendingFollowups ?? 0) > 0 ? 'Scheduled in next 7 days' : 'No upcoming follow-ups' }}
            </p>
        </div>

        {{-- KPI 4: Conversion Rate --}}
        <div class="bg-white rounded-2xl border border-border-subtle p-5 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-stage-negotiation/60 to-stage-negotiation/10 rounded-t-2xl"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 rounded-xl bg-stage-negotiation/8 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] text-stage-negotiation" style="font-variation-settings:'FILL' 1">military_tech</span>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $conversionRate >= 15 ? 'bg-stage-interested/10 text-stage-interested' : 'bg-stage-contacted/10 text-stage-contacted' }} text-[10px] font-bold uppercase tracking-wider">
                    {{ $conversionRate >= 15 ? '↑ High' : '→ Growing' }}
                </span>
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-on-surface-variant mb-1">Conversion</p>
            <p class="text-3xl font-bold text-on-surface tracking-tight">{{ $conversionRate }}<span class="text-lg font-semibold text-on-surface-variant">%</span></p>
            <div class="mt-2 h-1 bg-surface-container-high rounded-full overflow-hidden">
                <div class="h-full bg-stage-negotiation rounded-full transition-all" style="width: {{ min($conversionRate, 100) }}%"></div>
            </div>
        </div>

    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-12 gap-8">

        {{-- Left Column --}}
        <div class="col-span-12 lg:col-span-8 space-y-8">

            {{-- Priority Leads Table --}}
            <div class="bg-white rounded-[20px] border border-border-subtle p-8 soft-shadow">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="font-headline-sm text-headline-sm">Priority Leads</h3>
                    <a href="{{ route('leads.index') }}" class="flex items-center gap-1 text-label-md font-label-md text-on-surface-variant hover:text-primary transition-colors">
                        <span>View all</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border-subtle">
                                <th class="pb-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-widest text-[10px]">Lead Name</th>
                                <th class="pb-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-widest text-[10px]">Stage</th>
                                <th class="pb-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-widest text-[10px] hidden md:table-cell">Last Activity</th>
                                <th class="pb-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-widest text-[10px]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            @forelse($priorityEngagements as $eng)
                            <tr class="group hover:bg-surface-container-low/50 transition-colors cursor-pointer" onclick="window.location='{{ route('leads.show', $eng->id) }}'">
                                <td class="py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-surface-container-highest border border-border-subtle flex items-center justify-center text-[11px] font-bold text-on-surface-variant">
                                            {{ strtoupper(substr($eng->lead->name ?? 'NA', 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-body-md text-body-md font-semibold">{{ $eng->lead->name ?? 'Unknown' }}</p>
                                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $eng->lead->phone_e164 ? substr($eng->lead->phone_e164, 0, 4) . '***' : '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-5">
                                    @if($eng->stage)
                                    <span class="px-3 py-1 rounded-full text-label-sm font-label-sm" style="background-color: {{ $eng->stage->color ?? '#6366F1' }}15; color: {{ $eng->stage->color ?? '#6366F1' }}; border: 1px solid {{ $eng->stage->color ?? '#6366F1' }}30;">
                                        {{ $eng->stage->name }}
                                    </span>
                                    @else
                                    <span class="text-text-muted text-body-sm">—</span>
                                    @endif
                                </td>
                                <td class="py-5 font-body-sm text-body-sm text-on-surface-variant hidden md:table-cell">
                                    {{ $eng->last_activity_at ? \Carbon\Carbon::parse($eng->last_activity_at)->diffForHumans() : 'No activity' }}
                                </td>
                                <td class="py-5">
                                    @if($eng->sla_breached)
                                    <span class="flex items-center gap-1 text-error font-label-sm text-label-sm">
                                        <span class="material-symbols-outlined text-[14px]">warning</span> SLA Breach
                                    </span>
                                    @else
                                    <span class="flex items-center gap-1 text-stage-interested font-label-sm text-label-sm">
                                        <span class="material-symbols-outlined text-[14px]">check_circle</span> On track
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center">
                                    <span class="material-symbols-outlined text-[40px] text-text-muted block mb-2">check_circle</span>
                                    <p class="font-body-md text-body-md text-on-surface-variant">No priority issues. All leads are on track!</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($priorityEngagements->count() > 0)
                <a href="{{ route('leads.index') }}" class="mt-8 py-3.5 border border-border-subtle rounded-2xl font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low transition-all hover:text-on-surface block text-center active:scale-[0.99]">
                    View all leads
                </a>
                @endif
            </div>

            {{-- Activity Timeline --}}
            <div class="bg-white rounded-[20px] border border-border-subtle p-6 soft-shadow">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-headline-sm text-headline-sm">Activity Timeline</h3>
                    <a href="{{ route('leads.index') }}" class="text-xs text-primary font-semibold hover:underline">View All Leads →</a>
                </div>

                {{-- 7-day spark bar --}}
                <div class="mb-6">
                    <div class="flex items-end gap-1.5 h-14">
                        @foreach($activityTrends as $trend)
                        <div class="flex-1 flex flex-col items-center gap-1 group relative">
                            {{-- Tooltip --}}
                            <div class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 hidden group-hover:flex bg-on-surface text-surface text-[10px] font-semibold px-2 py-1 rounded-lg whitespace-nowrap z-10">
                                {{ $trend['date'] }}: {{ $trend['count'] }} {{ Str::plural('activity', $trend['count']) }}
                            </div>
                            <div class="w-full rounded-t-md transition-all duration-300 {{ $trend['is_today'] ? 'bg-primary' : 'bg-primary/20 group-hover:bg-primary/40' }}"
                                 style="height: {{ $trend['percent'] }}%"></div>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-1.5 mt-1.5">
                        @foreach($activityTrends as $trend)
                        <div class="flex-1 text-center text-[10px] {{ $trend['is_today'] ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
                            {{ $trend['day'] }}
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Real activity feed --}}
                <div class="space-y-1 relative">
                    <div class="absolute left-[15px] top-3 bottom-3 w-px bg-border-subtle"></div>
                    @forelse($recentActivities as $activity)
                    @php
                        $typeConfig = match($activity->type) {
                            'call'    => ['icon' => 'call',          'color' => 'text-blue-600',   'bg' => 'bg-blue-50',   'label' => 'Call'],
                            'followup'=> ['icon' => 'event',         'color' => 'text-amber-600',  'bg' => 'bg-amber-50',  'label' => 'Follow-up'],
                            'visit'   => ['icon' => 'location_on',   'color' => 'text-green-600',  'bg' => 'bg-green-50',  'label' => 'Site Visit'],
                            'note'    => ['icon' => 'sticky_note_2', 'color' => 'text-purple-600', 'bg' => 'bg-purple-50', 'label' => 'Note'],
                            default   => ['icon' => 'radio_button_checked', 'color' => 'text-on-surface-variant', 'bg' => 'bg-surface-container', 'label' => ucfirst($activity->type ?? 'Activity')],
                        };
                        $leadName  = $activity->engagement->lead->name ?? 'Unknown Lead';
                        $stageName = $activity->engagement->stage->name ?? '';
                    @endphp
                    <div class="relative pl-10 py-3 rounded-xl hover:bg-surface-container-lowest transition-colors group">
                        {{-- Icon dot --}}
                        <div class="absolute left-0 top-3 w-8 h-8 rounded-full {{ $typeConfig['bg'] }} border border-border-subtle flex items-center justify-center z-10 shadow-sm">
                            <span class="material-symbols-outlined text-[15px] {{ $typeConfig['color'] }}" style="font-variation-settings:'FILL' 1">{{ $typeConfig['icon'] }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                {{-- Lead name + stage --}}
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('leads.show', $activity->engagement->lead_id ?? 0) }}"
                                       class="font-semibold text-sm text-on-surface hover:text-primary transition-colors truncate max-w-[180px]">
                                        {{ $leadName }}
                                    </a>
                                    @if($stageName)
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-primary/8 text-primary">{{ $stageName }}</span>
                                    @endif
                                </div>
                                {{-- Activity type badge + notes --}}
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider {{ $typeConfig['color'] }}">{{ $typeConfig['label'] }}</span>
                                    @if($activity->notes)
                                    <span class="text-xs text-on-surface-variant truncate max-w-[200px]">· {{ $activity->notes }}</span>
                                    @endif
                                </div>
                                @if($activity->follow_up_at)
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="material-symbols-outlined text-[12px] text-amber-500">schedule</span>
                                    <span class="text-[11px] text-amber-600 font-semibold">Follow-up: {{ $activity->follow_up_at->format('d M, h:i A') }}</span>
                                </div>
                                @endif
                            </div>
                            <span class="text-[10px] text-on-surface-variant whitespace-nowrap flex-shrink-0 pt-0.5">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="pl-10 py-8 text-center">
                        <span class="material-symbols-outlined text-[40px] text-on-surface-variant/30 mb-2 block">history</span>
                        <p class="text-sm text-on-surface-variant font-medium">No activities yet</p>
                        <p class="text-xs text-on-surface-variant mt-1">Activities you log on leads will appear here</p>
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Right Column --}}
        <div class="col-span-12 lg:col-span-4 space-y-8">

            {{-- Leads Distribution Donut --}}
            <div class="bg-white rounded-[20px] border border-border-subtle p-8 soft-shadow">
                <h3 class="font-headline-sm text-headline-sm mb-8">My Lead Pipeline</h3>
                <div class="relative flex justify-center py-4">
                    @php
                        $colors = ['#6366F1','#F59E0B','#10B981','#22C55E','#8B5CF6','#3B82F6'];
                        $total = array_sum(array_column($leadDistribution, 'count'));
                        $offset = 0;
                    @endphp
                    <svg class="w-52 h-52 -rotate-90" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" fill="transparent" r="16" stroke="#f1eded" stroke-width="2.5"></circle>
                        @foreach($leadDistribution as $i => $dist)
                            @php $da = $total > 0 ? round(($dist['count'] / $total) * 100) : 0; @endphp
                            <circle cx="18" cy="18" fill="transparent" r="16"
                                stroke="{{ $colors[$i % count($colors)] }}"
                                stroke-dasharray="{{ $da }} 100"
                                stroke-dashoffset="-{{ $offset }}"
                                stroke-linecap="round"
                                stroke-width="3">
                            </circle>
                            @php $offset += $da; @endphp
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <p class="font-headline-md text-headline-md">{{ number_format($activeLeads) }}</p>
                        <p class="font-label-sm text-label-sm text-on-surface-variant">Active Leads</p>
                    </div>
                </div>
                <div class="mt-8 space-y-4">
                    @forelse($leadDistribution as $i => $dist)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $colors[$i % count($colors)] }}"></div>
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $dist['name'] }}</span>
                        </div>
                        <span class="font-body-sm text-body-sm font-bold">{{ $dist['count'] }} ({{ $dist['percent'] }}%)</span>
                    </div>
                    @empty
                    <p class="text-body-sm text-on-surface-variant text-center py-4">No leads assigned yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Quick Stats Card --}}
            <div class="bg-primary text-on-primary rounded-[20px] p-8 soft-shadow relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="font-headline-sm text-headline-sm text-white mb-2">Your Performance</h3>
                    <p class="font-body-sm text-body-sm text-primary-fixed-dim/80 mb-8">
                        {{ $conversionRate >= 15 ? 'Great work! You are above target conversion rate.' : 'Keep pushing — your conversion rate is growing.' }}
                    </p>
                    <div class="space-y-4">
                        <div class="bg-white/10 rounded-2xl p-4 flex justify-between items-center border border-white/10 hover:bg-white/15 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">show_chart</span>
                                </div>
                                <span class="font-label-md text-label-md">Conversion Rate</span>
                            </div>
                            <span class="font-label-md text-label-md text-stage-booked">{{ $conversionRate }}%</span>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-4 flex justify-between items-center border border-white/5 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-9 h-9 rounded-xl bg-white/5 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                                </div>
                                <span class="font-label-md text-label-md">Avg Close Time</span>
                            </div>
                            <span class="font-label-md text-label-md opacity-80">{{ $avgCloseTime }}d</span>
                        </div>
                    </div>
                    <a href="{{ route('leads.index') }}" class="w-full mt-8 py-3 bg-white text-primary rounded-full font-label-md text-label-md hover:bg-white/90 active:scale-95 transition-all shadow-lg shadow-black/10 block text-center">
                        View My Leads
                    </a>
                </div>
                <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
            </div>

        </div>
    </div>
</div>

<style>
    .soft-shadow { box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03); }
</style>

{{-- ─── My Reassignment Requests ────────────────────────────────────────────── --}}
@if($myRequests->isNotEmpty())
<div class="mt-8 px-margin-page pb-10">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center border border-amber-200">
            <span class="material-symbols-outlined text-amber-600 text-[18px]">swap_horiz</span>
        </div>
        <div>
            <h3 class="font-semibold text-on-surface text-base">My Reassignment Requests</h3>
            <p class="text-xs text-on-surface-variant">Track your lead reassignment requests</p>
        </div>
        <span class="ml-auto inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold">
            {{ $myRequests->where('status', 'pending')->count() + $myRequests->where('status', 'escalated')->count() }} active
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach($myRequests as $req)
        @php
            $statusClasses = match($req->status) {
                'pending'   => 'text-amber-700 bg-amber-50 border-amber-200',
                'escalated' => 'text-purple-700 bg-purple-50 border-purple-200',
                'approved'  => 'text-green-700 bg-green-50 border-green-200',
                'denied'    => 'text-red-700 bg-red-50 border-red-200',
            };
            $statusIcon = match($req->status) {
                'pending'   => 'pending',
                'escalated' => 'upload',
                'approved'  => 'check_circle',
                'denied'    => 'cancel',
            };
            $statusLabel = $req->statusLabel();
        @endphp
        <div class="bg-white rounded-2xl border border-border-subtle p-4 soft-shadow hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-primary text-[16px]">person</span>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-on-surface text-sm truncate">{{ $req->engagement->lead->name ?? 'Lead #'.$req->engagement_id }}</p>
                        <p class="text-xs text-on-surface-variant truncate">Was assigned to: {{ $req->currentOwner->name ?? 'Unassigned' }}</p>
                    </div>
                </div>
                <span class="flex-shrink-0 ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase tracking-wider {{ $statusClasses }}">
                    <span class="material-symbols-outlined text-[10px]">{{ $statusIcon }}</span>
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="bg-surface-container-low rounded-lg px-3 py-2 mb-3">
                <p class="text-xs text-on-surface-variant leading-relaxed line-clamp-2">{{ $req->reason }}</p>
            </div>

            @if($req->isApproved() && $req->reviewer_notes)
            <div class="flex items-start gap-2 bg-green-50 border border-green-100 rounded-lg px-3 py-2 mb-2">
                <span class="material-symbols-outlined text-green-600 text-[14px] mt-0.5">thumb_up</span>
                <p class="text-xs text-green-700 leading-relaxed">{{ $req->reviewer_notes ?: $req->branch_notes }}</p>
            </div>
            @endif

            @if($req->isDenied())
            <div class="flex items-start gap-2 bg-red-50 border border-red-100 rounded-lg px-3 py-2 mb-2">
                <span class="material-symbols-outlined text-red-600 text-[14px] mt-0.5">info</span>
                <p class="text-xs text-red-700 leading-relaxed">{{ $req->reviewer_notes ?: ($req->branch_notes ?: 'No reason provided.') }}</p>
            </div>
            @endif

            <div class="flex items-center justify-between pt-1">
                <p class="text-[10px] text-on-surface-variant">{{ $req->created_at->diffForHumans() }}</p>
                @if($req->is_cross_team)
                <span class="text-[10px] text-purple-600 font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-[10px]">warning</span>
                    Cross-team
                </span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

