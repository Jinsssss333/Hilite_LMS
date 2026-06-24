@extends('layouts.app')

@section('content')
<!-- Hero Stats Area -->
<div class="bg-panel-accent w-full p-8 md:p-10 mb-8 rounded-b-[32px] md:rounded-b-[48px] shadow-sm">
<div class="max-w-7xl mx-auto">
<div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
<div>
<h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary mb-2">Q3 Performance Overview</h2>
<p class="font-body-md text-body-md text-on-surface-variant">Global pipeline health and revenue projection.</p>
</div>
<div class="flex gap-2">
<button class="bg-surface border border-border-subtle text-primary font-label-md text-label-md px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-[16px]">calendar_today</span>
               Q3 2024
             </button>
<button class="bg-primary text-on-primary font-label-md text-label-md px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-on-surface-variant transition-colors">
<span class="material-symbols-outlined text-[16px]">download</span>
               Export
             </button>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<!-- Regional Revenue Forecast -->
<div class="bg-surface rounded-xl p-6 border border-border-subtle relative overflow-hidden group">
<div class="flex justify-between items-start mb-4">
<div>
<p class="font-label-sm text-label-sm text-text-muted uppercase tracking-wider mb-1">Projected Revenue</p>
<h3 class="font-headline-md text-headline-md text-primary">${{ $projectedRevenue }}M</h3>
</div>
<div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container">
<span class="material-symbols-outlined text-[20px]">trending_up</span>
</div>
</div>
<div class="h-16 flex items-end gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
<div class="w-full bg-surface-container-high h-[30%] rounded-t-sm relative"><div class="absolute -top-6 left-1/2 -translate-x-1/2 font-label-sm text-[10px] text-text-muted opacity-0 group-hover:opacity-100 transition-opacity">Jul</div></div>
<div class="w-full bg-surface-container-high h-[45%] rounded-t-sm relative"><div class="absolute -top-6 left-1/2 -translate-x-1/2 font-label-sm text-[10px] text-text-muted opacity-0 group-hover:opacity-100 transition-opacity">Aug</div></div>
<div class="w-full bg-surface-container-high h-[60%] rounded-t-sm relative"><div class="absolute -top-6 left-1/2 -translate-x-1/2 font-label-sm text-[10px] text-text-muted opacity-0 group-hover:opacity-100 transition-opacity">Sep</div></div>
<div class="w-full bg-primary h-[85%] rounded-t-sm relative"><div class="absolute -top-6 left-1/2 -translate-x-1/2 font-label-sm text-[10px] text-primary font-bold opacity-0 group-hover:opacity-100 transition-opacity">Oct</div></div>
<div class="w-full bg-surface-container-high h-[50%] rounded-t-sm relative border border-dashed border-border-subtle bg-transparent"></div>
</div>
<div class="mt-4 flex items-center gap-2 text-stage-booked font-label-sm text-label-sm">
<span class="material-symbols-outlined text-[14px]">arrow_upward</span>
<span class="">{{ $revenueGrowth }}% vs last quarter</span>
</div>
</div>
<!-- Global Conversion -->
<div class="bg-surface rounded-xl p-6 border border-border-subtle flex flex-col justify-between">
<div class="flex justify-between items-start">
<div>
<p class="font-label-sm text-label-sm text-text-muted uppercase tracking-wider mb-1">Global Conversion</p>
<h3 class="font-headline-md text-headline-md text-primary">{{ $globalConversion }}%</h3>
</div>
<div class="relative w-12 h-12">
<svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
<path class="text-surface-container-high" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3"></path>
<path class="text-primary" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="{{ min($globalConversion, 100) }}, 100" stroke-linecap="round" stroke-width="3"></path>
</svg>
</div>
</div>
<div class="mt-6 flex justify-between items-center pt-4 border-t border-border-subtle">
<div class="text-center">
<div class="font-body-sm text-body-sm text-text-muted">Avg Cycle</div>
<div class="font-label-md text-label-md text-primary mt-1">{{ $avgCycle }} Days</div>
</div>
<div class="w-px h-8 bg-border-subtle"></div>
<div class="text-center">
<div class="font-body-sm text-body-sm text-text-muted">Velocity</div>
<div class="font-label-md text-label-md text-stage-booked mt-1">+{{ $velocity }}x</div>
</div>
</div>
</div>
<!-- Top Performing Branch -->
<div class="bg-surface rounded-xl p-6 border border-border-subtle relative overflow-hidden">
<div class="absolute right-0 top-0 w-32 h-32 bg-secondary-container opacity-20 rounded-bl-full -z-0"></div>
<div class="relative z-10">
<div class="flex justify-between items-start mb-4">
<div>
<p class="font-label-sm text-label-sm text-text-muted uppercase tracking-wider mb-1">Top Branch</p>
<h3 class="font-headline-md text-headline-md text-primary">{{ $topBranchName }}</h3>
</div>
<div class="w-10 h-10 rounded-full border border-border-subtle bg-surface flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">emoji_events</span>
</div>
</div>
<div class="space-y-3 mt-6">
<div class="flex justify-between items-center font-body-sm text-body-sm">
<span class="text-text-muted">Target Attainment</span>
<span class="font-semibold text-primary">{{ $topBranchAttainment }}%</span>
</div>
<div class="w-full bg-surface-container-high rounded-full h-1.5">
<div class="bg-primary h-1.5 rounded-full" style="width: {{ min($topBranchAttainment, 100) }}%"></div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Pipeline Macro View -->
<div class="max-w-7xl mx-auto px-6 md:px-10 mb-12">
<div class="flex justify-between items-center mb-6">
<h3 class="font-headline-sm text-headline-sm text-primary">Macro Pipeline Volume</h3>
<a href="{{ route('leads.index') }}" class="text-primary font-label-md text-label-md flex items-center gap-1 hover:text-on-surface-variant transition-colors">
          View Detail <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
</a>
</div>
<div class="kanban-scroll overflow-x-auto pb-4">
<div class="flex gap-4 min-w-max">
@foreach($pipelineVolumes as $vol)
<div class="w-[200px] {{ $vol['is_closed'] ? 'bg-inverse-surface text-on-primary shadow-md transform -translate-y-1' : 'bg-surface border border-border-subtle hover:border-outline-variant transition-colors' }} rounded-xl p-4 flex flex-col cursor-pointer group">
<div class="flex items-center gap-2 mb-4">
<div class="w-3 h-3 rounded-full" style="background-color: {{ $vol['color'] }}; {{ $vol['is_closed'] ? 'box-shadow: 0 0 8px '.$vol['color'].'99;' : '' }}"></div>
<span class="font-label-md text-label-md {{ $vol['is_closed'] ? 'uppercase tracking-wider' : 'text-primary uppercase tracking-wider' }}">{{ $vol['name'] }}</span>
</div>
<div class="mt-auto">
<div class="font-headline-md text-headline-md {{ $vol['is_closed'] ? '' : 'text-primary group-hover:text-['.$vol['color'].'] transition-colors' }}" {{ !$vol['is_closed'] ? 'style=group-hover:color:'.$vol['color'] : '' }}>{{ number_format($vol['count']) }}</div>
<div class="font-body-sm text-body-sm {{ $vol['is_closed'] ? 'text-tertiary-fixed-dim' : 'text-text-muted' }} mt-1">Vol: ${{ $vol['volume'] }}M</div>
</div>
</div>
@endforeach
</div>
</div>
</div>

<!-- Departmental Rankings Grid -->
<div class="max-w-7xl mx-auto px-6 md:px-10 mb-12">
<div class="bg-surface border border-border-subtle rounded-2xl overflow-hidden">
<div class="p-6 border-b border-border-subtle flex justify-between items-center bg-surface-container-low">
<h3 class="font-headline-sm text-headline-sm text-primary">Departmental Rankings</h3>
<div class="flex gap-2">
<span class="font-label-sm text-label-sm px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container">By Volume</span>
</div>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="border-b border-border-subtle font-label-md text-label-md text-text-muted uppercase tracking-wider">
<th class="p-4 pl-6 font-medium">Department</th>
<th class="p-4 font-medium">Manager</th>
<th class="p-4 font-medium text-right">Active Leads</th>
<th class="p-4 font-medium text-right">Win Rate</th>
<th class="p-4 pr-6 font-medium text-right">YTD Revenue</th>
</tr>
</thead>
<tbody class="font-body-md text-body-md text-primary divide-y divide-border-subtle">
@foreach($teams as $index => $team)
@php
    $win = ($team->active_count + $team->closed_count > 0) ? round(($team->closed_count / ($team->active_count + $team->closed_count)) * 100, 1) : 0;
    $ytd = round($team->closed_count * 0.05, 1);
    $bgClass = ['bg-primary-fixed-dim text-on-primary-fixed', 'bg-secondary-container text-on-secondary-container', 'bg-surface-container-high text-on-surface'][$index % 3];
@endphp
<tr class="hover:bg-surface-container-low transition-colors">
<td class="p-4 pl-6 font-semibold flex items-center gap-3">
<div class="w-8 h-8 rounded {{ $bgClass }} flex items-center justify-center font-bold text-xs">{{ strtoupper(substr($team->name, 0, 2)) }}</div>
                  {{ $team->branch->name ?? '' }} - {{ $team->name }}
                </td>
<td class="p-4 text-on-surface-variant">Manager</td>
<td class="p-4 text-right">{{ $team->active_count }}</td>
<td class="p-4 text-right {{ $win > 10 ? 'text-stage-booked' : 'text-text-muted' }}">{{ $win }}%</td>
<td class="p-4 pr-6 text-right font-medium">${{ $ytd }}M</td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>
</div>
@endsection
