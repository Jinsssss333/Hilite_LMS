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

    <!-- Stitch Hero Stats Area -->
    <section class="bg-[#E9EFE1] rounded-[20px] p-8 mb-8 flex flex-col md:flex-row justify-between gap-8 border border-[#d2dcc8]">
        <div class="flex-1">
            <h2 class="font-headline-lg text-headline-lg text-primary mb-2">Morning, {{ \App\Http\Helpers\AuthHelper::user()->name ?? 'Manager' }}.</h2>
            <p class="font-body-lg text-body-lg text-secondary max-w-lg">Here is the high-level overview of your teams' pipeline and performance.</p>
        </div>
        <div class="flex flex-wrap gap-4">
            <div class="bg-white rounded-xl p-6 w-48 border border-border-subtle shadow-sm">
                <p class="font-label-sm text-label-sm text-text-muted mb-1">TOTAL ACTIVE</p>
                <p class="font-headline-md text-headline-md text-primary">{{ number_format($totalActive) }}</p>
                <div class="flex items-center gap-1 text-stage-booked mt-2">
                    <span class="material-symbols-outlined text-[16px]">groups</span>
                    <span class="font-label-sm text-label-sm">in pipeline</span>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 w-48 border border-border-subtle shadow-sm">
                <p class="font-label-sm text-label-sm text-text-muted mb-1">WIN RATE</p>
                <p class="font-headline-md text-headline-md text-primary">{{ $winRate }}%</p>
                <div class="flex items-center gap-1 text-stage-booked mt-2">
                    <span class="material-symbols-outlined text-[16px]">emoji_events</span>
                    <span class="font-label-sm text-label-sm">avg across teams</span>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 w-48 border border-border-subtle shadow-sm">
                <p class="font-label-sm text-label-sm text-text-muted mb-1">CRITICAL SLAs</p>
                <p class="font-headline-md text-headline-md text-primary">{{ $slaBreaches }}</p>
                <div class="flex items-center gap-1 text-stage-lost mt-2">
                    <span class="material-symbols-outlined text-[16px]">warning</span>
                    <span class="font-label-sm text-label-sm">breached today</span>
                </div>
            </div>
        </div>
    </section>

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
