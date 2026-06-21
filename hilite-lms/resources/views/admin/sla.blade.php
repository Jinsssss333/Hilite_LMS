@extends('layouts.app')

@section('content')
<div class="max-w-[800px] w-full mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.index') }}" class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-1">SLA Policies</h2>
            <p class="font-body-md text-body-md text-text-muted">Set automatic dormancy rules and response time targets.</p>
        </div>
        <div class="ml-auto">
        <div class="ml-auto">
            <x-button @click="$dispatch('open-add-sla-modal')">
                <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                Add Policy
            </x-button>
        </div>
    </div>

    <!-- Modals -->
    <div x-data="{ addModalOpen: false, editModalOpen: false, editPolicy: {} }"
         @open-add-sla-modal.window="addModalOpen = true"
         @open-edit-sla-modal.window="editPolicy = $event.detail; editModalOpen = true">
        
        <!-- Add SLA Modal -->
        <div x-show="addModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="addModalOpen = false" class="bg-surface rounded-2xl p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Add SLA Policy</h3>
                    <button @click="addModalOpen = false" class="text-text-muted hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form action="{{ route('admin.sla.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Stage</label>
                        <select name="stage_id" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow appearance-none">
                            @foreach($stages as $stage)
                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Max Days (SLA)</label>
                        <input type="number" name="sla_days" required min="1" class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Escalate To Role (Optional)</label>
                        <select name="escalate_to_role" class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow appearance-none">
                            <option value="">None</option>
                            <option value="manager">Manager</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="branch_head">Branch Head</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-text-muted hover:text-on-surface font-label-md">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:bg-primary/90 transition-colors">Create Policy</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit SLA Modal -->
        <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="editModalOpen = false" class="bg-surface rounded-2xl p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Edit SLA Policy</h3>
                    <button @click="editModalOpen = false" class="text-text-muted hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form :action="`{{ url('/admin/sla') }}/${editPolicy.id}`" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Stage</label>
                        <select name="stage_id" x-model="editPolicy.stage_id" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow appearance-none">
                            @foreach($stages as $stage)
                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Max Days (SLA)</label>
                        <input type="number" name="sla_days" x-model="editPolicy.sla_days" required min="1" class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Escalate To Role (Optional)</label>
                        <select name="escalate_to_role" x-model="editPolicy.escalate_to_role" class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow appearance-none">
                            <option value="">None</option>
                            <option value="manager">Manager</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="branch_head">Branch Head</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-text-muted hover:text-on-surface font-label-md">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:bg-primary/90 transition-colors">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-stage-booked/10 text-stage-booked rounded-lg font-body-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-stage-lost/10 text-stage-lost rounded-lg font-body-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 bg-stage-lost/10 text-stage-lost rounded-lg font-body-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card class="!p-0">
        <div class="p-4 border-b border-border-subtle bg-surface-container-low flex justify-between font-label-sm text-text-muted uppercase tracking-wider">
            <div class="flex-1">Stage / Target</div>
            <div class="w-32 text-center">Max Days</div>
            <div class="w-24 text-center">Status</div>
            <div class="w-24 text-right">Actions</div>
        </div>
        
        <div class="divide-y divide-border-subtle">
            @forelse($policies as $policy)
            <div class="flex items-center p-4 hover:bg-surface-container-lowest transition-colors">
                <div class="flex-1">
                    <span class="font-medium text-primary block">{{ $policy->stage->name ?? 'Unknown Stage' }} SLA</span>
                    <span class="text-text-muted font-body-sm">Escalates to: {{ ucfirst(str_replace('_', ' ', $policy->escalate_to_role ?? 'None')) }}</span>
                </div>
                <div class="w-32 text-center font-medium text-on-surface-variant">
                    {{ $policy->sla_days ?? 'N/A' }} Days
                </div>
                <div class="w-24 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stage-booked/10 text-stage-booked font-label-sm">Active</span>
                </div>
                <div class="w-24 text-right flex justify-end gap-2">
                    <button @click="$dispatch('open-edit-sla-modal', { id: '{{ $policy->id }}', stage_id: '{{ $policy->stage_id }}', sla_days: {{ $policy->sla_days }}, escalate_to_role: '{{ $policy->escalate_to_role }}' })" class="text-primary hover:text-on-surface transition-colors p-1" title="Edit">
                        <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
                    </button>
                    <form action="{{ route('admin.sla.delete', $policy->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this SLA policy?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-stage-lost hover:text-on-surface transition-colors p-1" title="Delete">
                            <span class="material-symbols-outlined" style="font-size: 20px;">delete</span>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-text-muted">
                No SLA policies defined.
            </div>
            @endforelse
        </div>
    </x-card>
</div>
@endsection
