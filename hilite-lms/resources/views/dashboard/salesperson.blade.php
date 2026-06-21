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

    <!-- Hero Stats Strip -->
    <div class="bg-[#E9EFE1] rounded-[20px] p-6 mb-8 flex flex-col sm:flex-row gap-6 border border-[#d2dcc8]">
        <x-kpi-widget label="Active Leads" value="{{ $activeLeads }}" subtext="total" />
        <div class="hidden sm:block w-px bg-[#d2dcc8]"></div>
        <x-kpi-widget label="Pending Follow-ups" value="{{ $pendingFollowups }}" valueColor="text-stage-contacted" />
        <div class="hidden sm:block w-px bg-[#d2dcc8]"></div>
        <x-kpi-widget label="SLA Breaches" value="{{ $slaBreaches }}" valueColor="text-stage-lost" />
    </div>

    <!-- Pipeline Stages (Vertical Accordion) -->
    <div class="space-y-4 pb-12">
        @foreach($stages as $index => $stage)
        @php
            $stageLeads = $leadsByStage[$stage->id] ?? collect();
            $count = $stageLeads->count();
            $isOpen = ($index === 0 && $count > 0);
        @endphp
        <!-- Accordion Item -->
        <div class="bg-surface-container-low rounded-2xl border border-border-subtle overflow-hidden shadow-sm">
            <!-- Header -->
            <button onclick="toggleAccordion(this)" class="w-full p-5 bg-surface-container-lowest flex items-center justify-between hover:bg-surface-container transition-colors outline-none focus:ring-2 focus:ring-primary focus:ring-inset">
                <div class="flex items-center gap-4">
                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $stage->color }}"></div>
                    <h3 class="font-headline-sm md:font-headline-md text-headline-sm md:text-headline-md text-on-surface">{{ $stage->name }}</h3>
                    <span class="px-3 py-1 rounded-full bg-surface-container-high text-label-md font-bold text-on-surface-variant">{{ $count }}</span>
                </div>
                <span class="material-symbols-outlined transition-transform duration-200 chevron-icon {{ $isOpen ? 'rotate-180' : '' }}">expand_more</span>
            </button>
            
            <!-- Body -->
            <div class="accordion-body {{ $isOpen ? 'grid' : 'hidden' }} p-5 bg-surface-container-lowest border-t border-border-subtle grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                
                @if($count > 0)
                    @foreach($stageLeads->take(4) as $engagement)
                    <div class="bg-surface border border-border-subtle rounded-xl p-4 hover:shadow-md transition-shadow relative group">
                        <div class="flex justify-between items-start mb-2">
                            <p class="font-headline-sm text-on-surface truncate pr-6">{{ $engagement->lead->name }}</p>
                            @if($engagement->sla_breached)
                                <span class="material-symbols-outlined text-[18px] text-stage-lost absolute top-4 right-4" title="SLA Breached">warning</span>
                            @elseif($engagement->activities->first() && $engagement->activities->first()->follow_up_at && $engagement->activities->first()->follow_up_at->isPast())
                                <span class="material-symbols-outlined text-[18px] text-stage-contacted absolute top-4 right-4" title="Follow up overdue">timer</span>
                            @endif
                        </div>
                        <p class="font-body-sm text-text-muted font-mono tracking-wider truncate mb-4">{{ $engagement->lead->phone_e164 }}</p>
                        
                        <div class="flex items-center justify-between pt-3 border-t border-border-subtle">
                            <span class="text-[10px] text-text-muted font-bold uppercase tracking-wide">
                                {{ \Carbon\Carbon::parse($engagement->updated_at)->diffForHumans(null, true) }} in stage
                            </span>
                            <div class="flex gap-2">
                                <button type="button" class="w-8 h-8 rounded-full bg-surface-container hover:bg-primary/10 flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors" title="Log Call Disposition" onclick="openDispositionModal({{ $engagement->id }})">
                                    <span class="material-symbols-outlined text-[16px]">call</span>
                                </button>
                                <a href="#" class="w-8 h-8 rounded-full bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors" title="View Details">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="col-span-full py-8 text-center text-text-muted font-body-md">
                        No leads currently in this stage.
                    </div>
                @endif

            </div>
        </div>
        @endforeach

    </div>
</div>

<script>
function toggleAccordion(button) {
    const body = button.nextElementSibling;
    const chevron = button.querySelector('.chevron-icon');
    
    if (body.classList.contains('hidden')) {
        body.classList.remove('hidden');
        body.classList.add('grid');
        chevron.classList.add('rotate-180');
    } else {
        body.classList.add('hidden');
        body.classList.remove('grid');
        chevron.classList.remove('rotate-180');
    }
}
</script>

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
