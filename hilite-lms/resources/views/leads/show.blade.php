@extends('layouts.app')

@section('content')
<div class="flex-1 overflow-y-auto p-margin-page space-y-gutter no-scrollbar" x-data="{ showEditModal: false }">
    {{-- Breadcrumbs & Header Actions --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('leads.index') }}" class="p-2 rounded-full hover:bg-surface-container border border-border-subtle transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="font-headline-md text-headline-md">{{ $engagement->name }}</h2>
                    <span class="px-3 py-1 rounded-full text-label-sm font-bold" style="background-color: {{ $engagement->stage_color }}15; color: {{ $engagement->stage_color }}; border: 1px solid {{ $engagement->stage_color }}30;">
                        {{ strtoupper($engagement->stage_name) }}
                    </span>
                </div>
                @inject('phoneService', 'App\Services\PhoneNormalizationService')
                @if($canSeeFullPhone)
                    <p class="text-body-sm text-on-surface-variant">{{ $phoneService->toNationalFormat($engagement->phone_e164) ?: $engagement->phone_e164 }}</p>
                @else
                    <p class="text-body-sm text-on-surface-variant">{{ $phoneService->toMaskedFormat($engagement->phone_e164) }}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showEditModal = true" class="px-4 py-2 border border-border-subtle bg-white rounded-xl font-label-md flex items-center gap-2 hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">edit</span> Edit
            </button>
            <button class="px-4 py-2 border border-border-subtle bg-white rounded-xl font-label-md flex items-center gap-2 hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">share</span> Share
            </button>
            <a href="tel:{{ $canSeeFullPhone ? $engagement->phone_e164 : '' }}" class="px-4 py-2 bg-primary text-on-primary rounded-xl font-label-md flex items-center gap-2 hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[20px]">call</span> Call Now
            </a>
        </div>
    </div>

    {{-- Two-Column Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter items-start">

        {{-- Left Column: Information & Timeline --}}
        <div class="lg:col-span-7 space-y-gutter">

            {{-- Lead Information Card --}}
            <section class="bg-white rounded-2xl border border-border-subtle soft-shadow overflow-hidden">
                <div class="px-card-padding py-4 border-b border-border-subtle flex items-center gap-2 bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary">person</span>
                    <h3 class="font-label-md uppercase tracking-wider text-on-surface-variant">Lead Information</h3>
                </div>
                <div class="p-card-padding grid grid-cols-2 gap-y-6 gap-x-4">
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">PHONE</label>
                        @if($canSeeFullPhone)
                            <p class="font-body-md text-primary font-medium">{{ $phoneService->toNationalFormat($engagement->phone_e164) ?: $engagement->phone_e164 }}</p>
                        @else
                            <p class="font-body-md text-primary font-medium">{{ $phoneService->toMaskedFormat($engagement->phone_e164) }}</p>
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
                        <p class="font-body-md px-2 py-0.5 bg-surface-container-high rounded border border-border-subtle inline-block text-xs uppercase font-bold">{{ ucfirst($engagement->source ?? 'Manual') }}</p>
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
                        @if($engagement->last_activity_at)
                            <p class="font-body-md">
                                {{ \Carbon\Carbon::parse($engagement->last_activity_at)->format('M d, Y, h:i A') }}
                                <span class="text-text-muted text-xs">({{ \Carbon\Carbon::parse($engagement->last_activity_at)->diffForHumans() }})</span>
                            </p>
                        @else
                            <p class="font-body-md text-text-muted">Never</p>
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-label-sm text-text-muted">CREATED DATE</label>
                        <p class="font-body-md">{{ \Carbon\Carbon::parse($engagement->created_at)->format('M d, Y, h:i A') }}</p>
                    </div>
                </div>
            </section>

            {{-- Activity Timeline Card --}}
            <section class="bg-white rounded-2xl border border-border-subtle soft-shadow overflow-hidden">
                <div class="px-card-padding py-4 border-b border-border-subtle flex items-center justify-between bg-surface-container-low/30">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">history</span>
                        <h3 class="font-label-md uppercase tracking-wider text-on-surface-variant">Activity Timeline</h3>
                    </div>
                    <span class="text-label-sm text-text-muted">{{ count($activities) }} activities</span>
                </div>
                <div class="p-card-padding space-y-0 relative">
                    {{-- Timeline vertical line --}}
                    <div class="absolute left-[31px] top-9 bottom-4 w-0.5 bg-border-subtle"></div>

                    @forelse($activities as $activity)
                    <div class="relative pl-8 pb-8">
                        <div class="absolute left-0 top-0 w-6 h-6 rounded-full flex items-center justify-center z-10" style="background-color: {{ $activity->stage_color ?? ($activity->type === 'call' ? '#F59E0B' : ($activity->type === 'visit' ? '#3B82F6' : ($activity->type === 'system' ? '#10B981' : '#6366F1'))) }}">
                            <span class="material-symbols-outlined text-white text-[14px]">
                                {{ $activity->type === 'call' ? 'phone_in_talk' : ($activity->type === 'visit' ? 'home_work' : ($activity->type === 'system' ? 'bolt' : 'chat_bubble')) }}
                            </span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <p class="font-label-md text-on-surface capitalize">
                                    {{ $activity->type === 'call' ? 'Call completed by' : ($activity->type === 'visit' ? 'Site visit by' : ucfirst($activity->type) . ' by') }}
                                    <span class="font-bold">{{ $activity->user_name ?? 'System' }}</span>
                                </p>
                                <span class="text-label-sm text-text-muted" title="{{ $activity->created_at }}">{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}</span>
                            </div>
                            @if($activity->notes)
                            <div class="bg-surface-container-low p-3 rounded-xl border border-border-subtle">
                                <p class="text-body-sm italic">"{{ $activity->notes }}"</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6">
                        <span class="material-symbols-outlined text-text-muted text-[32px] mb-2 block">forum</span>
                        <p class="text-body-sm text-text-muted">No activities logged yet.</p>
                    </div>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Right Column: Quick Actions --}}
        <div class="lg:col-span-5 space-y-gutter">

            {{-- Quick Actions Card --}}
            <section class="bg-white rounded-2xl border border-border-subtle soft-shadow overflow-hidden">
                <div class="px-card-padding py-4 border-b border-border-subtle flex items-center gap-2 bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary">edit_square</span>
                    <h3 class="font-label-md uppercase tracking-wider text-on-surface-variant">Quick Actions</h3>
                </div>
                <div class="p-card-padding space-y-5">
                    <form method="POST" action="{{ route('leads.log-activity', $engagement->engagement_id) }}" class="space-y-4">
                        @csrf
                        <label class="text-label-md font-bold text-on-surface-variant block uppercase tracking-tight">LOG ACTIVITY</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-label-sm text-text-muted">TYPE</label>
                                <select name="type" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-4 py-2.5 text-body-sm focus:ring-2 focus:ring-primary outline-none">
                                    <option value="note">Note</option>
                                    <option value="followup">Follow-up</option>
                                    <option value="call">Call</option>
                                    <option value="visit">Visit</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-label-sm text-text-muted">FOLLOW-UP AT</label>
                                <input name="follow_up_at" class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-4 py-2 text-body-sm focus:ring-2 focus:ring-primary outline-none" type="datetime-local">
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-label-sm text-text-muted">NOTES</label>
                            <textarea name="notes" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-4 py-3 text-body-sm focus:ring-2 focus:ring-primary resize-none outline-none" placeholder="Write your notes here..." rows="4"></textarea>
                        </div>
                        <button type="submit" class="w-full py-3.5 bg-primary text-on-primary rounded-xl font-label-md flex items-center justify-center gap-2 hover:opacity-90 active:scale-[0.98] transition-all">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            Log Activity
                        </button>
                    </form>
                </div>
            </section>

            {{-- Stage & Assignment Expandable --}}
            <section x-data="{ expanded: false }" class="bg-white rounded-2xl border border-border-subtle soft-shadow overflow-hidden transition-all duration-300">
                <button @click="expanded = !expanded" class="w-full px-card-padding py-4 flex items-center justify-between hover:bg-surface-container-low/30 transition-colors">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">settings_account_box</span>
                        <h3 class="font-label-md uppercase tracking-wider text-on-surface-variant">Stage &amp; Assignment</h3>
                    </div>
                    <span class="material-symbols-outlined transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="expanded" x-collapse class="px-card-padding pb-6 space-y-6">
                    <form method="POST" action="{{ route('leads.update-stage', $engagement->engagement_id) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <label class="text-label-sm text-text-muted block">CURRENT PIPELINE STAGE</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($stages as $stage)
                                <button type="submit" name="stage_id" value="{{ $stage->id }}" class="px-3 py-2 text-label-sm border rounded-lg font-bold transition-all {{ $engagement->stage_id == $stage->id ? 'text-white' : 'opacity-70' }}" style="{{ $engagement->stage_id == $stage->id ? 'background-color: ' . $stage->color . '; border-color: ' . $stage->color . ';' : 'border-color: ' . $stage->color . '; color: ' . $stage->color . '; background-color: ' . $stage->color . '08;' }}">
                                    {{ strtoupper($stage->name) }}
                                </button>
                            @endforeach
                        </div>
                    </form>
                    <div class="space-y-2">
                        <label class="text-label-sm text-text-muted block">ASSIGN SALES OFFICER</label>
                        <div class="flex items-center gap-3 p-3 border border-border-subtle rounded-xl hover:bg-surface-container-low cursor-pointer transition-colors">
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

            {{-- SLA Panel --}}
            @if($engagement->sla_breached || $engagement->sla_due_at)
            <div class="bg-surface-dim/40 rounded-2xl border {{ $engagement->sla_breached ? 'border-error/30' : 'border-border-subtle' }} p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full {{ $engagement->sla_breached ? 'bg-error/10 text-error' : 'bg-primary/10 text-primary' }} flex items-center justify-center">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">timer</span>
                    </div>
                    <div>
                        <p class="text-label-md font-bold {{ $engagement->sla_breached ? 'text-error' : 'text-primary' }}">
                            {{ $engagement->sla_breached ? 'SLA BREACHED' : 'SLA AT RISK' }}
                        </p>
                        <p class="text-[11px] text-on-surface-variant">
                            {{ $engagement->sla_due_at ? 'Follow-up due ' . \Carbon\Carbon::parse($engagement->sla_due_at)->diffForHumans() : 'No deadline' }}
                        </p>
                    </div>
                </div>
                <button class="text-label-sm font-bold {{ $engagement->sla_breached ? 'text-error' : 'text-primary' }} underline underline-offset-4 hover:opacity-70 transition-opacity">Remind Me</button>
            </div>
            @endif

        </div>
    </div>
    </div>

    {{-- Edit Lead Modal --}}
    <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="showEditModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden soft-shadow transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-border-subtle">
                <form action="{{ route('leads.update', $engagement->engagement_id) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="px-6 py-5 border-b border-border-subtle flex justify-between items-center bg-surface-container-low/30">
                        <h3 class="text-headline-sm font-semibold text-on-surface" id="modal-title">Edit Lead</h3>
                        <button type="button" @click="showEditModal = false" class="text-text-muted hover:text-on-surface transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Success flash --}}
                        @if(session('success'))
                        <div class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            {{ session('success') }}
                        </div>
                        @endif

                        {{-- Name --}}
                        <div>
                            <label class="block text-label-sm text-on-surface-variant mb-1 font-semibold uppercase">Name <span class="text-error">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $engagement->name) }}" required
                                class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-border-subtle' }} focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-body-md bg-surface-container-low/50">
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Phone (editable, show formatted version) --}}
                        <div>
                            <label class="block text-label-sm text-on-surface-variant mb-1 font-semibold uppercase">Phone Number <span class="text-error">*</span></label>
                            <input type="text" name="phone"
                                value="{{ old('phone', $engagement->phone_e164) }}"
                                placeholder="+91 98765 43210"
                                required
                                class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-border-subtle' }} focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-body-md bg-surface-container-low/50">
                            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            <p class="mt-1 text-[11px] text-on-surface-variant">Enter with country code, e.g. +91 98765 43210</p>
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-label-sm text-on-surface-variant mb-1 font-semibold uppercase">Email Address</label>
                            <input type="email" name="email" value="{{ old('email', $engagement->email) }}"
                                class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-border-subtle' }} focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-body-md bg-surface-container-low/50">
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-surface-container-low/30 flex justify-end gap-3 border-t border-border-subtle">
                        <button type="button" @click="showEditModal = false" class="px-5 py-2.5 rounded-xl border border-border-subtle text-on-surface-variant font-label-md hover:bg-surface-container transition-colors">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-label-md hover:bg-primary/90 transition-colors shadow-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .soft-shadow { box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.03); }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection
