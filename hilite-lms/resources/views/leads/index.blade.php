@extends('layouts.app')

@section('content')
<div class="p-margin-page space-y-6">
    {{-- Page Header & Actions --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="font-headline-md text-headline-md text-primary">{{ in_array($role, ['admin','manager','branch_head']) ? 'All Leads Inventory' : 'My Leads' }}</h2>
            <p class="text-body-md text-on-surface-variant">
                Manage and track your leads across all stages.
                @if($slaBreaches > 0)
                    &nbsp;·&nbsp;<span class="text-error font-bold">⚠ {{ $slaBreaches }} SLA {{ Str::plural('breach', $slaBreaches) }}</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('leads.export', request()->only(['search','region','date','assigned_to','stage_id','sla'])) }}"
               class="flex items-center gap-2 px-4 py-2 border border-border-subtle bg-white rounded-lg font-body-md text-body-md hover:bg-surface-container-low transition-all group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">file_download</span>
                Export
            </a>
            {{-- 
            @if(in_array($role, ['admin', 'manager', 'branch_head']))
            <button class="flex items-center gap-2 px-4 py-2 border border-border-subtle bg-white rounded-lg font-body-md text-body-md hover:bg-surface-container-low transition-all">
                <span class="material-symbols-outlined text-[20px]">person_add</span>
                Change Owner
            </button>
            @endif 
            --}}
            <button onclick="document.getElementById('filter-panel').classList.toggle('hidden')" class="flex items-center gap-2 px-5 py-2 bg-primary text-on-primary rounded-lg font-body-md font-semibold hover:opacity-90 transition-all">
                <span class="material-symbols-outlined text-[20px]">filter_list</span>
                Advanced Filters
            </button>
        </div>
    </div>

    {{-- Stats Bento Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-border-subtle flex items-center justify-between custom-shadow">
            <div>
                <p class="text-label-md text-on-surface-variant uppercase tracking-wider">Unassigned</p>
                <p class="text-headline-sm font-bold text-primary">{{ $unassignedCount ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container">
                <span class="material-symbols-outlined">person_off</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-border-subtle flex items-center justify-between custom-shadow">
            <div>
                <p class="text-label-md text-on-surface-variant uppercase tracking-wider">High SLA Risk</p>
                <p class="text-headline-sm font-bold text-error">{{ $slaBreaches }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-error-container flex items-center justify-center text-on-error-container">
                <span class="material-symbols-outlined">warning</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-border-subtle flex items-center justify-between custom-shadow">
            <div>
                <p class="text-label-md text-on-surface-variant uppercase tracking-wider">Follow-ups Today</p>
                <p class="text-headline-sm font-bold text-stage-site-visit">{{ $followupsToday ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-stage-site-visit">
                <span class="material-symbols-outlined">event_upcoming</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-border-subtle flex items-center justify-between custom-shadow">
            <div>
                <p class="text-label-md text-on-surface-variant uppercase tracking-wider">Lead Speed</p>
                <p class="text-headline-sm font-bold text-stage-interested">{{ $avgSpeed ?? '—' }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-stage-interested">
                <span class="material-symbols-outlined">bolt</span>
            </div>
        </div>
    </div>

    {{-- Advanced Filter Panel (hidden by default unless filters are active) --}}
    <div id="filter-panel" class="{{ (request('stage_id') || request('search') || request('region') || request('assigned_to')) ? '' : 'hidden' }} bg-white border border-border-subtle rounded-xl p-6 shadow-sm relative overflow-hidden mt-4">
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div>
        <form method="GET" action="{{ route('leads.index') }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-headline-sm text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-text-muted">tune</span>
                    Advanced Filters
                </h3>
                <button type="button" onclick="document.getElementById('filter-panel').classList.add('hidden')" class="text-text-muted hover:text-on-surface">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            {{-- Stage Pills --}}
            <div class="mb-6 flex flex-wrap gap-2 items-center">
                <span class="text-label-md font-bold text-on-surface-variant mr-2">Filter by Stage:</span>
                <button type="submit" name="stage_id" value="" class="px-4 py-1.5 rounded-full text-body-sm font-medium transition-all {{ !request('stage_id') ? 'bg-primary text-on-primary' : 'bg-white border border-border-subtle text-on-surface-variant hover:border-primary' }}">All Leads</button>
                @foreach($stages as $stage)
                    <button type="submit" name="stage_id" value="{{ $stage->id }}" class="px-4 py-1.5 rounded-full text-body-sm font-medium transition-all {{ request('stage_id') == $stage->id ? 'bg-primary text-on-primary' : 'bg-white border border-border-subtle text-on-surface-variant' }}" style="{{ request('stage_id') == $stage->id ? '' : 'border-color: ' . $stage->color . '40;' }}">{{ $stage->name }}</button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <label class="flex flex-col gap-1.5">
                    <span class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, phone or email..." class="w-full rounded-xl border border-border-subtle bg-surface-container-low h-10 px-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none">
                </label>
                <label class="flex flex-col gap-1.5">
                    <span class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Region</span>
                    <select name="region" class="form-select w-full rounded-xl border border-border-subtle bg-surface-container-low h-10 px-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none">
                        <option value="">All Regions</option>
                        <option value="india" {{ request('region') === 'india' ? 'selected' : '' }}>India</option>
                        <option value="uae" {{ request('region') === 'uae' ? 'selected' : '' }}>UAE (Dubai)</option>
                        <option value="us" {{ request('region') === 'us' ? 'selected' : '' }}>United States</option>
                        <option value="uk" {{ request('region') === 'uk' ? 'selected' : '' }}>United Kingdom</option>
                    </select>
                </label>
                @if(in_array($role, ['admin', 'manager', 'branch_head', 'team_lead']))
                <label class="flex flex-col gap-1.5">
                    <span class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Assigned To</span>
                    <select name="assigned_to" class="form-select w-full rounded-xl border border-border-subtle bg-surface-container-low h-10 px-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none">
                        <option value="">All Team Members</option>
                        @foreach($assignableUsers as $u)
                        <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </label>
                @endif
            </div>
            
            <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-border-subtle">
                <a href="{{ route('leads.index') }}" class="px-4 py-2 text-label-md font-bold text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors">Clear All</a>
                <button type="submit" class="flex items-center gap-2 bg-primary text-on-primary px-5 py-2 rounded-full font-label-md hover:opacity-90 transition-colors">
                    <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    {{-- Inventory Table --}}
    <div class="bg-white rounded-xl border border-border-subtle overflow-hidden custom-shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-border-subtle">
                    <tr>
                        <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Lead Name</th>
                        <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Stage</th>
                        <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Source</th>
                        <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Assigned Rep</th>
                        <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase tracking-wider">SLA Status</th>
                        <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    @forelse($leads as $lead)
                    <tr class="hover:bg-surface-container-lowest transition-colors group cursor-pointer" onclick="window.location='{{ route('leads.show', $lead->engagement_id) }}'">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-on-primary font-bold text-sm">
                                    {{ strtoupper(substr($lead->name, 0, 2)) }}
                                </div>
                                <div>
                                    <a href="{{ route('leads.show', $lead->engagement_id) }}" class="font-body-md font-bold text-primary hover:underline" onclick="event.stopPropagation()">{{ $lead->name }}</a>
                                    <p class="text-label-sm text-on-surface-variant">{{ ucfirst($lead->source ?? 'Direct') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @inject('phoneService', 'App\Services\PhoneNormalizationService')
                            @if($canSeeFullPhone)
                                <p class="text-body-md text-on-surface">{{ $phoneService->toNationalFormat($lead->phone_e164) ?: $lead->phone_e164 }}</p>
                                <p class="text-label-sm text-on-surface-variant">{{ $lead->email ?: '-' }}</p>
                            @else
                                <p class="text-body-md text-on-surface">{{ $phoneService->toMaskedFormat($lead->phone_e164) }}</p>
                                <p class="text-label-sm text-on-surface-variant">{{ $lead->email ? (str_contains($lead->email, '@') ? substr($lead->email, 0, 3) . '***@' . explode('@', $lead->email)[1] : '***') : '-' }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4" onclick="event.stopPropagation()">
                            @if($canEditStatus)
                            <div class="relative inline-block w-max">
                                <select onchange="updateStage({{ $lead->engagement_id }}, this.value, this)" class="stage-pill px-3 py-1 rounded-full text-label-sm font-bold appearance-none cursor-pointer pr-7 outline-none focus:ring-2 focus:ring-primary transition-all" style="background-color: {{ $lead->stage_color }}15; border: 1px solid {{ $lead->stage_color }}; color: {{ $lead->stage_color }};">
                                    @foreach($stages as $stage)
                                    <option value="{{ $stage->id }}" data-color="{{ $stage->color }}" {{ $lead->stage_id == $stage->id ? 'selected' : '' }}>{{ strtoupper($stage->name) }}</option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined absolute right-1.5 top-1/2 -translate-y-1/2 pointer-events-none" style="font-size: 14px; color: {{ $lead->stage_color }};">arrow_drop_down</span>
                            </div>
                            @else
                                <span class="stage-pill px-3 py-1 rounded-full text-label-sm font-bold" style="background-color: {{ $lead->stage_color }}15; border: 1px solid {{ $lead->stage_color }}; color: {{ $lead->stage_color }};">{{ strtoupper($lead->stage_name) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1.5 text-on-surface-variant">
                                <span class="material-symbols-outlined text-[16px]">{{ $lead->source === 'webhook' ? 'language' : ($lead->source === 'csv' ? 'upload_file' : 'contact_page') }}</span>
                                <span class="text-body-sm">{{ ucfirst($lead->source ?? 'Manual') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($lead->assigned_name)
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container">
                                    {{ strtoupper(substr($lead->assigned_name, 0, 2)) }}
                                </div>
                                <span class="text-body-sm font-medium">{{ $lead->assigned_name }}</span>
                            </div>
                            @else
                                <span class="text-body-sm text-on-surface-variant italic">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($lead->sla_breached)
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-stage-lost"></div>
                                    <span class="text-body-sm text-error font-bold">Breached</span>
                                </div>
                            @elseif($lead->sla_due_at)
                                @php
                                    $dueAt = \Carbon\Carbon::parse($lead->sla_due_at);
                                    $hoursLeft = now()->diffInHours($dueAt, false);
                                @endphp
                                @if($hoursLeft <= 0)
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-stage-lost"></div>
                                        <span class="text-body-sm text-error font-bold">Overdue</span>
                                    </div>
                                @elseif($hoursLeft <= 4)
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-stage-contacted"></div>
                                        <span class="text-body-sm">Urgent ({{ round($hoursLeft) }}h left)</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-stage-interested"></div>
                                        <span class="text-body-sm">Healthy ({{ round($hoursLeft) }}h left)</span>
                                    </div>
                                @endif
                            @else
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-stage-interested"></div>
                                    <span class="text-body-sm">Healthy</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                            <a href="{{ route('leads.show', $lead->engagement_id) }}" class="text-on-surface-variant hover:text-primary transition-colors">
                                <span class="material-symbols-outlined">more_vert</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <span class="material-symbols-outlined text-text-muted text-[48px] mb-2 block">inbox</span>
                            <p class="text-body-md text-text-muted">No leads found matching your criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination Footer --}}
        <div class="bg-surface-container-low px-6 py-4 flex items-center justify-between border-t border-border-subtle">
            <p class="text-body-sm text-on-surface-variant">
                Showing <span class="font-bold text-primary">{{ $leads->firstItem() ?? 0 }} - {{ $leads->lastItem() ?? 0 }}</span> leads
            </p>
            <div class="flex items-center gap-2">
                @if($leads->previousPageUrl())
                    <a href="{{ $leads->previousPageUrl() }}" class="p-2 border border-border-subtle rounded-lg bg-white hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                @else
                    <button class="p-2 border border-border-subtle rounded-lg bg-white opacity-50 cursor-not-allowed" disabled>
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                @endif
                <span class="px-3 py-1 bg-primary text-on-primary rounded-lg font-bold text-body-sm">{{ $leads->currentPage() }}</span>
                @if($leads->hasMorePages())
                    <a href="{{ $leads->nextPageUrl() }}" class="p-2 border border-border-subtle rounded-lg bg-white hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                @else
                    <button class="p-2 border border-border-subtle rounded-lg bg-white opacity-50 cursor-not-allowed" disabled>
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Disposition Modal --}}
<div id="disposition-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-lg border border-border-subtle">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Log Disposition</h3>
            <button onclick="closeDispositionModal()" class="text-text-muted hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="disposition-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block font-label-sm text-label-sm text-text-muted mb-1">Type</label>
                <select name="type" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:ring-2 focus:ring-primary outline-none">
                    <option value="call">Call</option>
                    <option value="note">Note</option>
                    <option value="followup">Follow-up</option>
                    <option value="visit">Site Visit</option>
                </select>
            </div>
            <div>
                <label class="block font-label-sm text-label-sm text-text-muted mb-1">Notes</label>
                <textarea name="notes" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:ring-2 focus:ring-primary outline-none resize-none" rows="3" placeholder="What happened on this call?"></textarea>
            </div>
            <div>
                <label class="block font-label-sm text-label-sm text-text-muted mb-1">Follow-up Date (optional)</label>
                <input type="datetime-local" name="follow_up_at" class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeDispositionModal()" class="px-4 py-2 text-text-muted hover:text-on-surface font-label-md">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-colors">Log Activity</button>
            </div>
        </form>
    </div>
</div>

<style>
    .custom-shadow { box-shadow: 0 2px 4px rgba(0,0,0,0.02), 0 1px 0 rgba(0,0,0,0.01); }
    .stage-pill { border-width: 1px; }
</style>

<script>
    // Stage update via AJAX
    function updateStage(engagementId, stageId, selectEl) {
        fetch(`/leads/${engagementId}/stage`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ stage_id: stageId })
        }).then(r => r.json()).then(data => {
            if (data.color) {
                selectEl.style.backgroundColor = data.color + '15';
                selectEl.style.borderColor = data.color;
                selectEl.style.color = data.color;
                selectEl.nextElementSibling.style.color = data.color;
            }
        }).catch(() => alert('Failed to update stage'));
    }

    // Disposition modal
    function openDispositionModal(engagementId) {
        const modal = document.getElementById('disposition-modal');
        const form = document.getElementById('disposition-form');
        form.action = `/leads/${engagementId}/log-activity`;
        modal.classList.remove('hidden');
    }
    function closeDispositionModal() {
        document.getElementById('disposition-modal').classList.add('hidden');
    }

    // Micro-interactions for table rows
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.transform = 'translateY(-1px)';
            row.classList.add('custom-shadow');
        });
        row.addEventListener('mouseleave', () => {
            row.style.transform = 'none';
            row.classList.remove('custom-shadow');
        });
    });

    // Auto-open reassignment request modal if a duplicate was detected
    @if(session('duplicate_engagement_id'))
    document.addEventListener('DOMContentLoaded', () => openReassignModal());
    @endif

    function openReassignModal() {
        document.getElementById('reassign-modal').classList.remove('hidden');
    }
    function closeReassignModal() {
        document.getElementById('reassign-modal').classList.add('hidden');
    }
</script>

{{-- ─── Reassignment Request Modal ─────────────────────────────────────────── --}}
@if(session('duplicate_engagement_id'))
@php
    $dupEngagementId  = session('duplicate_engagement_id');
    $dupLeadName      = session('duplicate_lead_name', 'Unknown Lead');
    $dupOwnerName     = session('duplicate_owner_name', 'Unassigned');
    $dupOwnerTeamId   = session('duplicate_owner_team_id');
    $authUser         = \App\Http\Helpers\AuthHelper::user();
    $isCrossTeam      = $dupOwnerTeamId && $authUser && $dupOwnerTeamId !== $authUser->team_id;
@endphp
<div id="reassign-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-[22px]">person_search</span>
                </div>
                <div>
                    <h3 class="text-white font-bold text-lg leading-tight">Lead Already Exists</h3>
                    <p class="text-white/80 text-sm">Request reassignment from your team lead</p>
                </div>
            </div>
        </div>

        {{-- Lead info --}}
        <div class="px-6 pt-5">
            <div class="bg-surface-container-low rounded-xl p-4 mb-4 border border-border-subtle">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-[20px]">person</span>
                    </div>
                    <div>
                        <p class="font-semibold text-on-surface text-sm">{{ $dupLeadName }}</p>
                        <p class="text-xs text-on-surface-variant">Currently assigned to: <span class="font-medium text-on-surface">{{ $dupOwnerName }}</span></p>
                    </div>
                </div>
                @if($isCrossTeam)
                <div class="mt-3 flex items-start gap-2 bg-purple-50 border border-purple-200 rounded-lg px-3 py-2.5">
                    <span class="material-symbols-outlined text-purple-600 text-[16px] mt-0.5 flex-shrink-0">warning</span>
                    <p class="text-xs text-purple-700 leading-relaxed"><span class="font-bold">Cross-team conflict.</span> This lead belongs to a different team. Your request will go to your team lead and may be escalated to the Branch Head.</p>
                </div>
                @endif
            </div>

            <form action="{{ route('leads.reassign-request') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="engagement_id" value="{{ $dupEngagementId }}">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Why do you want this lead? <span class="text-error">*</span></label>
                    <textarea name="reason" rows="4" required minlength="10"
                        placeholder="Explain your reason — e.g., I spoke with this client at the property event yesterday and they specifically asked to work with me..."
                        class="w-full px-4 py-3 rounded-xl border border-border-subtle text-sm text-on-surface bg-surface-container-low focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none"></textarea>
                </div>
                <div class="flex gap-3 pb-5">
                    <button type="button" onclick="closeReassignModal()"
                        class="flex-1 py-3 border border-border-subtle rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-surface-container-low transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl text-sm font-bold hover:opacity-90 active:scale-95 transition-all shadow-lg shadow-amber-500/20">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

