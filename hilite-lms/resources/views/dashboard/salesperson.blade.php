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
        <x-kpi-widget label="Active Leads" value="42" subtext="total" />
        <div class="hidden sm:block w-px bg-[#d2dcc8]"></div>
        <x-kpi-widget label="Pending Follow-ups" value="8" valueColor="text-stage-contacted" />
        <div class="hidden sm:block w-px bg-[#d2dcc8]"></div>
        <x-kpi-widget label="SLA Breaches" value="2" valueColor="text-stage-lost" />
    </div>

    <!-- Pipeline Stages (Vertical Accordion) -->
    <div class="space-y-4 pb-12">
        @php
            $stages = [
                ['id' => 'new', 'name' => 'New', 'color' => 'bg-stage-new', 'count' => 12],
                ['id' => 'contacted', 'name' => 'Contacted', 'color' => 'bg-stage-contacted', 'count' => 8],
                ['id' => 'interested', 'name' => 'Interested', 'color' => 'bg-stage-interested', 'count' => 5],
                ['id' => 'site-visit', 'name' => 'Site Visit', 'color' => 'bg-stage-site-visit', 'count' => 3],
                ['id' => 'negotiation', 'name' => 'Negotiation', 'color' => 'bg-stage-negotiation', 'count' => 2],
                ['id' => 'booked', 'name' => 'Booked', 'color' => 'bg-stage-booked', 'count' => 10],
                ['id' => 'lost', 'name' => 'Lost', 'color' => 'bg-stage-lost', 'count' => 0],
                ['id' => 'not-interested', 'name' => 'Not Interested', 'color' => 'bg-stage-not-interested', 'count' => 2],
            ];
        @endphp

        @foreach($stages as $index => $stage)
        <!-- Accordion Item -->
        <div class="bg-surface-container-low rounded-2xl border border-border-subtle overflow-hidden shadow-sm">
            <!-- Header -->
            <button onclick="toggleAccordion(this)" class="w-full p-5 bg-surface-container-lowest flex items-center justify-between hover:bg-surface-container transition-colors outline-none focus:ring-2 focus:ring-primary focus:ring-inset">
                <div class="flex items-center gap-4">
                    <div class="w-4 h-4 rounded-full {{ $stage['color'] }}"></div>
                    <h3 class="font-headline-sm md:font-headline-md text-headline-sm md:text-headline-md text-on-surface">{{ $stage['name'] }}</h3>
                    <span class="px-3 py-1 rounded-full bg-surface-container-high text-label-md font-bold text-on-surface-variant">{{ $stage['count'] }}</span>
                </div>
                <!-- Default open the first one with count > 0 -->
                @php $isOpen = $index === 0 && $stage['count'] > 0; @endphp
                <span class="material-symbols-outlined transition-transform duration-200 chevron-icon {{ $isOpen ? 'rotate-180' : '' }}">expand_more</span>
            </button>
            
            <!-- Body -->
            <div class="accordion-body {{ $isOpen ? 'grid' : 'hidden' }} p-5 bg-surface-container-lowest border-t border-border-subtle grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                
                @if($stage['count'] > 0)
                    <!-- Dummy Cards -->
                    @for($i = 0; $i < min($stage['count'], 4); $i++)
                    <div class="bg-surface border border-border-subtle rounded-xl p-4 hover:shadow-md transition-shadow relative group">
                        <div class="flex justify-between items-start mb-2">
                            <p class="font-headline-sm text-on-surface truncate pr-6">John Doe {{ $i+1 }}</p>
                            @if($i == 0)
                                <span class="material-symbols-outlined text-[18px] text-stage-contacted absolute top-4 right-4" title="Follow up due soon">timer</span>
                            @endif
                        </div>
                        <p class="font-body-sm text-text-muted font-mono tracking-wider truncate mb-4">+1 (555) ***-**67</p>
                        
                        <div class="flex items-center justify-between pt-3 border-t border-border-subtle">
                            <span class="text-[10px] text-text-muted font-bold uppercase tracking-wide">2 days in stage</span>
                            <div class="flex gap-2">
                                <button class="w-8 h-8 rounded-full bg-surface-container hover:bg-primary/10 flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors" title="Log Call Disposition" onclick="document.getElementById('dispositionModal').classList.remove('hidden')">
                                    <span class="material-symbols-outlined text-[16px]">call</span>
                                </button>
                                <button class="w-8 h-8 rounded-full bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors" title="View Details">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endfor
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
            <x-button variant="primary" onclick="document.getElementById('dispositionModal').classList.add('hidden'); alert('Interaction and disposition saved successfully!');">
                <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                Save Log
            </x-button>
        </div>
    </div>
</div>
@endpush
@endsection
