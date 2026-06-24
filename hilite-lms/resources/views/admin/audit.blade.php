@extends('layouts.app')

@section('content')
<div class="flex-1 overflow-y-auto bg-surface">
    <!-- Page Header & Tabs -->
    <header class="px-6 md:px-8 py-8 pb-6 border-b border-border-subtle mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-surface-container-high text-on-surface-variant border border-border-subtle">System Settings</span>
                </div>
                <h2 class="font-headline-lg text-headline-lg font-bold text-primary tracking-tight">Administration</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1 max-w-2xl">Manage system users, define Service Level Agreement policies, and monitor global audit logs.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.audit.export') }}" class="flex items-center gap-2 px-4 py-2 bg-surface border border-border-subtle rounded-full hover:bg-surface-container-high transition-colors font-label-md text-label-md text-primary shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export CSV
                </a>
            </div>
        </div>
        <!-- In-Page Navigation (Tabs) -->
        <div class="mt-8 flex overflow-x-auto no-scrollbar -mb-6">
            <a href="{{ route('admin.users') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">User Management</a>
            <a href="{{ route('admin.pipeline') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">Pipeline Stages</a>
            <a href="{{ route('admin.sla') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">SLA Policies</a>
            <a href="{{ route('admin.audit') }}" class="px-4 py-3 font-label-md text-label-md text-primary border-b-2 border-primary whitespace-nowrap">Audit Log</a>
        </div>
    </header>

    <div class="px-6 md:px-8 pb-12 flex-1 space-y-6">
        <!-- Page Title & Restriction Notice -->
        <div class="flex flex-col gap-2 mb-6">
            <div class="flex items-center gap-3">
                <h3 class="font-headline-sm text-headline-sm text-primary font-bold">System Audit Logs</h3>
                <span class="px-2 py-0.5 bg-error-container text-on-error-container text-label-sm font-label-sm rounded-md flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">lock</span>
                    Restricted View
                </span>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-2xl">
                Viewing historical activity logs. Note: Sensitive personal information (PII) is automatically masked for compliance with regional data privacy standards.
            </p>
        </div>

        <!-- Filters Strip -->
        <div class="flex flex-wrap items-center gap-4 mb-6 bg-surface p-4 rounded-xl border border-border-subtle shadow-sm shadow-primary/5">
            <div class="flex items-center gap-2">
                <span class="font-label-md text-label-md text-on-surface-variant">Action Type:</span>
                <select class="bg-surface-container-low border border-border-subtle rounded-lg px-3 py-1.5 font-body-sm text-body-sm focus:outline-none focus:ring-1 focus:ring-primary appearance-none">
                    <option>All Actions</option>
                </select>
            </div>
            <div class="flex items-center gap-2 ml-auto">
                <button class="flex items-center gap-2 px-4 py-2 border border-border-subtle rounded-lg font-body-sm text-body-sm hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span>
                    More Filters
                </button>
            </div>
        </div>

        <!-- Audit Table -->
        <div class="bg-surface rounded-xl border border-border-subtle overflow-hidden shadow-sm shadow-primary/5">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-surface-container-low/50 border-b border-border-subtle">
                            <th class="px-6 py-4 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Timestamp</th>
                            <th class="px-6 py-4 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Actor</th>
                            <th class="px-6 py-4 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Action</th>
                            <th class="px-6 py-4 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Lead Reference</th>
                            <th class="px-6 py-4 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Before (JSON)</th>
                            <th class="px-6 py-4 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">After (JSON)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        @forelse($logs as $log)
                        <tr class="hover:bg-surface-container-lowest transition-colors group">
                            <td class="px-6 py-4 font-body-sm text-body-sm whitespace-nowrap text-on-surface-variant">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container">
                                        {{ $log->actor ? strtoupper(substr($log->actor->name, 0, 2)) : 'SY' }}
                                    </div>
                                    <span class="font-body-sm text-body-sm font-medium">{{ $log->actor ? $log->actor->name : 'System' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-surface-container-highest text-on-surface-variant border border-border-subtle rounded font-label-sm text-[10px] uppercase font-bold">{{ str_replace('_', ' ', $log->action) }}</span>
                            </td>
                            <td class="px-6 py-4 font-body-sm text-body-sm text-on-surface italic">
                                @if($log->entity_type == 'lead_engagement')
                                    <a href="{{ route('leads.show', $log->entity_id) }}" class="text-primary hover:underline">Lead #{{ $log->entity_id }}</a>
                                @else
                                    <span class="text-text-muted capitalize">{{ str_replace('_', ' ', $log->entity_type) }} #{{ $log->entity_id }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="bg-surface-container-highest rounded p-2 text-[11px] font-mono text-on-surface-variant max-w-[200px] truncate hover:whitespace-normal hover:max-w-none transition-all cursor-default" title="{{ json_encode($log->before_state) }}">
                                    {{ $log->before_state ? json_encode($log->before_state) : 'NULL' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="bg-surface-container-highest rounded p-2 text-[11px] font-mono text-on-surface-variant max-w-[200px] truncate hover:whitespace-normal hover:max-w-none transition-all cursor-default" title="{{ json_encode($log->after_state) }}">
                                    {{ $log->after_state ? json_encode($log->after_state) : 'NULL' }}
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-on-surface-variant text-body-md">No audit logs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination Footer -->
            <div class="px-6 py-4 bg-surface border-t border-border-subtle flex items-center justify-between">
                <span class="font-body-sm text-body-sm text-on-surface-variant">Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries</span>
                <div class="flex items-center gap-1">
                    {{ $logs->links('pagination::tailwind') }}
                </div>
            </div>
        </div>

        <!-- Footer Summary Info -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-secondary-container/30 p-4 rounded-xl border border-secondary-container flex items-start gap-4">
                <div class="p-2 bg-secondary-container rounded-lg shrink-0">
                    <span class="material-symbols-outlined text-on-secondary-container">policy</span>
                </div>
                <div>
                    <h4 class="font-label-md text-label-md text-on-surface">Data Governance</h4>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Audit logs are retained for 365 days. Records older than 1 year are archived to cold storage.</p>
                </div>
            </div>
            <div class="bg-surface p-4 rounded-xl border border-border-subtle flex items-start gap-4 shadow-sm shadow-primary/5">
                <div class="p-2 bg-surface-container rounded-lg shrink-0">
                    <span class="material-symbols-outlined text-on-surface-variant">visibility_off</span>
                </div>
                <div>
                    <h4 class="font-label-md text-label-md text-on-surface">Masking Rules</h4>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Names, emails, and phone numbers are redacted in accordance with GDPR Level 2 access.</p>
                </div>
            </div>
            <div class="bg-surface p-4 rounded-xl border border-border-subtle flex items-start gap-4 shadow-sm shadow-primary/5">
                <div class="p-2 bg-surface-container rounded-lg shrink-0">
                    <span class="material-symbols-outlined text-on-surface-variant">history_edu</span>
                </div>
                <div>
                    <h4 class="font-label-md text-label-md text-on-surface">Compliance Export</h4>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Need full unmasked logs? Contact your System Administrator for an elevated compliance report.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
