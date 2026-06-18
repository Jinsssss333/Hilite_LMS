@extends('layouts.app')

@section('content')
<div class="max-w-[800px] w-full mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.index') }}" class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-1">Pipeline Stages</h2>
            <p class="font-body-md text-body-md text-text-muted">Configure Kanban columns, colors, and stage ordering.</p>
        </div>
        <div class="ml-auto">
            <x-button>
                <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                Add Stage
            </x-button>
        </div>
    </div>

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
                    <button class="text-primary hover:text-on-surface transition-colors p-1" title="Edit">
                        <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
                    </button>
                    <button class="text-stage-lost hover:text-on-surface transition-colors p-1" title="Delete">
                        <span class="material-symbols-outlined" style="font-size: 20px;">delete</span>
                    </button>
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
