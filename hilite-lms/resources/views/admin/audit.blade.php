@extends('layouts.app')

@section('content')
<div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar max-w-[1600px] w-full mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary tracking-tight">Audit Logs</h2>
                <p class="text-on-surface-variant font-body-md text-body-md mt-1">Comprehensive traceability of all lead engagements and system events.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.audit.export') }}" class="flex items-center gap-2 px-4 py-2 bg-surface-container-lowest border border-border-subtle rounded-xl font-label-md text-label-md hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export CSV
                </a>
                <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-surface-container-lowest border border-border-subtle rounded-xl font-label-md text-label-md hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px]">print</span>
                    Print
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section (Bento Inspired) -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-8">
        <div class="md:col-span-4 bg-surface-container-lowest p-4 rounded-2xl border border-border-subtle shadow-sm">
            <label class="block font-label-md text-label-md text-on-surface-variant mb-2">ACTION TYPE</label>
            <select class="w-full bg-surface-container-low border border-border-subtle rounded-xl text-body-md py-2 px-3 focus:ring-1 focus:ring-primary outline-none appearance-none">
                <option>All Actions</option>
                <option>Lead Rescored</option>
                <option>Stage Changed</option>
                <option>Lead Assigned</option>
                <option>Field Updated</option>
                <option>Document Uploaded</option>
            </select>
        </div>
        <div class="md:col-span-6 bg-surface-container-lowest p-4 rounded-2xl border border-border-subtle grid grid-cols-2 gap-4 shadow-sm">
            <div>
                <label class="block font-label-md text-label-md text-on-surface-variant mb-2">FROM DATE</label>
                <input class="w-full bg-surface-container-low border border-border-subtle rounded-xl text-body-md py-2 px-3 focus:ring-1 focus:ring-primary outline-none" type="date">
            </div>
            <div>
                <label class="block font-label-md text-label-md text-on-surface-variant mb-2">TO DATE</label>
                <input class="w-full bg-surface-container-low border border-border-subtle rounded-xl text-body-md py-2 px-3 focus:ring-1 focus:ring-primary outline-none" type="date">
            </div>
        </div>
        <div class="md:col-span-2 flex items-end gap-2">
            <button class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-body-md text-body-md font-bold hover:opacity-90 transition-all">Apply</button>
            <button class="p-2.5 bg-surface-container-lowest border border-border-subtle text-on-surface-variant rounded-xl hover:bg-surface-container-high transition-all">
                <span class="material-symbols-outlined">refresh</span>
            </button>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-surface-container-lowest rounded-2xl border border-border-subtle shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-border-subtle bg-surface-container-low/30 flex justify-between items-center">
            <span class="text-xs font-semibold text-on-surface-variant">Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries</span>
            
            <div class="flex gap-1 items-center">
                {{ $logs->links('pagination::tailwind') }}
            </div>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-surface-container-low/20 border-b border-border-subtle">
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">ID</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Action</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Actor</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Lead</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Before</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">After</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    @forelse($logs as $log)
                    <tr class="hover:bg-surface-container-lowest transition-colors">
                        <td class="px-6 py-4 font-body-sm text-body-sm">
                            <span class="font-mono text-on-surface-variant">#{{ $log->id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[18px]">bolt</span>
                                <span class="font-label-md text-label-md capitalize">{{ str_replace('_', ' ', $log->action) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center text-white text-[10px] font-bold">
                                    {{ $log->actor ? strtoupper(substr($log->actor->name, 0, 2)) : 'SY' }}
                                </div>
                                <span class="font-body-md text-body-md font-semibold">{{ $log->actor ? $log->actor->name : 'System' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($log->engagement_id)
                                <a href="{{ route('leads.show', $log->engagement_id) }}" class="font-body-md text-body-md text-primary font-medium hover:underline cursor-pointer">
                                    Lead #{{ $log->engagement_id }}
                                </a>
                            @else
                                <span class="font-body-md text-body-md text-text-muted">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-body-sm text-body-sm text-on-surface-variant">
                            <div class="max-w-[150px] truncate" title="{{ json_encode($log->before) }}">
                                {{ $log->before ? json_encode($log->before) : '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 font-body-sm text-body-sm text-on-surface-variant">
                            <div class="max-w-[150px] truncate" title="{{ json_encode($log->after) }}">
                                {{ $log->after ? json_encode($log->after) : '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-[11px] text-text-muted" title="{{ $log->created_at }}">{{ $log->created_at->format('M d, g:i A') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-on-surface-variant text-body-md">
                            No audit logs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Mobile/Simple Pagination for smaller screens if needed -->
        <div class="md:hidden px-6 py-4 border-t border-border-subtle bg-surface-container-low/30">
            {{ $logs->links('pagination::simple-tailwind') }}
        </div>
    </div>
</div>
@endsection
