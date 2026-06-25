@extends('layouts.app')

@section('content')
<!-- Header & Filters Area -->
<div class="py-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary">Unassigned Engagements</h2>
        <p class="font-body-md text-body-md text-on-surface-variant mt-2 max-w-2xl">Select leads below to assign them in bulk to your team representatives. Ensure workload balance before assignment.</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-sm">search</span>
            <input class="pl-9 pr-4 py-2 bg-surface-container-lowest border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none font-body-md text-body-md w-full md:w-64 transition-all" placeholder="Search leads..." type="text">
        </div>
        <button class="bg-surface-container-lowest border border-border-subtle p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-low transition-colors focus:ring-2 focus:ring-primary/20 outline-none">
            <span class="material-symbols-outlined">filter_list</span>
        </button>
    </div>
</div>

<!-- High-Density Lead List -->
<form method="POST" action="{{ route('assignment.bulk') }}" id="assignment-form">
    @csrf
<div class="bg-surface-container-lowest border border-border-subtle rounded-xl overflow-hidden shadow-sm">
    <!-- Table Header -->
    <div class="grid grid-cols-[40px_minmax(200px,_1fr)_minmax(120px,_1fr)_minmax(150px,_1fr)_minmax(120px,_1fr)_100px] gap-4 p-4 border-b border-border-subtle bg-surface-container-low/50">
        <div class="flex items-center justify-center">
            <input class="soft-checkbox" id="selectAll" type="checkbox">
        </div>
        <div class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Lead Details</div>
        <div class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Source</div>
        <div class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Interest Level</div>
        <div class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Time in Queue</div>
        <div class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-right">Action</div>
    </div>
    
    <!-- List Body -->
    <div class="divide-y divide-border-subtle">
        @forelse($unassigned as $e)
        <div class="lead-row grid grid-cols-[40px_minmax(200px,_1fr)_minmax(120px,_1fr)_minmax(150px,_1fr)_minmax(120px,_1fr)_100px] gap-4 p-4 items-center hover:bg-surface-container-low/30 transition-colors">
            <div class="flex items-center justify-center">
                <input class="soft-checkbox lead-checkbox" name="engagement_ids[]" value="{{ $e->id }}" type="checkbox">
            </div>
            <div>
                <div class="font-label-md text-label-md text-primary mb-1">{{ $e->lead->name }}</div>
                <div class="font-body-sm text-body-sm text-text-muted flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">call</span> {{ $e->lead->phone_e164 }}
                </div>
            </div>
            <div class="font-body-sm text-body-sm text-on-surface-variant">{{ ucfirst($e->source) }}</div>
            <div>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-white font-label-sm text-label-sm" style="background-color: {{ $e->stage->color }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-white mr-1.5 opacity-75"></span> {{ $e->stage->name }}
                </span>
            </div>
            <div class="font-body-sm text-body-sm {{ $e->sla_breached ? 'text-error font-medium' : 'text-on-surface-variant' }}">
                {{ \Carbon\Carbon::parse($e->created_at)->diffForHumans(null, true) }}
            </div>
            <div class="text-right">
                <button type="button" class="text-primary hover:bg-surface-container-low p-1.5 rounded-md transition-colors">
                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                </button>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-text-muted font-body-md">
            No unassigned leads found.
        </div>
        @endforelse
    </div>
    
    <!-- Pagination Footer -->
    <div class="p-4 border-t border-border-subtle bg-surface-container-low/30">
        {{ $unassigned->links() }}
    </div>
</div>

<!-- Floating Bulk Action Bar -->
<div class="fixed bottom-6 left-1/2 -translate-x-1/2 w-[90%] md:w-auto md:min-w-[600px] bg-primary-container text-on-primary-container rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-primary-fixed/20 p-4 z-50 flex flex-col md:flex-row items-center gap-6 translate-y-[150%] transition-transform duration-300" id="bulk-action-bar">
    <!-- Selection Info -->
    <div class="flex items-center gap-3 shrink-0">
        <div class="w-10 h-10 rounded-full bg-primary-fixed text-primary-fixed-dim flex items-center justify-center font-headline-sm text-headline-sm">
            <span id="selected-count">0</span>
        </div>
        <div>
            <div class="font-label-md text-label-md text-on-primary">Leads Selected</div>
            <button class="font-label-sm text-label-sm text-inverse-primary hover:text-on-primary underline decoration-dotted mt-0.5" id="clear-selection">Clear all</button>
        </div>
    </div>
    <!-- Assignment Controls -->
    <div class="flex-1 flex flex-col md:flex-row items-center gap-4 w-full border-t md:border-t-0 md:border-l border-primary-fixed/20 pt-4 md:pt-0 md:pl-6">
        <div class="w-full flex-1">
            <label class="font-label-sm text-label-sm text-inverse-primary block mb-1.5">Assign To Representative</label>
            <div class="relative w-full">
                <select name="assign_to_user_id" required class="w-full appearance-none bg-inverse-surface border border-outline/50 rounded-lg py-2 pl-3 pr-8 font-body-sm text-body-sm text-on-primary focus:ring-2 focus:ring-primary-fixed focus:border-primary-fixed outline-none">
                    <option disabled selected value="">Select Rep...</option>
                    @foreach($assignableUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-sm">arrow_drop_down</span>
            </div>
        </div>
        <div class="w-full flex-1">
            <label class="font-label-sm text-label-sm text-inverse-primary block mb-1.5">Assignment Reason (Optional)</label>
            <input name="reason" class="w-full bg-inverse-surface border border-outline/50 rounded-lg py-2 px-3 font-body-sm text-body-sm text-on-primary focus:ring-2 focus:ring-primary-fixed focus:border-primary-fixed outline-none placeholder:text-outline" placeholder="e.g. Territory match" type="text">
        </div>
        <button type="submit" class="w-full md:w-auto mt-4 md:mt-0 shrink-0 bg-primary-fixed text-on-primary-fixed font-label-md text-label-md py-2.5 px-6 rounded-lg hover:bg-primary-fixed-dim transition-colors focus:ring-2 focus:ring-primary-fixed/50 outline-none">
            Assign Leads
        </button>
    </div>
</div>
</form>

<style>
    /* Soft Paper Checkbox */
    .soft-checkbox {
        appearance: none;
        width: 18px;
        height: 18px;
        border: 1px solid #c5c6ca;
        border-radius: 4px;
        background-color: #ffffff;
        cursor: pointer;
        position: relative;
        transition: all 0.2s ease;
    }
    .soft-checkbox:checked {
        background-color: #1a1c19;
        border-color: #1a1c19;
    }
    .soft-checkbox:checked::after {
        content: '';
        position: absolute;
        left: 5px;
        top: 2px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }
    
    #bulk-action-bar.visible {
        transform: translateY(0);
    }
</style>

<script>
    function initBulkAction() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;

        const checkboxes = document.querySelectorAll('.lead-checkbox');
        const actionBar = document.getElementById('bulk-action-bar');
        const countDisplay = document.getElementById('selected-count');
        const clearBtn = document.getElementById('clear-selection');
        const rows = document.querySelectorAll('.lead-row');

        // Remove old listeners if any (by replacing elements with clones, or just use Alpine, but for now we keep it simple)
        // Since Turbo replaces the body, the elements are fresh, so adding listeners is safe.

        function updateActionBar() {
            const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            countDisplay.textContent = selectedCount;
            
            if (selectedCount > 0) {
                actionBar.classList.add('visible');
                actionBar.classList.remove('translate-y-[150%]');
            } else {
                actionBar.classList.remove('visible');
                actionBar.classList.add('translate-y-[150%]');
                selectAll.checked = false;
            }

            // Update row styles based on selection
            checkboxes.forEach((cb, index) => {
                if (cb.checked) {
                    rows[index].classList.add('bg-surface-container-low', 'border-l-2', 'border-primary');
                } else {
                    rows[index].classList.remove('bg-surface-container-low', 'border-l-2', 'border-primary');
                }
            });
        }

        selectAll.addEventListener('change', (e) => {
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            updateActionBar();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                selectAll.checked = allChecked;
                updateActionBar();
            });
        });

        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            checkboxes.forEach(cb => cb.checked = false);
            selectAll.checked = false;
            updateActionBar();
        });
        
        rows.forEach((row, index) => {
            row.addEventListener('click', (e) => {
                if(e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'SPAN' && e.target.tagName !== 'SELECT' && e.target.closest('button') === null) {
                    checkboxes[index].checked = !checkboxes[index].checked;
                    updateActionBar();
                }
            });
        });
    }

    // Run immediately for normal load or Turbo script evaluation
    initBulkAction();
    
    // Also bind to Turbo events just in case it's loaded via cache
    document.addEventListener("turbo:load", initBulkAction);
</script>
@endsection
