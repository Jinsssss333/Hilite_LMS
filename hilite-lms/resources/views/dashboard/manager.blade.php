@extends('layouts.app')

@section('content')
<div class="max-w-[1200px] w-full mx-auto">
    <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 mb-6">
        <div>
            <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg font-bold text-on-surface mb-1">Executive Dashboard</h2>
            <p class="font-body-md text-body-md text-text-muted">High-level overview of company pipeline and team performance.</p>
        </div>
        <x-button variant="secondary">
            <span class="material-symbols-outlined" style="font-size: 18px;">download</span>
            Download Report
        </x-button>
    </header>

    <!-- Executive KPIs -->
    <div class="bg-[#E9EFE1] rounded-[20px] p-6 mb-8 flex flex-col sm:flex-row gap-6 border border-[#d2dcc8]">
        <x-kpi-widget label="Total Active Leads" value="{{ $totalActive }}" subtext="in pipeline" />
        <div class="hidden sm:block w-px bg-[#d2dcc8]"></div>
        <x-kpi-widget label="Win Rate" value="{{ $winRate }}%" subtext="avg across teams" valueColor="text-stage-booked" />
        <div class="hidden sm:block w-px bg-[#d2dcc8]"></div>
        <x-kpi-widget label="Critical SLAs" value="{{ $slaBreaches }}" subtext="breached today" valueColor="text-stage-lost" />
    </div>

    <!-- Teams Summary -->
    <h3 class="font-headline-sm text-headline-sm text-primary mb-4">Teams Performance</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($teams as $team)
        <x-card>
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-headline-sm text-on-surface">{{ $team->branch->name ?? '' }} - {{ $team->name }}</h4>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center border-b border-border-subtle pb-2">
                    <span class="text-text-muted">Total Leads</span>
                    <span class="font-bold">{{ $team->active_count + $team->closed_count }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-border-subtle pb-2">
                    <span class="text-text-muted">Conversions</span>
                    <span class="font-bold text-stage-booked">{{ $team->closed_count }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-text-muted">Active Leads</span>
                    <span class="font-bold text-primary">{{ $team->active_count }}</span>
                </div>
            </div>
        </x-card>
        @endforeach
    </div>
</div>
@endsection
