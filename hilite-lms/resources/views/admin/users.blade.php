@extends('layouts.app')

@section('content')
<div class="max-w-[1000px] w-full mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.index') }}" class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-1">User Management</h2>
            <p class="font-body-md text-body-md text-text-muted">Manage system access, roles, and branch/team assignments.</p>
        </div>
        <div class="ml-auto">
            <x-button>
                <span class="material-symbols-outlined" style="font-size: 18px;">person_add</span>
                Add User
            </x-button>
        </div>
    </div>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low font-label-sm text-label-sm text-text-muted uppercase tracking-wider border-b border-border-subtle">
                        <th class="p-4 font-medium">Name & Role</th>
                        <th class="p-4 font-medium">Email</th>
                        <th class="p-4 font-medium">Branch & Team</th>
                        <th class="p-4 font-medium">Status</th>
                        <th class="p-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="font-body-sm text-body-sm divide-y divide-border-subtle">
                    @forelse($users as $u)
                    <tr class="hover:bg-surface-container-lowest transition-colors">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-label-md text-label-md">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <div>
                                    <span class="font-medium text-primary block">{{ $u->name }}</span>
                                    <span class="text-text-muted font-label-sm capitalize">{{ str_replace('_', ' ', $u->role) }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-on-surface-variant">{{ $u->email }}</td>
                        <td class="p-4 text-on-surface-variant">
                            <div>{{ $u->branch_name ?? 'Global' }}</div>
                            <div class="text-text-muted text-xs">{{ $u->team_name ?? '-' }}</div>
                        </td>
                        <td class="p-4">
                            @if($u->is_active)
                            <span class="inline-flex items-center gap-1 text-stage-booked bg-stage-booked/10 px-2 py-0.5 rounded-full font-label-sm">Active</span>
                            @else
                            <span class="inline-flex items-center gap-1 text-stage-lost bg-stage-lost/10 px-2 py-0.5 rounded-full font-label-sm">Inactive</span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <button class="text-primary hover:text-on-surface transition-colors p-1" title="Edit">
                                <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
                            </button>
                            <button class="text-stage-lost hover:text-on-surface transition-colors p-1" title="Deactivate">
                                <span class="material-symbols-outlined" style="font-size: 20px;">block</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-text-muted">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
