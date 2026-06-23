@extends('layouts.app')

@section('content')
<div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar max-w-[1000px] w-full mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.index') }}" class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-1">Pipeline Stages</h2>
            <p class="font-body-md text-body-md text-text-muted">Configure Kanban columns, colors, and stage ordering.</p>
        </div>
        <div class="ml-auto">
        <div class="ml-auto">
            <x-button @click="$dispatch('open-add-stage-modal')">
                <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                Add Stage
            </x-button>
        </div>
    </div>

    <!-- Modals -->
    <div x-data="{ addModalOpen: false, editModalOpen: false, editStage: {} }"
         @open-add-stage-modal.window="addModalOpen = true"
         @open-edit-stage-modal.window="editStage = $event.detail; editModalOpen = true">
        
        <!-- Add Stage Modal -->
        <div x-show="addModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="addModalOpen = false" class="bg-surface rounded-2xl p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Add Stage</h3>
                    <button @click="addModalOpen = false" class="text-text-muted hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form action="{{ route('admin.pipeline.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Name</label>
                        <input type="text" name="name" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block font-label-sm text-label-sm text-text-muted mb-1">Color (Hex)</label>
                            <input type="color" name="color" value="#6366f1" class="w-full h-10 bg-surface-container-lowest border border-border-subtle rounded-lg px-1 py-1 cursor-pointer">
                        </div>
                        <div class="flex-1">
                            <label class="block font-label-sm text-label-sm text-text-muted mb-1">Order</label>
                            <input type="number" name="order" value="{{ $stages->count() + 1 }}" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-2">
                            <input type="checkbox" name="is_closed" value="1" class="w-4 h-4 text-primary rounded border-border-subtle focus:ring-primary">
                            <span class="font-label-sm text-on-surface">Is Closed Stage (End of Pipeline)</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-text-muted hover:text-on-surface font-label-md">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:bg-primary/90 transition-colors">Create Stage</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Stage Modal -->
        <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="editModalOpen = false" class="bg-surface rounded-2xl p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Edit Stage</h3>
                    <button @click="editModalOpen = false" class="text-text-muted hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form :action="`{{ url('/admin/pipeline') }}/${editStage.id}`" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Name</label>
                        <input type="text" name="name" x-model="editStage.name" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block font-label-sm text-label-sm text-text-muted mb-1">Color (Hex)</label>
                            <input type="color" name="color" x-model="editStage.color" class="w-full h-10 bg-surface-container-lowest border border-border-subtle rounded-lg px-1 py-1 cursor-pointer">
                        </div>
                        <div class="flex-1">
                            <label class="block font-label-sm text-label-sm text-text-muted mb-1">Order</label>
                            <input type="number" name="order" x-model="editStage.order" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-2">
                            <input type="checkbox" name="is_closed" value="1" x-model="editStage.is_closed" class="w-4 h-4 text-primary rounded border-border-subtle focus:ring-primary">
                            <span class="font-label-sm text-on-surface">Is Closed Stage (End of Pipeline)</span>
                        </label>
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
            <div class="w-16 text-center">Order</div>
            <div class="flex-1">Stage Details</div>
            <div class="w-24 text-center">Status</div>
            <div class="w-24 text-right">Actions</div>
        </div>
        
        <div class="divide-y divide-border-subtle">
            @forelse($stages as $stage)
            <div class="flex items-center p-4 hover:bg-surface-container-lowest transition-colors">
                <div class="w-16 flex items-center justify-center gap-1 text-text-muted">
                    <span class="material-symbols-outlined cursor-grab" style="font-size: 18px;">drag_indicator</span>
                    {{ $stage->order }}
                </div>
                <div class="flex-1 flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $stage->color }}"></div>
                    <div>
                        <span class="font-medium text-primary">{{ $stage->name }}</span>
                    </div>
                </div>
                <div class="w-24 text-center">
                    @if($stage->is_closed)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-text-muted font-label-sm">Closed</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stage-booked/10 text-stage-booked font-label-sm">Open</span>
                    @endif
                </div>
                <div class="w-24 text-right flex justify-end gap-2">
                    <button @click="$dispatch('open-edit-stage-modal', { id: '{{ $stage->id }}', name: '{{ addslashes($stage->name) }}', color: '{{ $stage->color }}', order: {{ $stage->order }}, is_closed: {{ $stage->is_closed ? 'true' : 'false' }} })" class="text-primary hover:text-on-surface transition-colors p-1" title="Edit">
                        <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
                    </button>
                    <form action="{{ route('admin.pipeline.delete', $stage->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this stage?');">
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
                No pipeline stages defined.
            </div>
            @endforelse
        </div>
    </x-card>
</div>
@endsection
