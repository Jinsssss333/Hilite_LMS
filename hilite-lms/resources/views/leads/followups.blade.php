@extends('layouts.app')

@section('content')
<!-- Page Header & Filters -->
<div class="mb-6 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div>
        <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg font-bold text-on-surface mb-1">Follow-ups</h2>
        <p class="font-body-md text-body-md text-text-muted">Manage your scheduled activities and overdue tasks.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Filters -->
        <div class="flex items-center gap-2 bg-surface-container-lowest border border-border-subtle rounded-full px-1 py-1 shadow-sm">
            <select class="bg-transparent border-none text-body-sm text-on-surface focus:ring-0 py-1 pl-3 pr-8 rounded-full cursor-pointer hover:bg-surface-container-low">
                <option value="all">All Activities</option>
                <option value="call">Calls</option>
                <option value="visit">Site Visits</option>
                <option value="email">Emails/Notes</option>
            </select>
            <div class="w-px h-4 bg-border-subtle"></div>
            <select class="bg-transparent border-none text-body-sm text-on-surface focus:ring-0 py-1 pl-3 pr-8 rounded-full cursor-pointer hover:bg-surface-container-low">
                <option value="me">Assigned to Me</option>
                <option value="team">My Team</option>
            </select>
        </div>
        <x-button variant="secondary">
            <span class="material-symbols-outlined" style="font-size: 18px;">calendar_today</span>
            Today
        </x-button>
    </div>
</div>

<!-- List Container -->
<div class="max-w-[1200px] mx-auto space-y-8">
    
    <!-- OVERDUE GROUP -->
    @if($overdue->count() > 0)
    <section>
        <h3 class="font-label-sm text-label-sm text-stage-lost flex items-center gap-2 mb-3 uppercase tracking-wider font-bold">
            <span class="material-symbols-outlined" style="font-size: 16px;">error</span>
            Overdue ({{ $overdue->count() }})
        </h3>
        <div class="space-y-2">
            @foreach($overdue as $activity)
            <x-card class="followup-card relative">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-stage-lost"></div>
                <div class="flex-1 flex items-center gap-4 pl-3 w-full">
                    <div class="w-9 h-9 rounded-full bg-error-container flex items-center justify-center flex-shrink-0 text-error">
                        <span class="material-symbols-outlined" style="font-size: 18px;">{{ $activity->type === 'visit' ? 'directions_car' : ($activity->type === 'email' ? 'mail' : 'call') }}</span>
                    </div>
                    <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <div class="sm:col-span-4 flex items-center gap-2">
                            <p class="font-headline-sm text-headline-sm text-on-surface truncate">{{ $activity->engagement->lead->name }}</p>
                            <span class="text-[11px] font-bold uppercase tracking-wider rounded-full px-3 py-1.5 border" style="background-color: {{ $activity->engagement->stage->color }}20; color: {{ $activity->engagement->stage->color }};">{{ $activity->engagement->stage->name }}</span>
                        </div>
                        <div class="sm:col-span-5">
                            <p class="font-body-sm text-body-sm text-text-muted truncate flex items-center gap-1.5">
                                <span class="font-medium text-on-surface">{{ ucfirst($activity->type) }}:</span> {{ $activity->notes ?? 'Pending follow-up' }}
                            </p>
                        </div>
                        <div class="sm:col-span-3 text-left sm:text-right">
                            <p class="font-label-sm text-label-sm text-stage-lost font-bold">{{ \Carbon\Carbon::parse($activity->follow_up_at)->format('M d, g:i A') }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Default Actions -->
                <div class="default-actions mt-3 sm:mt-0 ml-auto pl-4 transition-opacity duration-200">
                    <form method="POST" action="{{ route('leads.followup.complete', $activity->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" title="Mark Completed" class="w-8 h-8 flex items-center justify-center rounded-full border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                        </button>
                    </form>
                </div>
            </x-card>
            @endforeach
        </div>
    </section>
    @endif

    <!-- TODAY GROUP -->
    <section>
        <h3 class="font-label-sm text-label-sm text-text-muted flex items-center gap-2 mb-3 uppercase tracking-wider font-bold">
            <span class="material-symbols-outlined" style="font-size: 16px;">today</span>
            Today ({{ $today->count() }})
        </h3>
        <div class="space-y-2">
            @forelse($today as $activity)
            <x-card class="followup-card relative">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-secondary-fixed-dim"></div>
                <div class="flex-1 flex items-center gap-4 pl-3 w-full">
                    <div class="w-9 h-9 rounded-full bg-surface-container-high flex items-center justify-center flex-shrink-0 text-on-surface">
                        <span class="material-symbols-outlined" style="font-size: 18px;">{{ $activity->type === 'visit' ? 'directions_car' : ($activity->type === 'email' ? 'mail' : 'call') }}</span>
                    </div>
                    <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <div class="sm:col-span-4 flex items-center gap-2">
                            <p class="font-headline-sm text-headline-sm text-on-surface truncate">{{ $activity->engagement->lead->name }}</p>
                            <span class="text-[11px] font-bold uppercase tracking-wider rounded-full px-3 py-1.5 border" style="background-color: {{ $activity->engagement->stage->color }}20; color: {{ $activity->engagement->stage->color }};">{{ $activity->engagement->stage->name }}</span>
                        </div>
                        <div class="sm:col-span-5">
                            <p class="font-body-sm text-body-sm text-text-muted truncate flex items-center gap-1.5">
                                <span class="font-medium text-on-surface">{{ ucfirst($activity->type) }}:</span> {{ $activity->notes ?? 'Pending follow-up' }}
                            </p>
                        </div>
                        <div class="sm:col-span-3 text-left sm:text-right">
                            <p class="font-label-sm text-label-sm text-on-surface font-bold">{{ \Carbon\Carbon::parse($activity->follow_up_at)->format('g:i A') }}</p>
                        </div>
                    </div>
                </div>
                <!-- Default Actions -->
                <div class="default-actions mt-3 sm:mt-0 ml-auto pl-4 transition-opacity duration-200">
                    <form method="POST" action="{{ route('leads.followup.complete', $activity->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" title="Mark Completed" class="w-8 h-8 flex items-center justify-center rounded-full border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                        </button>
                    </form>
                </div>
            </x-card>
            @empty
            <div class="py-4 text-text-muted font-body-sm">No follow-ups due today.</div>
            @endforelse
        </div>
    </section>

    <!-- UPCOMING GROUP -->
    @if($upcoming->count() > 0)
    <section>
        <h3 class="font-label-sm text-label-sm text-text-muted flex items-center gap-2 mb-3 uppercase tracking-wider font-bold">
            <span class="material-symbols-outlined" style="font-size: 16px;">update</span>
            Upcoming ({{ $upcoming->count() }})
        </h3>
        <div class="space-y-2">
            @foreach($upcoming as $activity)
            <x-card class="followup-card relative">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-border-subtle"></div>
                <div class="flex-1 flex items-center gap-4 pl-3 w-full">
                    <div class="w-9 h-9 rounded-full bg-surface-container-low flex items-center justify-center flex-shrink-0 text-text-muted">
                        <span class="material-symbols-outlined" style="font-size: 18px;">{{ $activity->type === 'visit' ? 'directions_car' : ($activity->type === 'email' ? 'mail' : 'call') }}</span>
                    </div>
                    <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <div class="sm:col-span-4 flex items-center gap-2">
                            <p class="font-headline-sm text-headline-sm text-on-surface truncate">{{ $activity->engagement->lead->name }}</p>
                            <span class="text-[11px] font-bold uppercase tracking-wider rounded-full px-3 py-1.5 border" style="background-color: {{ $activity->engagement->stage->color }}20; color: {{ $activity->engagement->stage->color }};">{{ $activity->engagement->stage->name }}</span>
                        </div>
                        <div class="sm:col-span-5">
                            <p class="font-body-sm text-body-sm text-text-muted truncate flex items-center gap-1.5">
                                <span class="font-medium text-on-surface">{{ ucfirst($activity->type) }}:</span> {{ $activity->notes ?? 'Pending follow-up' }}
                            </p>
                        </div>
                        <div class="sm:col-span-3 text-left sm:text-right">
                            <p class="font-label-sm text-label-sm text-on-surface font-bold">{{ \Carbon\Carbon::parse($activity->follow_up_at)->format('M d, g:i A') }}</p>
                        </div>
                    </div>
                </div>
                <!-- Default Actions -->
                <div class="default-actions mt-3 sm:mt-0 ml-auto pl-4 transition-opacity duration-200">
                    <form method="POST" action="{{ route('leads.followup.complete', $activity->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" title="Mark Completed" class="w-8 h-8 flex items-center justify-center rounded-full border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                        </button>
                    </form>
                </div>
            </x-card>
            @endforeach
        </div>
    </section>
    @endif

</div>

<style>
    /* Quick Actions Hover Effect for Follow-up Cards */
    .followup-card .quick-actions {
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.15s ease-in-out;
    }
    .followup-card:hover .quick-actions {
        opacity: 1;
        pointer-events: auto;
    }
    .followup-card:hover .default-actions {
        opacity: 0;
        pointer-events: none;
    }
</style>
@endsection
