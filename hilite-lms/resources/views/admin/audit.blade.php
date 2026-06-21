@extends('layouts.app')

@section('content')
<div class="max-w-[1000px] w-full mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.index') }}" class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-1">Audit Logs</h2>
            <p class="font-body-md text-body-md text-text-muted">View system-wide activity, data exports, and deletions.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <x-button variant="secondary">
                <span class="material-symbols-outlined" style="font-size: 18px;">filter_list</span>
                Filter
            </x-button>
            <x-button variant="secondary">
                <span class="material-symbols-outlined" style="font-size: 18px;">download</span>
                Export
            </x-button>
        </div>
    </div>

    <!-- Alpine Modal for JSON -->
    <div x-data="{ open: false, rawJson: '' }"
         @open-json-modal.window="rawJson = $event.detail.json; open = true">
         
        <div x-show="open" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="open = false" class="bg-surface rounded-2xl p-6 w-full max-w-2xl shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Raw Audit Data</h3>
                    <button @click="open = false" class="text-text-muted hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="bg-surface-container-lowest border border-border-subtle rounded-xl p-4 overflow-x-auto max-h-[60vh] custom-scrollbar">
                    <pre class="font-mono text-sm text-text-muted" x-text="rawJson"></pre>
                </div>
                <div class="flex justify-end pt-4">
                    <button @click="open = false" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:bg-primary/90 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </div>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low font-label-sm text-label-sm text-text-muted uppercase tracking-wider border-b border-border-subtle">
                        <th class="p-4 font-medium w-48">Timestamp</th>
                        <th class="p-4 font-medium w-48">User</th>
                        <th class="p-4 font-medium">Action</th>
                        <th class="p-4 font-medium w-32 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="font-body-sm text-body-sm divide-y divide-border-subtle">
                    @forelse($logs as $log)
                    <tr class="hover:bg-surface-container-lowest transition-colors">
                        <td class="p-4 text-text-muted">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                        <td class="p-4">
                            <span class="font-medium text-primary">{{ $log->actor_name ?? 'System API' }}</span>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1 bg-surface-container px-2 py-0.5 rounded text-on-surface font-mono text-xs">
                                {{ $log->action }}
                            </span>
                            @if($log->engagement_id)
                                <span class="text-text-muted ml-2">on Lead #{{ $log->engagement_id }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <button class="text-primary hover:underline" @click="$dispatch('open-json-modal', { json: JSON.stringify({{ json_encode($log->after_state) }}, null, 2) })">
                                View JSON
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-text-muted">No audit logs available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
        <div class="p-4 border-t border-border-subtle">
            {{ $logs->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
