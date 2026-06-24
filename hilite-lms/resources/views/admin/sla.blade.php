@extends('layouts.app')

@section('content')
<div class="flex-1 overflow-y-auto bg-surface">
    <!-- Page Header -->
    <header class="px-6 md:px-8 py-8 pb-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-surface-container-high text-on-surface-variant border border-border-subtle">System Settings</span>
                </div>
                <h2 class="font-headline-lg text-headline-lg font-bold text-primary tracking-tight">Administration</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1 max-w-2xl">Manage system users, define Service Level Agreement policies, and monitor global audit logs.</p>
            </div>
            <div class="flex gap-2">
                <button @click="$dispatch('open-add-sla-modal')" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-full hover:bg-surface-tint transition-colors font-label-md text-label-md shadow-sm active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    New Policy
                </button>
            </div>
        </div>
        <!-- In-Page Navigation (Tabs) -->
        <div class="mt-8 border-b border-border-subtle flex overflow-x-auto no-scrollbar">
            <a href="{{ route('admin.users') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">User Management</a>
            <a href="{{ route('admin.pipeline') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">Pipeline Stages</a>
            <a href="{{ route('admin.sla') }}" class="px-4 py-3 font-label-md text-label-md text-primary border-b-2 border-primary whitespace-nowrap">SLA Policies</a>
            <a href="{{ route('admin.audit') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">Audit Log</a>
        </div>
    </header>

    <div class="px-6 md:px-8 pb-12 flex-1 space-y-6">
        @if(session('success'))
            <div class="mb-4 p-4 bg-[#E8F5E9] text-[#2E7D32] border border-[#C8E6C9] rounded-lg font-body-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-error-container text-on-error-container border border-error/20 rounded-lg font-body-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-4 bg-error-container text-on-error-container border border-error/20 rounded-lg font-body-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-surface rounded-[24px] border border-border-subtle overflow-hidden shadow-sm shadow-primary/5">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-border-subtle bg-surface-container-low/50">
                            <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Stage / Target</th>
                            <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold text-center w-32">Max Days</th>
                            <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold text-center w-24">Status</th>
                            <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold text-right w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle font-body-sm">
                        @forelse($policies as $policy)
                        <tr class="hover:bg-surface-container-lowest transition-colors group">
                            <td class="py-4 px-6">
                                <div class="font-label-md text-primary">{{ $policy->stage->name ?? 'Unknown Stage' }} SLA</div>
                                <div class="text-on-surface-variant text-[12px] mt-0.5">Escalates to: {{ ucfirst(str_replace('_', ' ', $policy->escalate_to_role ?? 'None')) }}</div>
                            </td>
                            <td class="py-4 px-6 text-center font-medium text-on-surface-variant">
                                {{ $policy->sla_days ?? 'N/A' }} Days
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E8F5E9] text-[#2E7D32] border border-[#C8E6C9]">
                                    Active
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-all">
                                    <button @click="$dispatch('open-edit-sla-modal', { id: '{{ $policy->id }}', stage_id: '{{ $policy->stage_id }}', sla_days: {{ $policy->sla_days }}, escalate_to_role: '{{ $policy->escalate_to_role }}' })" class="text-on-surface-variant hover:text-primary p-1 rounded hover:bg-surface-container-high transition-all" title="Edit">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <form action="{{ route('admin.sla.delete', $policy->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this SLA policy?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-on-surface-variant hover:text-error p-1 rounded hover:bg-surface-container-high transition-all" title="Delete">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 px-6 text-center text-on-surface-variant font-body-md">No SLA policies defined.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div x-data="{ addModalOpen: false, editModalOpen: false, editPolicy: {} }"
         @open-add-sla-modal.window="addModalOpen = true"
         @open-edit-sla-modal.window="editPolicy = $event.detail; editModalOpen = true">
        
        <!-- Add SLA Modal -->
        <div x-show="addModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="addModalOpen = false" class="bg-surface rounded-[24px] p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Add SLA Policy</h3>
                    <button @click="addModalOpen = false" class="text-on-surface-variant hover:text-primary p-1 rounded-full hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form action="{{ route('admin.sla.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Stage</label>
                        <select name="stage_id" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow appearance-none font-body-sm">
                            @foreach($stages as $stage)
                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Max Days (SLA)</label>
                        <input type="number" name="sla_days" required min="1" class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Escalate To Role (Optional)</label>
                        <select name="escalate_to_role" class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow appearance-none font-body-sm">
                            <option value="">None</option>
                            <option value="manager">Manager</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="branch_head">Branch Head</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-4 border-t border-border-subtle mt-6">
                        <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-on-surface-variant hover:text-primary font-label-md rounded-xl hover:bg-surface-container-high transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-opacity">Create Policy</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit SLA Modal -->
        <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="editModalOpen = false" class="bg-surface rounded-[24px] p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Edit SLA Policy</h3>
                    <button @click="editModalOpen = false" class="text-on-surface-variant hover:text-primary p-1 rounded-full hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form :action="`{{ url('/admin/sla') }}/${editPolicy.id}`" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Stage</label>
                        <select name="stage_id" x-model="editPolicy.stage_id" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow appearance-none font-body-sm">
                            @foreach($stages as $stage)
                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Max Days (SLA)</label>
                        <input type="number" name="sla_days" x-model="editPolicy.sla_days" required min="1" class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Escalate To Role (Optional)</label>
                        <select name="escalate_to_role" x-model="editPolicy.escalate_to_role" class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow appearance-none font-body-sm">
                            <option value="">None</option>
                            <option value="manager">Manager</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="branch_head">Branch Head</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-4 border-t border-border-subtle mt-6">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-on-surface-variant hover:text-primary font-label-md rounded-xl hover:bg-surface-container-high transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-opacity">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
