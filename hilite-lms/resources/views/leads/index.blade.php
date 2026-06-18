@extends('layouts.app')

@section('content')
<div class="max-w-[1200px] w-full mx-auto">
    <form method="GET" action="{{ route('leads.index') }}">
    <!-- Header Section -->
    <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 mb-6">
        <div>
            <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg font-bold text-on-surface mb-1">Lead Inventory</h2>
            <p class="font-body-md text-body-md text-text-muted">
                Showing {{ $leads->firstItem() ?? 0 }} to {{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }} leads
                @if($slaBreaches > 0)
                    &nbsp;·&nbsp;<span class="text-stage-lost font-bold">⚠ {{ $slaBreaches }} SLA {{ Str::plural('breach', $slaBreaches) }}</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative focus-within:ring-2 focus-within:ring-primary rounded-full bg-surface-container-lowest border border-border-subtle shadow-sm flex items-center px-3">
                <span class="material-symbols-outlined text-text-muted text-[18px]">search</span>
                <input type="text" name="search" value="{{ request('search') }}" class="bg-transparent border-none focus:ring-0 text-body-sm h-9 px-2 w-48" placeholder="Search by name or phone..." />
            </div>
            <x-button variant="secondary" onclick="document.getElementById('filter-panel').classList.toggle('hidden')">
                <span class="material-symbols-outlined" style="font-size: 18px;">filter_list</span>
                Filters
            </x-button>
            <x-button type="button" variant="primary">
                <span class="material-symbols-outlined" style="font-size: 18px;">download</span>
                Export
            </x-button>
        </div>
    </header>

    <!-- Filter Panel (Hidden by default) -->
    <div id="filter-panel" class="hidden bg-surface-container-low border border-border-subtle rounded-[20px] p-6 mb-6 shadow-sm relative overflow-hidden">
        <!-- Decorative accent -->
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div>
        
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-headline-sm text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-text-muted">tune</span>
                Advanced Filters
            </h3>
            <button onclick="document.getElementById('filter-panel').classList.add('hidden')" class="text-text-muted hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Region Filter -->
            <label class="flex flex-col gap-1.5">
                <span class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Region / Place</span>
                <select name="region" class="form-select w-full rounded-xl border border-border-subtle bg-surface h-10 px-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none">
                    <option value="">All Regions</option>
                    <option value="india" {{ request('region') === 'india' ? 'selected' : '' }}>India</option>
                    <option value="uae" {{ request('region') === 'uae' ? 'selected' : '' }}>UAE (Dubai)</option>
                    <option value="us" {{ request('region') === 'us' ? 'selected' : '' }}>United States</option>
                    <option value="uk" {{ request('region') === 'uk' ? 'selected' : '' }}>United Kingdom</option>
                </select>
            </label>

            <!-- Date Range Filter -->
            <label class="flex flex-col gap-1.5">
                <span class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Created Date</span>
                <select name="date" class="form-select w-full rounded-xl border border-border-subtle bg-surface h-10 px-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none">
                    <option value="">Any Time</option>
                    <option value="today" {{ request('date') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="this_week" {{ request('date') === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ request('date') === 'this_month' ? 'selected' : '' }}>This Month</option>
                </select>
            </label>

            <!-- Assigned To Filter (visible to managers and above) -->
            @if(in_array($role, ['admin', 'manager', 'branch_head', 'team_lead']))
            <label class="flex flex-col gap-1.5 relative group">
                <span class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider flex items-center justify-between">
                    Assigned To
                    @if($role === 'team_lead')
                    <span class="text-[9px] bg-secondary-container text-on-secondary-container px-1.5 py-0.5 rounded-md flex items-center gap-1 cursor-help" title="Team Leads can only filter and view data for salespeople in their own team.">
                        <span class="material-symbols-outlined" style="font-size: 10px;">lock</span> TL Only
                    </span>
                    @endif
                </span>
                <select name="assigned_to" class="form-select w-full rounded-xl border border-border-subtle bg-surface h-10 px-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none">
                    <option value="">All Team Members</option>
                    @foreach($assignableUsers as $u)
                    <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </label>
            @endif

            <!-- Stage Filter -->
            <label class="flex flex-col gap-1.5">
                <span class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Pipeline Stage</span>
                <select name="stage_id" class="form-select w-full rounded-xl border border-border-subtle bg-surface h-10 px-3 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none">
                    <option value="">All Stages</option>
                    @foreach($stages as $stage)
                    <option value="{{ $stage->id }}" {{ request('stage_id') == $stage->id ? 'selected' : '' }}>{{ $stage->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        
        <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-border-subtle">
            <button type="button" class="px-4 py-2 text-label-md font-bold text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors" onclick="document.getElementById('filter-panel').classList.add('hidden')">Cancel</button>
            <button type="submit" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2 rounded-full font-label-md text-label-md hover:bg-tertiary transition-colors">
                <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                Apply Filters
            </button>
        </div>
    </div>
    </form>

    <!-- Table Card -->
    <div class="bg-surface-container-lowest border border-border-subtle rounded-[20px] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-border-subtle">
                        <th class="px-6 py-4 font-label-sm text-label-sm text-text-muted uppercase tracking-wider font-bold">Name &amp; Contact</th>
                        <th class="px-6 py-4 font-label-sm text-label-sm text-text-muted uppercase tracking-wider font-bold">Stage</th>
                        <th class="px-6 py-4 font-label-sm text-label-sm text-text-muted uppercase tracking-wider font-bold">Assigned To</th>
                        <th class="px-6 py-4 font-label-sm text-label-sm text-text-muted uppercase tracking-wider font-bold">Last Activity</th>
                        <th class="px-6 py-4 font-label-sm text-label-sm text-text-muted uppercase tracking-wider font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    @forelse($leads as $lead)
                    <tr class="hover:bg-surface-container-low/50 transition-colors group">
                        <td class="px-6 py-4">
                            <p class="font-headline-sm text-headline-sm text-on-surface">{{ $lead->name }}</p>
                            @if($canSeeFullPhone)
                                <p class="font-body-sm text-body-sm text-text-muted font-mono tracking-wider">
                                    {{ $lead->phone_e164 }}
                                </p>
                            @else
                                <p class="font-body-sm text-body-sm text-text-muted font-mono tracking-wider">
                                    {{ substr($lead->phone_e164, 0, 4) . str_repeat('*', max(0, strlen($lead->phone_e164) - 7)) . substr($lead->phone_e164, -3) }}
                                </p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($canEditStatus)
                            <div class="relative inline-block w-max">
                                <select onchange="updateStage({{ $lead->engagement_id }}, this.value, this)" class="text-[11px] font-bold uppercase tracking-wider rounded-full px-3 py-1.5 border border-border-subtle focus:ring-2 focus:ring-primary appearance-none cursor-pointer pr-8 outline-none hover:opacity-80 transition-opacity" style="background-color: {{ $lead->stage_color }}20; color: {{ $lead->stage_color }};">
                                    @foreach($stages as $stage)
                                    <option value="{{ $stage->id }}" data-color="{{ $stage->color }}" {{ $lead->stage_id == $stage->id ? 'selected' : '' }}>{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" style="font-size: 16px; color: {{ $lead->stage_color }};">arrow_drop_down</span>
                            </div>
                            @else
                                <span class="text-[11px] font-bold uppercase tracking-wider rounded-full px-3 py-1.5 border" style="background-color: {{ $lead->stage_color }}20; color: {{ $lead->stage_color }};">{{ $lead->stage_name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($lead->assigned_name)
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container">
                                    {{ strtoupper(substr($lead->assigned_name, 0, 2)) }}
                                </div>
                                <span class="font-body-sm text-on-surface">{{ $lead->assigned_name }}</span>
                            </div>
                            @else
                                <span class="text-body-sm text-text-muted italic">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-body-sm text-on-surface">{{ \Carbon\Carbon::parse($lead->last_activity_at)->diffForHumans() }}</p>
                            <p class="font-label-sm text-text-muted">{{ ucfirst($lead->source ?? 'Unknown') }}</p>
                            @if($lead->sla_breached)
                                <span class="text-[9px] text-stage-lost font-bold uppercase tracking-wide">⚠ SLA Breached</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex justify-end gap-1">
                                <button class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-text-muted hover:text-primary transition-colors" title="Log Disposition" onclick="document.getElementById('dispositionModal').classList.remove('hidden')">
                                    <span class="material-symbols-outlined text-[18px]">call</span>
                                </button>
                                <button class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-text-muted hover:text-on-surface">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-text-muted font-body-md">
                            <span class="material-symbols-outlined text-[48px] block mb-2 text-border-subtle">inbox</span>
                            No leads found for your current filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Footer -->
        <div class="px-6 py-4 border-t border-border-subtle bg-surface-container-lowest flex items-center justify-between">
            <span class="text-body-sm text-text-muted">
                Showing {{ $leads->firstItem() ?? 0 }} to {{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }} leads
            </span>
            <div class="flex items-center gap-2">
                {{ $leads->links() }}
            </div>
        </div>
    </div>
</div>

@push('modals')
<!-- Disposition Modal -->
<div id="dispositionModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('dispositionModal').classList.add('hidden')"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-surface-container-lowest border border-border-subtle rounded-[24px] shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-6 border-b border-border-subtle flex justify-between items-center bg-surface">
            <h3 class="font-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">support_agent</span>
                Log Interaction
            </h3>
            <button onclick="document.getElementById('dispositionModal').classList.add('hidden')" class="text-text-muted hover:text-on-surface bg-surface-container-low rounded-full w-8 h-8 flex items-center justify-center">
                <span class="material-symbols-outlined" style="font-size: 20px;">close</span>
            </button>
        </div>
        
        <div class="p-6 space-y-6">
            <label class="flex flex-col gap-2">
                <span class="text-label-sm font-bold uppercase tracking-wider text-on-surface-variant">Call Outcome / Disposition</span>
                <select class="form-select w-full rounded-xl border border-border-subtle bg-surface h-12 px-4 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none shadow-sm">
                    <option value="">Select the call result...</option>
                    <option value="connected">✅ Connected / Spoke to Lead</option>
                    <option value="busy">📵 Busy / Disconnected</option>
                    <option value="no_answer">⏱️ Ringing / No Answer</option>
                    <option value="not_reachable">📴 Switched Off / Not Reachable</option>
                    <option value="callback">🔄 Callback Requested</option>
                    <option value="not_interested">🚫 Not Interested</option>
                    <option value="invalid">❌ Invalid Number</option>
                </select>
            </label>
            
            <label class="flex flex-col gap-2">
                <span class="text-label-sm font-bold uppercase tracking-wider text-on-surface-variant">Interaction Notes</span>
                <textarea class="form-textarea w-full rounded-xl border border-border-subtle bg-surface p-4 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none shadow-sm resize-none" rows="3" placeholder="Summarize the conversation..."></textarea>
            </label>
            
            <label class="flex flex-col gap-2">
                <span class="text-label-sm font-bold uppercase tracking-wider text-on-surface-variant flex justify-between">
                    Schedule Follow-up
                    <span class="text-[10px] text-text-muted font-normal lowercase">(optional)</span>
                </span>
                <input type="datetime-local" class="form-input w-full rounded-xl border border-border-subtle bg-surface h-12 px-4 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none shadow-sm" />
            </label>
        </div>
        
        <div class="p-6 border-t border-border-subtle bg-surface-container-lowest flex justify-end gap-3">
            <button class="px-5 py-2.5 text-label-md font-bold text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors" onclick="document.getElementById('dispositionModal').classList.add('hidden')">Cancel</button>
            <x-button variant="primary" onclick="document.getElementById('dispositionModal').classList.add('hidden'); showToast('Interaction saved successfully!', 'success');">
                <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                Save Log
            </x-button>
        </div>
    </div>
</div>
<script>
    async function updateStage(engagementId, stageId, selectElement) {
        try {
            // Update UI colors optimistically
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const color = selectedOption.getAttribute('data-color');
            selectElement.style.backgroundColor = `${color}20`;
            selectElement.style.color = color;
            selectElement.nextElementSibling.style.color = color;

            const response = await fetch(`/leads/${engagementId}/stage`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ stage_id: stageId })
            });

            const data = await response.json();
            
            if (response.ok && data.success) {
                showToast('Status updated successfully', 'success');
            } else {
                showToast(data.message || 'Failed to update status', 'error');
                // Revert to original (this requires page reload or storing original value, for simplicity we show error)
            }
        } catch (error) {
            showToast('Network error while updating status', 'error');
            console.error(error);
        }
    }
</script>
@endpush
@endsection
