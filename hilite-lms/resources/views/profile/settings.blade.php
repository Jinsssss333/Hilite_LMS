@extends('layouts.app')

@section('content')
<div class="max-w-[800px] w-full mx-auto" x-data="{ editMode: false }">
    <header class="mb-8">
        <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-1">Profile Settings</h2>
        <p class="font-body-md text-body-md text-text-muted">Manage your personal account details and preferences.</p>
    </header>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-8 p-4 rounded-xl border border-[#d2dcc8] bg-[#E9EFE1] text-stage-booked font-medium flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-8 p-4 rounded-xl border border-stage-lost/20 bg-stage-lost/10 text-stage-lost font-medium flex flex-col gap-1">
            @foreach($errors->all() as $error)
                <span class="flex items-center gap-2"><span class="material-symbols-outlined">error</span> {{ $error }}</span>
            @endforeach
        </div>
    @endif

    <div class="bg-surface-container-lowest border border-border-subtle rounded-2xl p-6 md:p-8 shadow-sm">
        <div class="flex items-center justify-between gap-6 mb-8 border-b border-border-subtle pb-6">
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 rounded-full bg-secondary-container flex items-center justify-center text-[24px] font-bold text-on-secondary-container border border-border-subtle">
                    {{ substr(\App\Http\Helpers\AuthHelper::user()->name ?? 'U', 0, 1) }}
                </div>
                <div>
                    <h3 class="font-headline-md text-on-surface">{{ \App\Http\Helpers\AuthHelper::user()->name }}</h3>
                    <p class="text-text-muted">{{ \App\Http\Helpers\AuthHelper::user()->email }} • {{ ucwords(str_replace('_', ' ', \App\Http\Helpers\AuthHelper::user()->role)) }}</p>
                </div>
            </div>
            <div>
                <x-button type="button" variant="secondary" @click="editMode = !editMode" x-text="editMode ? 'Cancel Edit' : 'Edit Profile'">Edit Profile</x-button>
            </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <label class="flex flex-col gap-2">
                    <span class="text-label-md text-on-surface">Full Name</span>
                    <input name="name" :disabled="!editMode" :class="{ 'opacity-50 cursor-not-allowed bg-surface-container': !editMode }" class="form-input w-full rounded-xl border border-border-subtle bg-surface h-12 px-4 transition-all" value="{{ \App\Http\Helpers\AuthHelper::user()->name }}" type="text" required />
                </label>
                <label class="flex flex-col gap-2">
                    <span class="text-label-md text-on-surface">Email Address</span>
                    <input name="email" :disabled="!editMode" :class="{ 'opacity-50 cursor-not-allowed bg-surface-container': !editMode }" class="form-input w-full rounded-xl border border-border-subtle bg-surface h-12 px-4 transition-all" value="{{ \App\Http\Helpers\AuthHelper::user()->email }}" type="email" required />
                </label>
                <label class="flex flex-col gap-2 md:col-span-2">
                    <span class="text-label-md text-on-surface flex items-center gap-1">
                        Phone Number
                        <span class="material-symbols-outlined text-[14px] text-text-muted cursor-help" title="Enter the raw number — country code is detected automatically via libphonenumber. e.g. 9876543210 or +919876543210">info</span>
                    </span>
                    <input
                        name="phone"
                        :disabled="!editMode"
                        :class="{ 'opacity-50 cursor-not-allowed bg-surface-container': !editMode }"
                        class="form-input w-full rounded-xl border border-border-subtle bg-surface h-12 px-4 font-mono tracking-wide transition-all"
                        value="{{ \App\Http\Helpers\AuthHelper::user()?->phone ?? '' }}"
                        placeholder="e.g. 9876543210 or +919876543210"
                        type="tel"
                    />
                    @php
                        $savedPhone = \App\Http\Helpers\AuthHelper::user()?->phone;
                        $detectedRegion = $savedPhone ? app(\App\Services\PhoneNormalizationService::class)->detectRegion($savedPhone) : null;
                    @endphp
                    @if($detectedRegion && $savedPhone)
                        <span class="text-[11px] text-stage-interested flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">check_circle</span>
                            Stored as E.164 · Detected region: <strong>{{ $detectedRegion }}</strong>
                        </span>
                    @elseif(!$savedPhone)
                        <span class="text-[11px] text-text-muted">No phone number saved yet.</span>
                    @endif
                </label>

            </div>

            <div x-show="editMode" x-transition class="flex justify-end pt-6 border-t border-border-subtle">
                <x-button type="submit" variant="primary">Save Changes</x-button>
            </div>
        </form>

        <!-- Logout Section -->
        <div class="mt-10 pt-8 border-t border-border-subtle">
            <h3 class="font-headline-sm text-on-surface mb-2">Account Actions</h3>
            <p class="text-body-sm text-text-muted mb-4">Log out of your active session on this device.</p>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-surface text-error border border-error/20 hover:bg-error/5 rounded-lg font-label-md transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
                    Log Out
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
