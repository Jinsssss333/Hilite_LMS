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
            <x-button>
                <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                Add Policy
            </x-button>
        </div>
    </div>

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
                    <span class="font-medium text-primary block">Stage SLA</span>
                    <span class="text-text-muted font-body-sm">{{ $policy->description ?? 'Auto policy' }}</span>
                </div>
                <div class="w-32 text-center font-medium text-on-surface-variant">
                    {{ $policy->max_days ?? 'N/A' }} Days
                </div>
                <div class="w-24 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stage-booked/10 text-stage-booked font-label-sm">Active</span>
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
                No SLA policies defined.
            </div>
            @endforelse
        </div>
    </x-card>
</div>
@endsection
