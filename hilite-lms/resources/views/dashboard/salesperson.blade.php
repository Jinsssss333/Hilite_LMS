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

    <!-- Stitch Hero Stats Area -->
    <section class="bg-[#E9EFE1] rounded-[20px] p-8 mb-8 flex flex-col md:flex-row justify-between gap-8 border border-[#d2dcc8]">
        <div class="flex-1">
            <h2 class="font-headline-lg text-headline-lg text-primary mb-2">Morning, {{ \App\Http\Helpers\AuthHelper::user()->name ?? 'User' }}.</h2>
            <p class="font-body-lg text-body-lg text-secondary max-w-lg">You have {{ $pendingFollowups }} priority follow-ups today and {{ $slaBreaches }} SLA breaches. Your pipeline health is {{ $totalLeadsTrend >= 0 ? 'up' : 'down' }} {{ abs($totalLeadsTrend) }}% this month.</p>
        </div>
        <div class="flex flex-wrap gap-4">
            <div class="bg-white rounded-xl p-6 w-48 border border-border-subtle shadow-sm">
                <p class="font-label-sm text-label-sm text-text-muted mb-1">TOTAL LEADS</p>
                <p class="font-headline-md text-headline-md text-primary">{{ number_format($totalLeads) }}</p>
                <div class="flex items-center gap-1 {{ $totalLeadsTrend >= 0 ? 'text-stage-booked' : 'text-stage-lost' }} mt-2">
                    <span class="material-symbols-outlined text-[16px]">{{ $totalLeadsTrend >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    <span class="font-label-sm text-label-sm">{{ $totalLeadsTrend >= 0 ? '+' : '' }}{{ $totalLeadsTrend }}%</span>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 w-48 border border-border-subtle shadow-sm">
                <p class="font-label-sm text-label-sm text-text-muted mb-1">AVG. CLOSE TIME</p>
                <p class="font-headline-md text-headline-md text-primary">{{ $avgCloseTime }}d</p>
                <div class="flex items-center gap-1 text-text-muted mt-2">
                    <span class="material-symbols-outlined text-[16px]">schedule</span>
                    <span class="font-label-sm text-label-sm">Days to close</span>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 w-48 border border-border-subtle shadow-sm">
                <p class="font-label-sm text-label-sm text-text-muted mb-1">CONVERSION</p>
                <p class="font-headline-md text-headline-md text-primary">{{ $conversionRate }}%</p>
                <div class="flex items-center gap-1 {{ $conversionRate >= 15 ? 'text-stage-interested' : 'text-stage-contacted' }} mt-2">
                    <span class="material-symbols-outlined text-[16px]">{{ $conversionRate >= 15 ? 'check_circle' : 'warning' }}</span>
                    <span class="font-label-sm text-label-sm">{{ $conversionRate >= 15 ? 'High' : 'Needs Impr.' }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Bento Grid Insights -->
    <div class="grid grid-cols-12 gap-8 mb-8">
        <!-- Activity Trends -->
        <div class="col-span-12 lg:col-span-8 bg-white border border-border-subtle rounded-xl p-6 shadow-sm">
            <div class="flex justify-between items-center mb-10">
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-primary">Activity Trends</h3>
                    <p class="font-body-sm text-body-sm text-text-muted">Lead engagement performance over 7 days</p>
                </div>
            </div>
            <div class="flex items-end justify-between h-48 px-4 gap-2">
                @foreach($activityTrends as $trend)
                <div class="flex-1 flex flex-col items-center gap-2">
                    <div class="w-full bg-surface-container rounded-t-lg hover:bg-primary transition-all duration-300" style="height: {{ $trend['percent'] }}%;" title="{{ $trend['count'] }} activities"></div>
                    <span class="font-label-sm text-label-sm text-text-muted">{{ $trend['day'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Distribution Donut -->
        <div class="col-span-12 lg:col-span-4 bg-white border border-border-subtle rounded-xl p-6 shadow-sm flex flex-col">
            <h3 class="font-headline-sm text-headline-sm text-primary mb-1">Lead Distribution</h3>
            <p class="font-body-sm text-body-sm text-text-muted mb-8">By current sales stage</p>
            
            <div class="flex-1 flex items-center justify-center relative">
                <!-- SVG Circular Gauge -->
                <svg class="w-48 h-48 transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" fill="none" r="40" stroke="#f1eded" stroke-width="12"></circle>
                    @php $dashOffset = 0; @endphp
                    @foreach($leadDistribution as $dist)
                        @if($dist['percent'] > 0)
                            @php 
                                $dashArray = ($dist['percent'] / 100) * 251.2; 
                                $restArray = 251.2 - $dashArray;
                            @endphp
                            <circle cx="50" cy="50" fill="none" r="40" stroke="{{ $dist['color'] }}" stroke-dasharray="{{ $dashArray }} {{ $restArray }}" stroke-dashoffset="-{{ $dashOffset }}" stroke-width="12"></circle>
                            @php $dashOffset += $dashArray; @endphp
                        @endif
                    @endforeach
                </svg>
                <div class="absolute text-center">
                    <p class="font-headline-md text-headline-md text-primary">{{ $leadDistribution[0]['percent'] ?? 0 }}%</p>
                    <p class="font-label-sm text-label-sm text-text-muted">{{ $leadDistribution[0]['name'] ?? 'Stage' }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                @foreach($leadDistribution as $dist)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $dist['color'] }};"></div>
                        <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $dist['name'] }}</span>
                    </div>
                    <span class="font-label-md text-label-md text-primary">{{ $dist['count'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Priority Engagements Table -->
    <section class="bg-white border border-border-subtle rounded-xl overflow-hidden shadow-sm mb-8">
        <div class="px-6 py-5 border-b border-border-subtle flex justify-between items-center">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-primary">Priority Engagements</h3>
                <p class="font-body-sm text-body-sm text-text-muted">Actions requiring immediate attention</p>
            </div>
            <a href="#pipeline-kanban" class="flex items-center gap-2 text-primary font-label-md text-label-md hover:underline">
                View all pipeline <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-surface-container-low border-b border-border-subtle">
                        <th class="px-6 py-4 font-label-md text-label-md text-text-muted">CLIENT / LEAD</th>
                        <th class="px-6 py-4 font-label-md text-label-md text-text-muted">STAGE</th>
                        <th class="px-6 py-4 font-label-md text-label-md text-text-muted">LAST ACTIVITY</th>
                        <th class="px-6 py-4 font-label-md text-label-md text-text-muted">STATUS</th>
                        <th class="px-6 py-4 font-label-md text-label-md text-text-muted text-right">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    @forelse($priorityEngagements as $engagement)
                    <tr class="hover:bg-surface-container-lowest transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center font-bold text-primary">
                                    {{ strtoupper(substr($engagement->lead->first_name, 0, 1) . substr($engagement->lead->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-label-md text-label-md text-primary">{{ $engagement->lead->first_name }} {{ $engagement->lead->last_name }}</p>
                                    <p class="font-body-sm text-body-sm text-text-muted">{{ $engagement->lead->email ?? $engagement->lead->phone }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-opacity-10 rounded-full font-label-sm text-label-sm" style="color: {{ $engagement->stage->color ?? '#6366F1' }}; background-color: {{ $engagement->stage->color ?? '#6366F1' }}1a; border: 1px solid {{ $engagement->stage->color ?? '#6366F1' }};">{{ $engagement->stage->name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($engagement->activities->count() > 0)
                                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $engagement->activities->first()->title ?? ucfirst($engagement->activities->first()->type) }}</p>
                                <p class="font-label-sm text-label-sm text-text-muted">{{ $engagement->activities->first()->created_at->diffForHumans() }}</p>
                            @else
                                <p class="font-body-sm text-body-sm text-text-muted">No activity yet</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if($engagement->sla_breached)
                                    <div class="w-2 h-2 rounded-full bg-stage-lost animate-pulse"></div>
                                    <span class="font-label-sm text-label-sm text-stage-lost font-bold">SLA BREACH</span>
                                @else
                                    <div class="w-2 h-2 rounded-full bg-stage-contacted"></div>
                                    <span class="font-label-sm text-label-sm text-stage-contacted font-bold">OVERDUE</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('leads.show', $engagement->id) }}" class="p-2 rounded-full group-hover:bg-primary group-hover:text-white transition-all text-primary">
                                <span class="material-symbols-outlined">visibility</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-text-muted">
                            <span class="material-symbols-outlined text-[32px] mb-2 text-surface-dim">check_circle</span>
                            <p class="font-body-sm">No priority engagements right now. You're all caught up!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Pipeline Overview (Kanban Board) -->
    <div class="mb-4 flex items-center justify-between" id="pipeline-kanban">
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
