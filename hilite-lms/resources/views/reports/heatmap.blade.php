@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-8">
    <!-- Page Header -->
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-1">Lead Distribution Heatmap</h2>
            <p class="font-body-md text-body-md text-text-muted">Overview of lead origin hotspots and sources for this month.</p>
        </div>
        <div class="flex gap-2">
            <button class="px-4 py-2 bg-surface-container-low border border-border-subtle rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container-high transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                This Month
            </button>
            <button class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-surface-tint transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Export
            </button>
        </div>
    </div>

    <!-- Main Content Bento Grid -->
    <div class="grid grid-cols-12 gap-gutter">
        <div class="col-span-8 bg-surface-container-lowest border border-border-subtle rounded-2xl p-card-padding shadow-sm relative overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Lead Distribution</h3>
                <div class="flex gap-2">
                    <button class="w-8 h-8 rounded-full bg-surface-container-low border border-border-subtle flex items-center justify-center hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                    </button>
                    <button class="w-8 h-8 rounded-full bg-surface-container-low border border-border-subtle flex items-center justify-center hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[18px]">remove</span>
                    </button>
                    <button class="w-8 h-8 rounded-full bg-surface-container-low border border-border-subtle flex items-center justify-center hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[18px]">layers</span>
                    </button>
                </div>
            </div>
            
            <div class="relative h-[400px] bg-surface-container-low rounded-xl overflow-hidden border border-border-subtle/50">
                <!-- Mock Map Background -->
                <div class="absolute inset-0 opacity-40 grayscale">
                    <svg class="w-full h-full" fill="none" viewBox="0 0 800 400" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 0H800V400H0V0Z" fill="#E6E6DF"></path>
                        <path d="M100 50L150 100L200 80L250 120L300 90L350 150L400 130L450 180L500 160L550 210L600 190L650 240L700 220L750 270" stroke="#8A8D85" stroke-width="0.5"></path>
                        <path d="M50 150L120 220L180 200L240 260L310 230L380 290L440 270L510 330L570 310L640 370" stroke="#8A8D85" stroke-width="0.5"></path>
                    </svg>
                </div>
                
                <!-- Heatmap Overlays -->
                <div class="absolute top-1/4 left-1/3 w-32 h-32 bg-[#3B82F6]/30 rounded-full blur-2xl"></div>
                <div class="absolute top-1/2 left-1/2 w-40 h-40 bg-[#F59E0B]/30 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 right-1/4 w-24 h-24 bg-[#10B981]/30 rounded-full blur-2xl"></div>
                
                <!-- Floating Hotspots Panel -->
                <div class="absolute top-4 right-4 w-48 bg-surface-container-lowest/90 backdrop-blur-sm border border-border-subtle rounded-xl p-3 shadow-lg">
                    <p class="font-label-sm text-label-sm text-text-muted uppercase tracking-wider mb-3">Top Hotspots</p>
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="font-label-md text-label-md text-on-surface">Kochi</span>
                            <span class="font-body-sm text-body-sm font-semibold">412</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-label-md text-label-md text-on-surface">Calicut</span>
                            <span class="font-body-sm text-body-sm font-semibold">285</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-label-md text-label-md text-on-surface">Trivandrum</span>
                            <span class="font-body-sm text-body-sm font-semibold">194</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Source Distribution -->
        <div class="col-span-4 bg-surface-container-lowest border border-border-subtle rounded-2xl p-card-padding shadow-sm flex flex-col">
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-6">Lead Sources</h3>
            <div class="flex-grow flex flex-col justify-center gap-6">
                <!-- Item 1 -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-8 bg-[#3B82F6] rounded-full"></div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface">Webhook (API)</p>
                            <p class="font-body-sm text-body-sm text-text-muted">Website Forms</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-headline-sm text-headline-sm text-on-surface">65%</p>
                        <p class="font-label-sm text-label-sm text-text-muted">811 leads</p>
                    </div>
                </div>
                <!-- Item 2 -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-8 bg-[#F59E0B] rounded-full"></div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface">CSV Import</p>
                            <p class="font-body-sm text-body-sm text-text-muted">Bulk Uploads</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-headline-sm text-headline-sm text-on-surface">25%</p>
                        <p class="font-label-sm text-label-sm text-text-muted">312 leads</p>
                    </div>
                </div>
                <!-- Item 3 -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-8 bg-[#6B7280] rounded-full"></div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface">Manual Entry</p>
                            <p class="font-body-sm text-body-sm text-text-muted">Walk-ins</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-headline-sm text-headline-sm text-on-surface">10%</p>
                        <p class="font-label-sm text-label-sm text-text-muted">125 leads</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
