@extends('layouts.app')

@section('content')
<div>
    <!-- Header Section -->
    <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 mb-6">
        <div>
            <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg font-bold text-on-surface mb-1">My Pipeline</h2>
            <p class="font-body-md text-body-md text-text-muted">Manage your active leads across all stages.</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Example filters or actions -->
            <x-button variant="secondary">
                <span class="material-symbols-outlined" style="font-size: 18px;">filter_list</span>
                Filter
            </x-button>
        </div>
    </header>

    <!-- Hero Stats Strip (Charts) -->
    <div class="bg-[#E9EFE1] rounded-[20px] p-6 mb-8 flex flex-col sm:flex-row gap-6 border border-[#d2dcc8]">
        <!-- 1. NEW LEADS -->
        <div class="flex-1 bg-white rounded-[16px] p-4 shadow-sm flex flex-col justify-between min-w-[200px]">
            <div class="flex justify-between items-start mb-4">
                <span class="text-text-muted font-label-sm uppercase tracking-wider font-bold">New Leads (5 Days)</span>
                <span class="text-stage-booked font-bold text-label-md flex items-center">
                    <span class="material-symbols-outlined text-[14px]">trending_up</span>
                    {{ $newLeadsTrend > 0 ? '+' : '' }}{{ $newLeadsTrend }}%
                </span>
            </div>
            <div class="flex items-end justify-between gap-2 h-16">
                @php $maxCount = max(collect($newLeadsData)->pluck('count')->max(), 1); @endphp
                @foreach($newLeadsData as $idx => $data)
                    @php $height = max(10, ($data['count'] / $maxCount) * 100); @endphp
                    <div class="flex flex-col items-center gap-1 w-full">
                        <div class="w-full bg-surface-container rounded-t-sm" style="height: {{ $height }}%; background-color: {{ $idx === 4 ? '#000000' : '#E6E6DF' }};"></div>
                        <span class="text-[10px] text-text-muted font-medium">{{ substr($data['day'], 0, 3) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 2. CONVERSION RATE -->
        <div class="flex-1 bg-white rounded-[16px] p-4 shadow-sm flex items-center justify-between min-w-[240px]">
            <div class="relative w-20 h-20 flex-shrink-0">
                <svg viewBox="0 0 36 36" class="w-full h-full transform -rotate-90">
                    <path class="text-surface-container" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="text-stage-booked" stroke-dasharray="{{ $conversionRate }}, 100" stroke-width="3" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="font-bold text-on-surface">{{ $conversionRate }}%</span>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <h4 class="text-text-muted font-label-sm uppercase tracking-wider font-bold mb-1">Conversion Rate</h4>
                <p class="text-body-sm text-text-muted">Target: 65%<br>this quarter.</p>
            </div>
        </div>

        <!-- 3. OPEN FOLLOW-UPS -->
        <div class="flex-1 bg-white rounded-[16px] p-4 shadow-sm flex flex-col justify-center min-w-[200px]">
            <h4 class="text-text-muted font-label-sm uppercase tracking-wider font-bold mb-3 flex items-center gap-1">Open Follow-ups <span class="material-symbols-outlined text-[16px]">arrow_forward</span></h4>
            <div class="flex items-end gap-3">
                <span class="text-headline-lg font-bold text-on-surface leading-none">{{ $openFollowups }}</span>
                @if($overdueFollowups > 0)
                    <span class="bg-stage-lost/10 text-stage-lost px-2 py-1 rounded-full text-label-sm font-medium">{{ $overdueFollowups }} Overdue</span>
                @endif
            </div>
        </div>

        <!-- 4. CLOSED THIS MONTH -->
        <div class="flex-1 bg-white rounded-[16px] p-4 shadow-sm flex flex-col justify-between min-w-[200px]">
            <h4 class="text-text-muted font-label-sm uppercase tracking-wider font-bold mb-2">Closed This Month</h4>
            <div>
                <div class="mb-2">
                    <span class="text-headline-sm font-bold text-on-surface">{{ $closedThisMonth }}</span>
                    <span class="text-body-sm text-text-muted"> / {{ $closedGoal }} Goal</span>
                </div>
                <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                    <div class="h-full bg-stage-booked rounded-full" style="width: {{ min(100, ($closedThisMonth / max(1, $closedGoal)) * 100) }}%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline Overview (Kanban Board) -->
    <div class="mb-4 flex items-center justify-between">
        <h3 class="font-headline-sm text-on-surface">Pipeline Overview</h3>
        <div class="flex items-center gap-2">
            <button class="w-8 h-8 rounded-md bg-surface-container flex items-center justify-center text-on-surface hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined text-[18px]">view_list</span></button>
            <button class="w-8 h-8 rounded-md bg-surface-container-high flex items-center justify-center text-on-surface"><span class="material-symbols-outlined text-[18px]">view_kanban</span></button>
        </div>
    </div>

    <!-- Kanban Container -->
    <div class="flex gap-6 overflow-x-auto pb-8 snap-x snap-mandatory hide-scrollbar">
        @foreach($stages as $stage)
        @php
            $stageLeads = $leadsByStage[$stage->id] ?? collect();
            $count = $stageLeads->count();
        @endphp
        <!-- Column -->
        <div class="flex-shrink-0 w-[320px] snap-start flex flex-col">
            <!-- Column Header -->
            <div class="flex items-center justify-between mb-4 px-1 pb-2 border-b border-border-subtle" style="border-bottom-color: {{ $stage->color }}40; border-bottom-width: 2px;">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $stage->color }}"></div>
                    <h4 class="font-label-sm uppercase tracking-wider text-on-surface font-bold">{{ $stage->name }}</h4>
                </div>
                <span class="w-6 h-6 rounded-full bg-surface-container flex items-center justify-center font-label-sm text-primary">{{ $count }}</span>
            </div>

            <!-- Column Body -->
            <div class="flex flex-col gap-3 min-h-[150px]">
                @if($count > 0)
                    @foreach($stageLeads->take(10) as $engagement)
                    <!-- Lead Card -->
                    <div class="bg-surface-container-lowest border border-border-subtle rounded-[16px] p-4 shadow-sm hover:shadow-md transition-shadow relative {{ $engagement->sla_breached ? 'ring-1 ring-stage-lost' : '' }}">
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-2 py-0.5 rounded bg-surface-container text-text-muted font-label-sm text-[10px] uppercase">{{ $engagement->source ?? 'Manual' }}</span>
                            <button class="text-on-surface-variant hover:text-primary transition-colors"><span class="material-symbols-outlined text-[16px]">more_horiz</span></button>
                        </div>
                        
                        <h5 class="font-headline-sm text-on-surface truncate mb-1">{{ $engagement->lead->name }}</h5>
                        <p class="font-body-sm text-text-muted font-mono tracking-wider truncate mb-4">{{ $engagement->lead->phone_e164 }}</p>
                        
                        <div class="flex items-center justify-between pt-3 border-t border-border-subtle">
                            <div class="flex items-center gap-1 text-text-muted text-[11px] font-medium">
                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                {{ \Carbon\Carbon::parse($engagement->updated_at)->diffForHumans(null, true) }}
                            </div>
                            <div class="flex gap-1.5">
                                <button type="button" class="w-7 h-7 rounded-full bg-surface-container hover:bg-primary hover:text-on-primary flex items-center justify-center text-on-surface-variant transition-colors" title="Log Interaction" onclick="openDispositionModal({{ $engagement->id }})">
                                    <span class="material-symbols-outlined text-[14px]">call</span>
                                </button>
                            </div>
                        </div>
                        @if($engagement->sla_breached)
                            <div class="absolute -top-2 -right-2 bg-stage-lost text-white text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded flex items-center gap-0.5 shadow-sm">
                                <span class="material-symbols-outlined text-[10px]">warning</span> SLA
                            </div>
                        @endif
                    </div>
                    @endforeach
                @else
                    <div class="w-full py-8 text-center border-2 border-dashed border-border-subtle rounded-xl text-text-muted font-body-sm">
                        No leads
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

@push('modals')
<!-- Disposition Modal -->
<div id="dispositionModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDispositionModal()"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-surface-container-lowest border border-border-subtle rounded-[24px] shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-6 border-b border-border-subtle flex justify-between items-center bg-surface">
            <h3 class="font-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">support_agent</span>
                Log Interaction
            </h3>
            <button onclick="closeDispositionModal()" class="text-text-muted hover:text-on-surface bg-surface-container-low rounded-full w-8 h-8 flex items-center justify-center">
                <span class="material-symbols-outlined" style="font-size: 20px;">close</span>
            </button>
        </div>
        
        <form id="dispositionForm">
            <input type="hidden" id="modalEngagementId" name="engagement_id">
            <div class="p-6 space-y-6">
                <label class="flex flex-col gap-2">
                    <span class="text-label-sm font-bold uppercase tracking-wider text-on-surface-variant">Call Outcome / Disposition</span>
                    <select id="dispositionSelect" name="disposition_id" required class="form-select w-full rounded-xl border border-border-subtle bg-surface h-12 px-4 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none shadow-sm">
                        <option value="">Select the call result...</option>
                    </select>
                </label>
                
                <label class="flex flex-col gap-2">
                    <span class="text-label-sm font-bold uppercase tracking-wider text-on-surface-variant">Interaction Notes</span>
                    <textarea id="dispositionNotes" name="notes" class="form-textarea w-full rounded-xl border border-border-subtle bg-surface p-4 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none shadow-sm resize-none" rows="3" placeholder="Summarize the conversation..."></textarea>
                </label>
                
                <label class="flex flex-col gap-2">
                    <span class="text-label-sm font-bold uppercase tracking-wider text-on-surface-variant flex justify-between">
                        Schedule Follow-up
                        <span class="text-[10px] text-text-muted font-normal lowercase">(optional)</span>
                    </span>
                    <input id="dispositionFollowUp" name="follow_up_at" type="datetime-local" class="form-input w-full rounded-xl border border-border-subtle bg-surface h-12 px-4 text-body-sm text-on-surface focus:ring-2 focus:ring-primary outline-none shadow-sm" />
                </label>
            </div>
            
            <div class="p-6 border-t border-border-subtle bg-surface-container-lowest flex justify-end gap-3">
                <button type="button" class="px-5 py-2.5 text-label-md font-bold text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors" onclick="closeDispositionModal()">Cancel</button>
                <x-button type="submit" variant="primary">
                    <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                    Save Log
                </x-button>
            </div>
        </form>
    </div>
</div>
<script>
    let dispositionsCache = null;

    async function openDispositionModal(engagementId) {
        document.getElementById('modalEngagementId').value = engagementId;
        document.getElementById('dispositionNotes').value = '';
        document.getElementById('dispositionFollowUp').value = '';
        
        const select = document.getElementById('dispositionSelect');
        if (!dispositionsCache) {
            try {
                const response = await fetch('/leads/dispositions');
                if (response.ok) {
                    dispositionsCache = await response.json();
                }
            } catch (e) {
                console.error('Failed to load dispositions');
            }
        }
        
        if (dispositionsCache) {
            select.innerHTML = '<option value="">Select the call result...</option>';
            dispositionsCache.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.label;
                select.appendChild(opt);
            });
        }
        
        document.getElementById('dispositionModal').classList.remove('hidden');
    }

    function closeDispositionModal() {
        document.getElementById('dispositionModal').classList.add('hidden');
    }

    document.getElementById('dispositionForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const engagementId = document.getElementById('modalEngagementId').value;
        const submitBtn = e.target.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());

            const response = await fetch(`/leads/${engagementId}/log-activity`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast('Interaction saved successfully!', 'success');
                closeDispositionModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to save interaction', 'error');
            }
        } catch (error) {
            showToast('Network error while saving', 'error');
            console.error(error);
        } finally {
            submitBtn.disabled = false;
        }
    });
</script>
@endpush
@endsection
