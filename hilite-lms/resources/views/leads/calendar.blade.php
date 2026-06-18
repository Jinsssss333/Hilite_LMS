@extends('layouts.app')

@section('content')
<!-- Header & Summary Strip -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div>
            <h2 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary">Site Visits</h2>
            <p class="font-body-md text-body-md text-text-muted mt-1">Manage property viewings and client meetings</p>
        </div>
        <div class="flex items-center gap-3 bg-surface-container-lowest p-1 rounded-full border border-border-subtle">
            <button class="px-4 py-1.5 rounded-full bg-primary text-on-primary font-label-md text-label-md">Calendar</button>
            <button class="px-4 py-1.5 rounded-full text-on-surface-variant hover:bg-surface-container-low font-label-md text-label-md transition-colors">List</button>
        </div>
    </div>

    <!-- KPI Summary Strip -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-[#E9EFE1] rounded-xl p-card-padding flex items-center justify-between border border-border-subtle">
            <div>
                <p class="font-label-sm text-label-sm text-on-secondary-container uppercase tracking-wider mb-1">Visits Today</p>
                <p class="font-headline-md text-headline-md text-on-secondary-fixed">4</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-secondary-fixed flex items-center justify-center">
                <span class="material-symbols-outlined text-[24px] text-on-secondary-container">directions_car</span>
            </div>
        </div>
        <div class="bg-[#E9EFE1] rounded-xl p-card-padding flex items-center justify-between border border-border-subtle">
            <div>
                <p class="font-label-sm text-label-sm text-on-secondary-container uppercase tracking-wider mb-1">Weekly Completion</p>
                <p class="font-headline-md text-headline-md text-on-secondary-fixed">78%</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-secondary-fixed flex flex-col justify-center items-center gap-[2px] p-2">
                <div class="flex items-end gap-1 h-full w-full">
                    <div class="w-1.5 bg-stage-site-visit/40 h-[40%] rounded-full"></div>
                    <div class="w-1.5 bg-stage-site-visit/60 h-[60%] rounded-full"></div>
                    <div class="w-1.5 bg-stage-site-visit/80 h-[80%] rounded-full"></div>
                    <div class="w-1.5 bg-stage-site-visit h-full rounded-full"></div>
                </div>
            </div>
        </div>
        <div class="bg-[#E9EFE1] rounded-xl p-card-padding flex items-center justify-between border border-border-subtle">
            <div>
                <p class="font-label-sm text-label-sm text-on-secondary-container uppercase tracking-wider mb-1">Pending Scheduling</p>
                <p class="font-headline-md text-headline-md text-on-secondary-fixed">12</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-secondary-fixed flex items-center justify-center">
                <span class="material-symbols-outlined text-[24px] text-on-secondary-container">pending_actions</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Layout: Calendar + Sidebar -->
<div class="flex flex-col lg:flex-row gap-6">
    <!-- Calendar View -->
    <div class="flex-1 bg-surface-container-lowest rounded-2xl border border-border-subtle shadow-sm overflow-hidden flex flex-col">
        <!-- Calendar Header -->
        <div class="p-6 border-b border-border-subtle flex justify-between items-center bg-surface-container-lowest">
            <div class="flex items-center gap-4">
                <h3 class="font-headline-sm text-headline-sm text-primary">October 2023</h3>
                <div class="flex gap-1">
                    <button class="p-1 rounded-full hover:bg-surface-container-low text-text-muted transition-colors">
                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                    </button>
                    <button class="p-1 rounded-full hover:bg-surface-container-low text-text-muted transition-colors">
                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                    </button>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="text-label-md font-label-md px-3 py-1.5 rounded-full border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors">Today</button>
                <div class="border border-border-subtle rounded-full flex items-center p-0.5">
                    <button class="px-3 py-1 rounded-full bg-surface-container-high text-on-surface font-label-sm text-label-sm">Month</button>
                    <button class="px-3 py-1 rounded-full text-text-muted hover:text-on-surface font-label-sm text-label-sm transition-colors">Week</button>
                </div>
            </div>
        </div>
        <!-- Calendar Grid -->
        <div class="flex-1 p-6">
            <div class="grid grid-cols-7 gap-4 mb-4">
                <div class="text-center font-label-sm text-label-sm text-text-muted uppercase">Sun</div>
                <div class="text-center font-label-sm text-label-sm text-text-muted uppercase">Mon</div>
                <div class="text-center font-label-sm text-label-sm text-text-muted uppercase">Tue</div>
                <div class="text-center font-label-sm text-label-sm text-text-muted uppercase">Wed</div>
                <div class="text-center font-label-sm text-label-sm text-text-muted uppercase">Thu</div>
                <div class="text-center font-label-sm text-label-sm text-text-muted uppercase">Fri</div>
                <div class="text-center font-label-sm text-label-sm text-text-muted uppercase">Sat</div>
            </div>
            <div class="grid grid-cols-7 gap-4 auto-rows-fr h-[500px]">
                <div class="border border-border-subtle rounded-xl p-2 min-h-[100px] flex flex-col bg-surface/30">
                    <span class="font-label-md text-label-md text-text-muted mb-2 text-right">1</span>
                </div>
                <div class="border border-border-subtle rounded-xl p-2 min-h-[100px] flex flex-col bg-surface/30">
                    <span class="font-label-md text-label-md text-text-muted mb-2 text-right">2</span>
                </div>
                <!-- Active Day -->
                <div class="border-2 border-border-subtle rounded-xl p-2 min-h-[100px] flex flex-col bg-surface-container-lowest shadow-sm">
                    <span class="font-label-md text-label-md text-primary mb-2 text-right font-bold">3</span>
                    <div class="flex flex-col gap-1.5">
                        <div class="bg-stage-site-visit/10 border border-stage-site-visit/30 rounded-lg p-1.5 px-2 cursor-pointer hover:bg-stage-site-visit/20 transition-colors">
                            <p class="font-label-sm text-label-sm text-stage-site-visit truncate">10:00 AM</p>
                            <p class="font-body-sm text-body-sm text-on-surface truncate font-medium">Arjun Nair</p>
                        </div>
                    </div>
                </div>
                <div class="border border-border-subtle rounded-xl p-2 min-h-[100px] flex flex-col bg-surface/30">
                    <span class="font-label-md text-label-md text-text-muted mb-2 text-right">4</span>
                </div>
                <!-- Day with Multiple -->
                <div class="border border-border-subtle rounded-xl p-2 min-h-[100px] flex flex-col bg-surface-container-lowest">
                    <span class="font-label-md text-label-md text-on-surface mb-2 text-right">5</span>
                    <div class="flex flex-col gap-1.5">
                        <div class="bg-stage-site-visit/10 border border-stage-site-visit/30 rounded-lg p-1.5 px-2 cursor-pointer hover:bg-stage-site-visit/20 transition-colors">
                            <p class="font-label-sm text-label-sm text-stage-site-visit truncate">1:30 PM</p>
                            <p class="font-body-sm text-body-sm text-on-surface truncate font-medium">Meena Krishnan</p>
                        </div>
                        <div class="bg-stage-booked/10 border border-stage-booked/30 rounded-lg p-1.5 px-2 cursor-pointer hover:bg-stage-booked/20 transition-colors">
                            <p class="font-label-sm text-label-sm text-stage-booked truncate">4:00 PM</p>
                            <p class="font-body-sm text-body-sm text-on-surface truncate font-medium">Rahul Sharma</p>
                        </div>
                    </div>
                </div>
                <div class="border border-border-subtle rounded-xl p-2 min-h-[100px] flex flex-col bg-surface/30">
                    <span class="font-label-md text-label-md text-text-muted mb-2 text-right">6</span>
                </div>
                <div class="border border-border-subtle rounded-xl p-2 min-h-[100px] flex flex-col bg-surface/30">
                    <span class="font-label-md text-label-md text-text-muted mb-2 text-right">7</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar: Pending Visits -->
    <div class="w-full lg:w-[320px] flex flex-col gap-4">
        <div class="bg-surface-container-lowest rounded-2xl border border-border-subtle p-5 flex flex-col h-[600px]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-headline-sm text-headline-sm text-primary">Pending Visits</h3>
                <span class="bg-surface-container-high text-on-surface font-label-sm text-label-sm px-2 py-0.5 rounded-full">12</span>
            </div>
            <div class="relative mb-4">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-text-muted">search</span>
                <input class="w-full bg-surface pl-9 pr-3 py-2 rounded-lg border border-border-subtle font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow" placeholder="Filter leads..." type="text">
            </div>
            <div class="flex-1 overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                <!-- Pending Lead Card -->
                <div class="bg-surface rounded-xl p-3 border border-border-subtle hover:border-primary/30 transition-colors group cursor-grab">
                    <div class="flex justify-between items-start mb-2">
                        <p class="font-body-sm text-body-sm font-semibold text-primary">Kiran Verma</p>
                        <span class="bg-stage-site-visit/10 text-stage-site-visit border border-stage-site-visit/30 px-2 py-0.5 rounded-full font-label-sm text-label-sm">Site Visit</span>
                    </div>
                    <p class="font-label-sm text-label-sm text-text-muted mb-3">HiLITE Business Park • 2BHK</p>
                    <button class="w-full py-1.5 border border-border-subtle rounded-lg text-on-surface font-label-sm text-label-sm hover:bg-surface-container-high transition-colors flex justify-center items-center gap-1 group-hover:border-primary/50">
                        <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                        Schedule
                    </button>
                </div>
                <!-- Pending Lead Card -->
                <div class="bg-surface rounded-xl p-3 border border-border-subtle hover:border-primary/30 transition-colors group cursor-grab">
                    <div class="flex justify-between items-start mb-2">
                        <p class="font-body-sm text-body-sm font-semibold text-primary">Deepa Menon</p>
                        <span class="bg-stage-site-visit/10 text-stage-site-visit border border-stage-site-visit/30 px-2 py-0.5 rounded-full font-label-sm text-label-sm">Site Visit</span>
                    </div>
                    <p class="font-label-sm text-label-sm text-text-muted mb-3">HiLITE Olympus • 3BHK</p>
                    <button class="w-full py-1.5 border border-border-subtle rounded-lg text-on-surface font-label-sm text-label-sm hover:bg-surface-container-high transition-colors flex justify-center items-center gap-1 group-hover:border-primary/50">
                        <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                        Schedule
                    </button>
                </div>
                <!-- Pending Lead Card -->
                <div class="bg-surface rounded-xl p-3 border border-border-subtle hover:border-primary/30 transition-colors group cursor-grab">
                    <div class="flex justify-between items-start mb-2">
                        <p class="font-body-sm text-body-sm font-semibold text-primary">Vikram Patel</p>
                        <span class="bg-stage-site-visit/10 text-stage-site-visit border border-stage-site-visit/30 px-2 py-0.5 rounded-full font-label-sm text-label-sm">Site Visit</span>
                    </div>
                    <p class="font-label-sm text-label-sm text-text-muted mb-3">HiLITE Mall • Commercial Space</p>
                    <button class="w-full py-1.5 border border-border-subtle rounded-lg text-on-surface font-label-sm text-label-sm hover:bg-surface-container-high transition-colors flex justify-center items-center gap-1 group-hover:border-primary/50">
                        <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                        Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom scrollbar for sidebar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: var(--border-subtle);
        border-radius: 10px;
    }
</style>
@endsection
