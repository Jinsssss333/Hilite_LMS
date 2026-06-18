@extends('layouts.app')

@section('content')
<!-- Page Header & Filters -->
<div class="mb-6 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div>
        <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg font-bold text-on-surface mb-1">Follow-ups</h2>
        <p class="font-body-md text-body-md text-text-muted">Manage your scheduled activities and overdue tasks.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Filters -->
        <div class="flex items-center gap-2 bg-surface-container-lowest border border-border-subtle rounded-full px-1 py-1 shadow-sm">
            <select class="bg-transparent border-none text-body-sm text-on-surface focus:ring-0 py-1 pl-3 pr-8 rounded-full cursor-pointer hover:bg-surface-container-low">
                <option value="all">All Activities</option>
                <option value="call">Calls</option>
                <option value="visit">Site Visits</option>
                <option value="email">Emails/Notes</option>
            </select>
            <div class="w-px h-4 bg-border-subtle"></div>
            <select class="bg-transparent border-none text-body-sm text-on-surface focus:ring-0 py-1 pl-3 pr-8 rounded-full cursor-pointer hover:bg-surface-container-low">
                <option value="me">Assigned to Me</option>
                <option value="team">My Team</option>
            </select>
        </div>
        <x-button variant="secondary">
            <span class="material-symbols-outlined" style="font-size: 18px;">calendar_today</span>
            Today
        </x-button>
    </div>
</div>

<!-- List Container -->
<div class="max-w-[1200px] mx-auto space-y-8">
    
    <!-- OVERDUE GROUP -->
    <section>
        <h3 class="font-label-sm text-label-sm text-stage-lost flex items-center gap-2 mb-3 uppercase tracking-wider font-bold">
            <span class="material-symbols-outlined" style="font-size: 16px;">error</span>
            Overdue
        </h3>
        <div class="space-y-2">
            
            <x-card class="followup-card">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-stage-lost"></div>
                <div class="flex-1 flex items-center gap-4 pl-3 w-full">
                    <div class="w-9 h-9 rounded-full bg-error-container flex items-center justify-center flex-shrink-0 text-error">
                        <span class="material-symbols-outlined" style="font-size: 18px;">call</span>
                    </div>
                    <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <div class="sm:col-span-4 flex items-center gap-2">
                            <p class="font-headline-sm text-headline-sm text-on-surface truncate">Sarah Jenkins</p>
                            <x-stage-pill stage="interested"></x-stage-pill>
                        </div>
                        <div class="sm:col-span-5">
                            <p class="font-body-sm text-body-sm text-text-muted truncate flex items-center gap-1.5">
                                <span class="font-medium text-on-surface">Call:</span> Follow up on site visit feedback
                            </p>
                        </div>
                        <div class="sm:col-span-3 text-left sm:text-right">
                            <p class="font-label-sm text-label-sm text-stage-lost font-bold">Yesterday, 2:00 PM</p>
                        </div>
                    </div>
                </div>
                
                <!-- Default Actions -->
                <div class="default-actions mt-3 sm:mt-0 ml-auto pl-4 transition-opacity duration-200">
                    <button class="w-8 h-8 flex items-center justify-center rounded-full border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                    </button>
                </div>
                
                <!-- Quick Actions (Hover) -->
                <div class="quick-actions absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5 bg-surface-container-lowest pl-4 py-1">
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 14px;">edit_document</span> Log
                    </button>
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 14px;">event</span> Reschedule
                    </button>
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm bg-primary text-on-primary hover:bg-tertiary transition-colors flex items-center gap-1">
                        View
                    </button>
                </div>
            </x-card>

            <x-card class="followup-card">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-stage-lost"></div>
                <div class="flex-1 flex items-center gap-4 pl-3 w-full">
                    <div class="w-9 h-9 rounded-full bg-error-container flex items-center justify-center flex-shrink-0 text-error">
                        <span class="material-symbols-outlined" style="font-size: 18px;">mail</span>
                    </div>
                    <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <div class="sm:col-span-4 flex items-center gap-2">
                            <p class="font-headline-sm text-headline-sm text-on-surface truncate">Michael Chang</p>
                            <x-stage-pill stage="new"></x-stage-pill>
                        </div>
                        <div class="sm:col-span-5">
                            <p class="font-body-sm text-body-sm text-text-muted truncate flex items-center gap-1.5">
                                <span class="font-medium text-on-surface">Email:</span> Send brochure for Phase 2
                            </p>
                        </div>
                        <div class="sm:col-span-3 text-left sm:text-right">
                            <p class="font-label-sm text-label-sm text-stage-lost font-bold">Sep 12, 10:00 AM</p>
                        </div>
                    </div>
                </div>
                <!-- Default Actions -->
                <div class="default-actions mt-3 sm:mt-0 ml-auto pl-4 transition-opacity duration-200">
                    <button class="w-8 h-8 flex items-center justify-center rounded-full border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                    </button>
                </div>
                <!-- Quick Actions (Hover) -->
                <div class="quick-actions absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5 bg-surface-container-lowest pl-4 py-1">
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 14px;">edit_document</span> Log
                    </button>
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 14px;">event</span> Reschedule
                    </button>
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm bg-primary text-on-primary hover:bg-tertiary transition-colors flex items-center gap-1">
                        View
                    </button>
                </div>
            </x-card>
        </div>
    </section>

    <!-- TODAY GROUP -->
    <section>
        <h3 class="font-label-sm text-label-sm text-text-muted flex items-center gap-2 mb-3 uppercase tracking-wider font-bold">
            <span class="material-symbols-outlined" style="font-size: 16px;">today</span>
            Today
        </h3>
        <div class="space-y-2">
            <x-card class="followup-card">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-secondary-fixed-dim"></div>
                <div class="flex-1 flex items-center gap-4 pl-3 w-full">
                    <div class="w-9 h-9 rounded-full bg-surface-container-high flex items-center justify-center flex-shrink-0 text-on-surface">
                        <span class="material-symbols-outlined" style="font-size: 18px;">calendar_clock</span>
                    </div>
                    <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <div class="sm:col-span-4 flex items-center gap-2">
                            <p class="font-headline-sm text-headline-sm text-on-surface truncate">Elena Rodriguez</p>
                            <x-stage-pill stage="site visit"></x-stage-pill>
                        </div>
                        <div class="sm:col-span-5">
                            <p class="font-body-sm text-body-sm text-text-muted truncate flex items-center gap-1.5">
                                <span class="font-medium text-on-surface">Meeting:</span> Confirm weekend appointment
                            </p>
                        </div>
                        <div class="sm:col-span-3 text-left sm:text-right">
                            <p class="font-label-sm text-label-sm text-on-surface font-bold">1:30 PM</p>
                        </div>
                    </div>
                </div>
                <!-- Default Actions -->
                <div class="default-actions mt-3 sm:mt-0 ml-auto pl-4 transition-opacity duration-200">
                    <button class="w-8 h-8 flex items-center justify-center rounded-full border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                    </button>
                </div>
                <!-- Quick Actions (Hover) -->
                <div class="quick-actions absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5 bg-surface-container-lowest pl-4 py-1">
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 14px;">edit_document</span> Log
                    </button>
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 14px;">event</span> Reschedule
                    </button>
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm bg-primary text-on-primary hover:bg-tertiary transition-colors flex items-center gap-1">
                        View
                    </button>
                </div>
            </x-card>

            <x-card class="followup-card">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-secondary-fixed-dim"></div>
                <div class="flex-1 flex items-center gap-4 pl-3 w-full">
                    <div class="w-9 h-9 rounded-full bg-surface-container-high flex items-center justify-center flex-shrink-0 text-on-surface">
                        <span class="material-symbols-outlined" style="font-size: 18px;">payments</span>
                    </div>
                    <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 items-center">
                        <div class="sm:col-span-4 flex items-center gap-2">
                            <p class="font-headline-sm text-headline-sm text-on-surface truncate">David Chen</p>
                            <x-stage-pill stage="negotiation"></x-stage-pill>
                        </div>
                        <div class="sm:col-span-5">
                            <p class="font-body-sm text-body-sm text-text-muted truncate flex items-center gap-1.5">
                                <span class="font-medium text-on-surface">Followup:</span> Discuss payment plan options
                            </p>
                        </div>
                        <div class="sm:col-span-3 text-left sm:text-right">
                            <p class="font-label-sm text-label-sm text-on-surface font-bold">4:00 PM</p>
                        </div>
                    </div>
                </div>
                <!-- Default Actions -->
                <div class="default-actions mt-3 sm:mt-0 ml-auto pl-4 transition-opacity duration-200">
                    <button class="w-8 h-8 flex items-center justify-center rounded-full border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                    </button>
                </div>
                <!-- Quick Actions (Hover) -->
                <div class="quick-actions absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5 bg-surface-container-lowest pl-4 py-1">
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 14px;">edit_document</span> Log
                    </button>
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm border border-border-subtle text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 14px;">event</span> Reschedule
                    </button>
                    <button class="px-3 py-1.5 rounded-full font-label-sm text-label-sm bg-primary text-on-primary hover:bg-tertiary transition-colors flex items-center gap-1">
                        View
                    </button>
                </div>
            </x-card>
        </div>
    </section>

</div>

<style>
    /* Quick Actions Hover Effect for Follow-up Cards */
    .followup-card .quick-actions {
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.15s ease-in-out;
    }
    .followup-card:hover .quick-actions {
        opacity: 1;
        pointer-events: auto;
    }
    .followup-card:hover .default-actions {
        opacity: 0;
        pointer-events: none;
    }
</style>
@endsection
