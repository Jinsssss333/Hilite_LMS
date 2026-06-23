@extends('layouts.app')

@section('content')
<div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 no-scrollbar max-w-[1600px] w-full mx-auto">
    <!-- Breadcrumbs & Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('leads.index') }}" class="p-2 rounded-full hover:bg-surface-container border border-border-subtle transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="font-headline-md text-headline-md">{{ $engagement->name }}</h2>
                    <span class="px-3 py-1 text-label-sm font-bold rounded-full" style="background-color: {{ $engagement->stage_color }}26; color: {{ $engagement->stage_color }}; border: 1px solid {{ $engagement->stage_color }}4d;">
                        {{ strtoupper($engagement->stage_name) }}
                    </span>
                </div>
                @if($canSeeFullPhone)
                    <p class="text-body-sm text-on-surface-variant font-mono">{{ $engagement->phone_e164 }}</p>
                @else
                    <p class="text-body-sm text-on-surface-variant font-mono">{{ substr($engagement->phone_e164, 0, 4) . '*** **' . substr($engagement->phone_e164, -3) }}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button class="px-4 py-2 border border-border-subtle bg-surface-container-lowest rounded-xl font-label-md flex items-center gap-2 hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">edit</span> Edit
            </button>
            <button class="px-4 py-2 border border-border-subtle bg-surface-container-lowest rounded-xl font-label-md flex items-center gap-2 hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">share</span> Share
            </button>
            <a href="tel:{{ $canSeeFullPhone ? $engagement->phone_e164 : '' }}" class="px-4 py-2 bg-primary text-on-primary rounded-xl font-label-md flex items-center gap-2 hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[20px]">call</span> Call Now
            </a>
        </div>
    </div>

    <!-- Two-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Column: Information & Timeline -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Lead Information Card -->
            <section class="bg-surface-container-lowest rounded-2xl border border-border-subtle shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border-subtle flex items-center gap-2 bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary">person</span>
                    <h3 class="font-label-md uppercase tracking-wider text-on-surface-variant">Lead Information</h3>
                </div>
                <div class="p-5 grid grid-cols-2 gap-y-6 gap-x-4">
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">PHONE</label>
                        @if($canSeeFullPhone)
                            <p class="font-body-md text-primary font-medium font-mono">{{ $engagement->phone_e164 }}</p>
                        @else
                            <p class="font-body-md text-primary font-medium font-mono">{{ substr($engagement->phone_e164, 0, 4) . '*** **' . substr($engagement->phone_e164, -3) }}</p>
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">EMAIL</label>
                        @if($canSeeFullPhone)
                            <p class="font-body-md">{{ $engagement->email ?: '-' }}</p>
                        @else
                            <p class="font-body-md">{{ $engagement->email ? (str_contains($engagement->email, '@') ? substr($engagement->email, 0, 3) . '***@' . explode('@', $engagement->email)[1] : '***') : '-' }}</p>
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">PRIORITY</label>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-stage-booked" style="font-variation-settings: 'FILL' 1;">star</span>
                            <p class="font-body-md font-semibold">High (85/100)</p>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">SOURCE</label>
                        <p class="font-body-md px-2 py-0.5 bg-surface-container-high rounded border border-border-subtle inline-block text-[11px] uppercase font-bold">{{ $engagement->source }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">STAGE</label>
                        <p class="font-body-md font-semibold flex items-center gap-2" style="color: {{ $engagement->stage_color }}">
                            <span class="w-3 h-3 rounded-full" style="background-color: {{ $engagement->stage_color }}"></span>
                            {{ $engagement->stage_name }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">ASSIGNED TO</label>
                        @if($engagement->assigned_name)
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold">
                                    {{ strtoupper(substr($engagement->assigned_name, 0, 2)) }}
                                </div>
                                <p class="font-body-md">{{ $engagement->assigned_name }}</p>
                            </div>
                        @else
                            <p class="font-body-md text-text-muted italic">Unassigned</p>
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">LAST ACTIVITY</label>
                        <p class="font-body-md">{{ $engagement->last_activity_at ? \Carbon\Carbon::parse($engagement->last_activity_at)->format('M d, Y, h:i A') : 'Never' }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">CREATED DATE</label>
                        <p class="font-body-md">{{ \Carbon\Carbon::parse($engagement->created_at)->format('M d, Y, h:i A') }}</p>
                    </div>
                </div>
            </section>

            <!-- Activity Timeline Card -->
            <section class="bg-surface-container-lowest rounded-2xl border border-border-subtle shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border-subtle flex items-center justify-between bg-surface-container-low/30">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">history</span>
                        <h3 class="font-label-md uppercase tracking-wider text-on-surface-variant">Activity Timeline</h3>
                    </div>
                    <span class="text-label-sm text-text-muted">{{ count($activities) }} activities</span>
                </div>
                <div class="p-5 space-y-0 relative">
                    <div class="absolute left-[31px] top-9 bottom-0 w-0.5 bg-border-subtle"></div>
                    
                    @forelse($activities as $activity)
                    <div class="relative pl-8 pb-8">
                        <div class="absolute left-0 top-0 w-6 h-6 rounded-full flex items-center justify-center z-10" style="background-color: {{ $activity->stage_color ?? '#000' }}">
                            <span class="material-symbols-outlined text-white text-[14px]">
                                {{ $activity->type === 'call' ? 'phone_in_talk' : ($activity->type === 'visit' ? 'home_work' : ($activity->type === 'system' ? 'bolt' : 'chat_bubble')) }}
                            </span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <p class="font-label-md text-on-surface capitalize">{{ $activity->type }} by <span class="font-bold">{{ $activity->user_name ?? 'System' }}</span></p>
                                <span class="text-label-sm text-text-muted" title="{{ $activity->created_at }}">{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}</span>
                            </div>
                            @if($activity->notes)
                            <div class="bg-surface-container-low p-3 rounded-xl border border-border-subtle mt-2">
                                <p class="text-body-sm italic">"{{ $activity->notes }}"</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6">
                        <p class="text-body-sm text-text-muted">No activities logged yet.</p>
                    </div>
                    @endforelse
                </div>
            </section>
        </div>

        <!-- Right Column: Quick Actions -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Quick Actions Card -->
            <section class="bg-surface-container-lowest rounded-2xl border border-border-subtle shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-border-subtle flex items-center gap-2 bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary">edit_square</span>
                    <h3 class="font-label-md uppercase tracking-wider text-on-surface-variant">Quick Actions</h3>
                </div>
                <div class="p-5 space-y-5">
                    <form method="POST" action="{{ route('leads.log-activity', $engagement->engagement_id) }}" class="space-y-4">
                        @csrf
                        <label class="text-label-md font-bold text-on-surface-variant block uppercase tracking-tight">LOG ACTIVITY</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-label-sm text-text-muted">TYPE</label>
                                <select name="type" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-4 py-2.5 text-body-sm focus:ring-2 focus:ring-primary">
                                    <option value="note">Note</option>
                                    <option value="follow_up">Follow-up</option>
                                    <option value="call">Call</option>
                                    <option value="visit">Visit</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-label-sm text-text-muted">FOLLOW-UP AT</label>
                                <input name="follow_up_at" class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-4 py-2.5 text-body-sm focus:ring-2 focus:ring-primary" type="datetime-local">
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-label-sm text-text-muted">NOTES</label>
                            <textarea name="notes" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-4 py-3 text-body-sm focus:ring-2 focus:ring-primary resize-none" placeholder="Write your notes here..." rows="4"></textarea>
                        </div>
                        <button type="submit" class="w-full py-3.5 bg-primary text-on-primary rounded-xl font-label-md flex items-center justify-center gap-2 hover:opacity-90 active:scale-[0.98] transition-all">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            Log Activity
                        </button>
                    </form>
                </div>
            </section>

            <!-- Stage & Assignment Expandable -->
            <section x-data="{ expanded: false }" class="bg-surface-container-lowest rounded-2xl border border-border-subtle shadow-sm overflow-hidden transition-all duration-300">
                <button @click="expanded = !expanded" class="w-full px-5 py-4 flex items-center justify-between hover:bg-surface-container-low/30 transition-colors">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">settings_account_box</span>
                        <h3 class="font-label-md uppercase tracking-wider text-on-surface-variant">Stage &amp; Assignment</h3>
                    </div>
                    <span class="material-symbols-outlined transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="expanded" x-collapse class="px-5 pb-6 space-y-6">
                    <form method="POST" action="{{ route('leads.update-stage', $engagement->engagement_id) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <label class="text-label-sm text-text-muted block">UPDATE PIPELINE STAGE</label>
                        <div class="flex items-center gap-2">
                            <select name="stage_id" class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-4 py-2.5 text-body-sm focus:ring-2 focus:ring-primary">
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->id }}" {{ $engagement->stage_id == $stage->id ? 'selected' : '' }}>{{ $stage->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-4 py-2.5 bg-surface-variant text-on-surface-variant rounded-xl font-label-md hover:bg-outline-variant transition-colors">Save</button>
                        </div>
                    </form>

                    <div class="space-y-2">
                        <label class="text-label-sm text-text-muted block">ASSIGN SALES OFFICER</label>
                        <div class="flex items-center gap-3 p-3 border border-border-subtle rounded-xl hover:bg-surface-container-low transition-colors">
                            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white text-[10px] font-bold">
                                {{ $engagement->assigned_name ? strtoupper(substr($engagement->assigned_name, 0, 2)) : 'UN' }}
                            </div>
                            <div class="flex-1">
                                <p class="text-body-sm font-semibold leading-none">{{ $engagement->assigned_name ?? 'Unassigned' }}</p>
                                <p class="text-[10px] text-text-muted">Current Assignee</p>
                            </div>
                            <span class="material-symbols-outlined text-on-surface-variant">swap_horiz</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SLA Panel -->
            @if($engagement->sla_breached || $engagement->sla_due_at)
            <div class="bg-surface-dim/40 rounded-2xl border {{ $engagement->sla_breached ? 'border-error/30 bg-error-container/20' : 'border-border-subtle' }} p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full {{ $engagement->sla_breached ? 'bg-error/10 text-error' : 'bg-primary/10 text-primary' }} flex items-center justify-center">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">timer</span>
                    </div>
                    <div>
                        <p class="text-label-md font-bold {{ $engagement->sla_breached ? 'text-error' : 'text-primary' }}">
                            {{ $engagement->sla_breached ? 'SLA BREACHED' : 'SLA DUE' }}
                        </p>
                        <p class="text-[11px] text-on-surface-variant">
                            {{ $engagement->sla_due_at ? \Carbon\Carbon::parse($engagement->sla_due_at)->diffForHumans() : 'No deadline' }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
