@extends('layouts.app')

@section('content')
<div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar max-w-[1000px] w-full mx-auto">
    <header class="mb-8">
        <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-1">System Administration</h2>
        <p class="font-body-md text-body-md text-text-muted">Manage users, teams, pipeline stages, and global settings.</p>
    </header>

    <div class="space-y-6">
        <!-- Users & Teams -->
        <a href="{{ route('admin.users') }}" class="block">
            <x-card class="flex flex-col gap-4 hover:bg-surface-container-low transition-colors cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined">manage_accounts</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-headline-sm text-on-surface">User Management</h3>
                        <p class="text-body-sm text-text-muted">Invite users, manage roles, and assign to teams/branches.</p>
                    </div>
                    <span class="material-symbols-outlined text-text-muted">chevron_right</span>
                </div>
            </x-card>
        </a>

        <!-- Pipeline Customization -->
        <a href="{{ route('admin.pipeline') }}" class="block">
            <x-card class="flex flex-col gap-4 hover:bg-surface-container-low transition-colors cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined">view_kanban</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-headline-sm text-on-surface">Pipeline & Stages</h3>
                        <p class="text-body-sm text-text-muted">Configure Kanban columns, colors, and stage ordering.</p>
                    </div>
                    <span class="material-symbols-outlined text-text-muted">chevron_right</span>
                </div>
            </x-card>
        </a>

        <!-- SLA Policies -->
        <a href="{{ route('admin.sla') }}" class="block">
            <x-card class="flex flex-col gap-4 hover:bg-surface-container-low transition-colors cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined">gavel</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-headline-sm text-on-surface">SLA Policies & Automation</h3>
                        <p class="text-body-sm text-text-muted">Set automatic dormancy rules and response time targets.</p>
                    </div>
                    <span class="material-symbols-outlined text-text-muted">chevron_right</span>
                </div>
            </x-card>
        </a>

        <!-- Audit Logs -->
        <a href="{{ route('admin.audit') }}" class="block">
            <x-card class="flex flex-col gap-4 hover:bg-surface-container-low transition-colors cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined">history</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-headline-sm text-on-surface">Audit Logs</h3>
                        <p class="text-body-sm text-text-muted">View system-wide activity, data exports, and deletions.</p>
                    </div>
                    <span class="material-symbols-outlined text-text-muted">chevron_right</span>
                </div>
            </x-card>
        </a>
    </div>
</div>
@endsection
