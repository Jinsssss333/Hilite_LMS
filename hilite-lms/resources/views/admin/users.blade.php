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
                <button class="flex items-center gap-2 px-4 py-2 bg-surface border border-border-subtle rounded-full hover:bg-surface-container-high transition-colors font-label-md text-label-md text-primary shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export Data
                </button>
                <button @click="$dispatch('open-add-user-modal')" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-full hover:bg-surface-tint transition-colors font-label-md text-label-md shadow-sm active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    New User
                </button>
            </div>
        </div>
        <!-- In-Page Navigation (Tabs) -->
        <div class="mt-8 border-b border-border-subtle flex overflow-x-auto no-scrollbar">
            <a href="{{ route('admin.users') }}" class="px-4 py-3 font-label-md text-label-md text-primary border-b-2 border-primary whitespace-nowrap">User Management</a>
            <a href="{{ route('admin.pipeline') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">Pipeline Stages</a>
            <a href="{{ route('admin.sla') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">SLA Policies</a>
            <a href="{{ route('admin.audit') }}" class="px-4 py-3 font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap">Audit Log</a>
        </div>
    </header>

    <!-- Content Canvas -->
    <div class="px-6 md:px-8 pb-12 flex-1 space-y-6">
        @if(session('success'))
            <div class="mb-4 p-4 bg-[#E8F5E9] text-[#2E7D32] border border-[#C8E6C9] rounded-lg font-body-sm">{{ session('success') }}</div>
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

        <section class="space-y-6">
            <!-- Filters / Actions Bar -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-surface rounded-[20px] p-4 border border-border-subtle shadow-sm shadow-primary/5">
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                        <input class="w-full pl-9 pr-3 py-1.5 bg-surface-container-low border border-border-subtle rounded-lg font-body-sm text-body-sm focus:outline-none focus:border-outline focus:ring-1 focus:ring-outline transition-colors" placeholder="Search users by name or email" type="text">
                    </div>
                </div>
                <div class="font-label-sm text-label-sm text-on-surface-variant flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-stage-booked inline-block"></span>
                    Showing {{ $users->count() }} users
                </div>
            </div>

            <!-- Bento-style Data Grid -->
            <div class="bg-surface rounded-[24px] border border-border-subtle overflow-hidden shadow-sm shadow-primary/5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-border-subtle bg-surface-container-low/50">
                                <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">User</th>
                                <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Role & Branch</th>
                                <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Team</th>
                                <th class="py-4 px-6 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold text-center">Status</th>
                                <th class="py-4 px-4 font-label-md text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold text-center">Lead Load</th>
                                <th class="py-4 px-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle font-body-sm">
                            @forelse($users as $u)
                            <tr class="hover:bg-surface-container-lowest transition-colors group">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-surface-container-high border border-border-subtle flex items-center justify-center text-primary font-label-md font-bold shrink-0">
                                            {{ strtoupper(substr($u->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-label-md text-primary">{{ $u->name }}</div>
                                            <div class="text-on-surface-variant text-[12px]">{{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-tertiary-fixed text-on-tertiary-fixed w-fit capitalize">{{ str_replace('_', ' ', $u->role) }}</span>
                                        <span class="text-on-surface-variant text-[12px]">{{ $u->branch->name ?? 'Global' }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-primary font-medium">{{ $u->team->name ?? '-' }}</div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @if($u->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E8F5E9] text-[#2E7D32] border border-[#C8E6C9]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#2E7D32]"></span>
                                        Active
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-error-container text-error border border-error/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                        Inactive
                                    </span>
                                    @endif
                                </td>
                                @if(in_array($u->role, ['salesperson', 'team_lead']))
                                @php
                                    $activeCnt = $u->activeLeadCount();
                                    $cap       = $u->max_lead_cap ?? \App\Models\User::SYSTEM_DEFAULT_CAP;
                                    $floor     = $u->min_lead_floor;
                                    $pct       = $cap > 0 ? round(($activeCnt / $cap) * 100) : 0;
                                    $capColor  = $pct >= 100 ? 'text-red-700 bg-red-50 border-red-200'
                                               : ($pct >= 80  ? 'text-amber-700 bg-amber-50 border-amber-200'
                                               : 'text-green-700 bg-green-50 border-green-200');
                                    $underFloor = $floor !== null && $activeCnt < $floor;
                                @endphp
                                <td class="py-4 px-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $capColor }}">
                                            {{ $activeCnt }} / {{ $cap }}
                                        </span>
                                        @if($underFloor)
                                        <span class="text-[9px] text-blue-600 font-semibold flex items-center gap-0.5">
                                            <span class="material-symbols-outlined text-[10px]">arrow_downward</span>
                                            Under floor ({{ $floor }})
                                        </span>
                                        @endif
                                    </div>
                                </td>
                                @else
                                <td class="py-4 px-4 text-center text-on-surface-variant text-xs">—</td>
                                @endif
                                <td class="py-4 px-4 text-right">
                                    <div class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-all">
                                        <button @click="$dispatch('open-edit-user-modal', { id: '{{ $u->id }}', name: '{{ addslashes($u->name) }}', email: '{{ addslashes($u->email) }}', role: '{{ $u->role }}', max_lead_cap: '{{ $u->max_lead_cap ?? '' }}', min_lead_floor: '{{ $u->min_lead_floor ?? '' }}' })" class="text-on-surface-variant hover:text-primary p-1 rounded hover:bg-surface-container-high transition-all" title="Edit">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </button>
                                        <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-on-surface-variant hover:text-primary p-1 rounded hover:bg-surface-container-high transition-all" title="{{ $u->is_active ? 'Deactivate' : 'Activate' }}">
                                                <span class="material-symbols-outlined text-[20px]">{{ $u->is_active ? 'block' : 'check_circle' }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-8 px-6 text-center text-on-surface-variant font-body-md">No users found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- Modals -->
    <div x-data="{ addModalOpen: false, editModalOpen: false, editUser: {} }"
         @open-add-user-modal.window="addModalOpen = true"
         @open-edit-user-modal.window="editUser = $event.detail; editModalOpen = true">
        
        <!-- Add User Modal -->
        <div x-show="addModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="addModalOpen = false" class="bg-surface rounded-[24px] p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Add User</h3>
                    <button @click="addModalOpen = false" class="text-on-surface-variant hover:text-primary p-1 rounded-full hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Name</label>
                        <input type="text" name="name" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Email</label>
                        <input type="email" name="email" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Password</label>
                        <input type="password" name="password" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Role</label>
                        <select name="role" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow appearance-none font-body-sm">
                            <option value="salesperson">Salesperson</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="branch_head">Branch Head</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-4 border-t border-border-subtle mt-6">
                        <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-on-surface-variant hover:text-primary font-label-md rounded-xl hover:bg-surface-container-high transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-opacity">Create User</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="editModalOpen = false" class="bg-surface rounded-[24px] p-6 w-full max-w-md shadow-lg border border-border-subtle" x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Edit User</h3>
                    <button @click="editModalOpen = false" class="text-on-surface-variant hover:text-primary p-1 rounded-full hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form :action="`{{ url('/admin/users') }}/${editUser.id}`" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Name</label>
                        <input type="text" name="name" x-model="editUser.name" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Email</label>
                        <input type="email" name="email" x-model="editUser.email" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow font-body-sm">
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Role</label>
                        <select name="role" x-model="editUser.role" required class="w-full bg-surface-container-low border border-border-subtle rounded-xl px-3 py-2 text-on-surface focus:border-outline focus:ring-1 focus:ring-outline outline-none transition-shadow appearance-none font-body-sm">
                            <option value="salesperson">Salesperson</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="branch_head">Branch Head</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    {{-- Lead Cap fields — only meaningful for salespersons --}}
                    <div class="grid grid-cols-2 gap-3 p-3 bg-amber-50 rounded-xl border border-amber-100">
                        <div>
                            <label class="block font-label-md text-label-md text-amber-800 mb-1">
                                Max Lead Cap
                                <span class="font-normal text-amber-600 text-[10px] ml-1">(hard ceiling)</span>
                            </label>
                            <input type="number" name="max_lead_cap" x-model="editUser.max_lead_cap"
                                min="1" max="9999" placeholder="Default: 30"
                                class="w-full bg-white border border-amber-200 rounded-xl px-3 py-2 text-on-surface focus:border-amber-400 focus:ring-1 focus:ring-amber-200 outline-none transition-shadow font-body-sm">
                            <p class="text-[10px] text-amber-600 mt-1">Auto-assign skips users at this limit</p>
                        </div>
                        <div>
                            <label class="block font-label-md text-label-md text-amber-800 mb-1">
                                Min Floor
                                <span class="font-normal text-amber-600 text-[10px] ml-1">(soft warning)</span>
                            </label>
                            <input type="number" name="min_lead_floor" x-model="editUser.min_lead_floor"
                                min="0" max="9999" placeholder="e.g. 5"
                                class="w-full bg-white border border-amber-200 rounded-xl px-3 py-2 text-on-surface focus:border-amber-400 focus:ring-1 focus:ring-amber-200 outline-none transition-shadow font-body-sm">
                            <p class="text-[10px] text-amber-600 mt-1">Highlights under-loaded users</p>
                        </div>
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
