@extends('layouts.app')

@section('content')
<div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar max-w-[1200px] w-full mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.index') }}" class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-1">User Management</h2>
            <p class="font-body-md text-body-md text-text-muted">Manage system access, roles, and branch/team assignments.</p>
        </div>
        <div class="ml-auto">
            <x-button @click="$dispatch('open-add-user-modal')">
                <span class="material-symbols-outlined" style="font-size: 18px;">person_add</span>
                Add User
            </x-button>
        </div>
    </div>

    <!-- Modals -->
    <div x-data="{ addModalOpen: false, editModalOpen: false, editUser: {} }"
         @open-add-user-modal.window="addModalOpen = true"
         @open-edit-user-modal.window="editUser = $event.detail; editModalOpen = true">
        
        <!-- Add User Modal -->
        <div x-show="addModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="addModalOpen = false" class="bg-surface rounded-2xl p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Add User</h3>
                    <button @click="addModalOpen = false" class="text-text-muted hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Name</label>
                        <input type="text" name="name" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Email</label>
                        <input type="email" name="email" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Password</label>
                        <input type="password" name="password" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Role</label>
                        <select name="role" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow appearance-none">
                            <option value="salesperson">Salesperson</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="branch_head">Branch Head</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-text-muted hover:text-on-surface font-label-md">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:bg-primary/90 transition-colors">Create User</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="editModalOpen = false" class="bg-surface rounded-2xl p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Edit User</h3>
                    <button @click="editModalOpen = false" class="text-text-muted hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form :action="`{{ url('/admin/users') }}/${editUser.id}`" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Name</label>
                        <input type="text" name="name" x-model="editUser.name" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Email</label>
                        <input type="email" name="email" x-model="editUser.email" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-text-muted mb-1">Role</label>
                        <select name="role" x-model="editUser.role" required class="w-full bg-surface-container-lowest border border-border-subtle rounded-lg px-3 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow appearance-none">
                            <option value="salesperson">Salesperson</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="branch_head">Branch Head</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
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
                        <td class="p-4 text-right flex items-center justify-end gap-2">
                            <button @click="$dispatch('open-edit-user-modal', { id: '{{ $u->id }}', name: '{{ addslashes($u->name) }}', email: '{{ addslashes($u->email) }}', role: '{{ $u->role }}' })" class="text-primary hover:text-on-surface transition-colors p-1" title="Edit">
                                <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
                            </button>
                            <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="{{ $u->is_active ? 'text-stage-lost' : 'text-stage-booked' }} hover:text-on-surface transition-colors p-1" title="{{ $u->is_active ? 'Deactivate' : 'Activate' }}">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">{{ $u->is_active ? 'block' : 'check_circle' }}</span>
                                </button>
                            </form>
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
