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
                <button @click="$dispatch('open-add-stage-modal')" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-full hover:bg-surface-tint transition-colors font-label-md text-label-md shadow-sm active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    New Stage
                </button>
            </div>
        </div>
        <!-- In-Page Navigation (Tabs) -->
        <div class="mt-8 border-b border-border-subtle flex overflow-x-auto no-scrollbar">
            <a href="{{ route('admin.users') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">User Management</a>
            <a href="{{ route('admin.pipeline') }}" class="px-4 py-3 font-label-md text-label-md text-primary border-b-2 border-primary whitespace-nowrap">Pipeline Stages</a>
            <a href="{{ route('admin.sla') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">SLA Policies</a>
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
                            <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold w-16 text-center">Order</th>
                            <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Stage Details</th>
                            <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold text-center w-32">Status</th>
                            <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold text-right w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle font-body-sm">
                        @forelse($stages as $stage)
                        <tr class="hover:bg-surface-container-lowest transition-colors group">
                            <td class="py-4 px-6 text-center text-on-surface-variant">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined cursor-grab text-[18px] opacity-50 group-hover:opacity-100">drag_indicator</span>
                                    <span class="font-medium">{{ $stage->order }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full shadow-sm" style="background-color: {{ $stage->color }}"></div>
                                    <span class="font-label-md text-primary">{{ $stage->name }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($stage->is_closed)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-surface-container-highest text-on-surface-variant">Closed</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#E8F5E9] text-[#2E7D32]">Open</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-all">
                                    <button @click="$dispatch('open-edit-stage-modal', { id: '{{ $stage->id }}', name: '{{ addslashes($stage->name) }}', color: '{{ $stage->color }}', order: {{ $stage->order }}, is_closed: {{ $stage->is_closed ? 'true' : 'false' }} })" class="text-on-surface-variant hover:text-primary p-1 rounded hover:bg-surface-container-high transition-all" title="Edit">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <form action="{{ route('admin.pipeline.delete', $stage->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this stage?');">
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
                            <td colspan="4" class="py-8 px-6 text-center text-on-surface-variant font-body-md">No pipeline stages defined.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div x-data="{ addModalOpen: false, editModalOpen: false, editStage: {} }"
         @open-add-stage-modal.window="addModalOpen = true"
         @open-edit-stage-modal.window="editStage = $event.detail; editModalOpen = true">
        
        <!-- Add Stage Modal -->
        <div x-show="addModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="addModalOpen = false" class="bg-surface rounded-[24px] p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Add Stage</h3>
                    <button @click="addModalOpen = false" class="text-on-surface-variant hover:text-primary p-1 rounded-full hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form action="{{ route('admin.pipeline.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Name</label>
                        <input type="text" name="name" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Color</label>
                            <input type="color" name="color" value="#6366f1" class="w-full h-10 bg-surface-container-low border border-border-subtle rounded-xl px-1 py-1 cursor-pointer">
                        </div>
                        <div class="flex-1">
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Order</label>
                            <input type="number" name="order" value="{{ $stages->count() + 1 }}" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-2 p-2 bg-surface-container-low rounded-xl border border-border-subtle hover:bg-surface-container-high transition-colors">
                            <input type="checkbox" name="is_closed" value="1" class="w-4 h-4 text-primary rounded border-border-subtle focus:ring-primary bg-surface">
                            <span class="font-label-md text-on-surface">Is Closed Stage (End of Pipeline)</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-4 border-t border-border-subtle mt-6">
                        <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-on-surface-variant hover:text-primary font-label-md rounded-xl hover:bg-surface-container-high transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-opacity">Create Stage</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Stage Modal -->
        <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="editModalOpen = false" class="bg-surface rounded-[24px] p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Edit Stage</h3>
                    <button @click="editModalOpen = false" class="text-on-surface-variant hover:text-primary p-1 rounded-full hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form :action="`{{ url('/admin/pipeline') }}/${editStage.id}`" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Name</label>
                        <input type="text" name="name" x-model="editStage.name" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Color</label>
                            <input type="color" name="color" x-model="editStage.color" class="w-full h-10 bg-surface-container-low border border-border-subtle rounded-xl px-1 py-1 cursor-pointer">
                        </div>
                        <div class="flex-1">
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Order</label>
                            <input type="number" name="order" x-model="editStage.order" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-2 p-2 bg-surface-container-low rounded-xl border border-border-subtle hover:bg-surface-container-high transition-colors">
                            <input type="checkbox" name="is_closed" value="1" x-model="editStage.is_closed" class="w-4 h-4 text-primary rounded border-border-subtle focus:ring-primary bg-surface">
                            <span class="font-label-md text-on-surface">Is Closed Stage (End of Pipeline)</span>
                        </label>
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
