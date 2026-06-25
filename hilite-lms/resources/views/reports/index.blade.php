@extends('layouts.app')

@section('content')
<div class="max-w-[1400px] w-full mx-auto relative">
    <!-- Page Header -->
    <div class="flex items-end justify-between mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-1">Performance Analytics</h2>
            <p class="font-body-md text-body-md text-text-muted">Comprehensive overview of lead intake, conversion, and agent activity.</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Date Picker Simulation -->
            <div class="flex items-center bg-surface-container-lowest border border-border-subtle rounded-lg py-1.5 px-3 cursor-pointer hover:border-outline-variant transition-colors">
                <span class="material-symbols-outlined text-text-muted mr-2" style="font-size: 18px;">calendar_today</span>
                <span class="font-label-md text-label-md text-primary">Last 30 Days</span>
                <span class="material-symbols-outlined text-text-muted ml-2" style="font-size: 18px;">arrow_drop_down</span>
            </div>
            <x-button variant="secondary">
                <span class="material-symbols-outlined" style="font-size: 18px;">download</span>
                Export Report
            </x-button>
        </div>
    </div>

    <!-- Bento Grid Layout -->
    <div class="grid grid-cols-12 gap-6 pb-12">
        
        <!-- KPI 1: Total Leads -->
        <x-card class="col-span-3 flex flex-col justify-between !p-card-padding">
            <div class="flex justify-between items-start mb-4">
                <span class="font-label-md text-label-md text-text-muted uppercase tracking-wider">Total Leads</span>
                <div class="w-8 h-8 rounded-full bg-surface-container-low flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined" style="font-size: 18px;">group</span>
                </div>
            </div>
            <div>
                <div class="flex items-baseline gap-2">
                    <span class="font-headline-lg text-headline-lg text-primary">{{ number_format($totalLeads) }}</span>
                    <span class="font-label-sm text-label-sm {{ $leadGrowth >= 0 ? 'text-stage-interested' : 'text-stage-lost' }} flex items-center">
                        <span class="material-symbols-outlined" style="font-size: 14px;">{{ $leadGrowth >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
                        {{ abs($leadGrowth) }}%
                    </span>
                </div>
                <p class="font-body-sm text-body-sm text-text-muted mt-1">vs. previous {{ $days }} days</p>
            </div>
        </x-card>

        <!-- KPI 2: Conversion Rate -->
        <x-card class="col-span-3 flex flex-col justify-between !p-card-padding">
            <div class="flex justify-between items-start mb-4">
                <span class="font-label-md text-label-md text-text-muted uppercase tracking-wider">Conversion Rate</span>
                <div class="w-8 h-8 rounded-full bg-surface-container-low flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined" style="font-size: 18px;">monitoring</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <!-- Simple CSS radial progress representation -->
                <div class="w-12 h-12 rounded-full border-4 border-surface-container-high border-t-stage-new border-r-stage-new flex items-center justify-center transform rotate-45">
                    <div class="transform -rotate-45 font-label-md text-label-md text-primary">{{ $conversionRate }}%</div>
                </div>
                <div>
                    <span class="font-headline-md text-headline-md text-primary">{{ $conversionRate }}%</span>
                    <p class="font-body-sm text-body-sm text-text-muted">Avg. across all teams</p>
                </div>
            </div>
        </x-card>

        <!-- KPI 3: Avg Response Time -->
        <x-card class="col-span-3 flex flex-col justify-between !p-card-padding">
            <div class="flex justify-between items-start mb-4">
                <span class="font-label-md text-label-md text-text-muted uppercase tracking-wider">Avg. Response Time</span>
                <div class="w-8 h-8 rounded-full bg-surface-container-low flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined" style="font-size: 18px;">timer</span>
                </div>
            </div>
            <div>
                <div class="flex items-baseline gap-2">
                    <span class="font-headline-lg text-headline-lg text-primary">{{ $avgResponseTime }}</span>
                    <span class="font-label-sm text-label-sm text-stage-lost flex items-center">
                        <span class="material-symbols-outlined" style="font-size: 14px;">arrow_upward</span>
                        {{ $avgResponseTrend }}
                    </span>
                </div>
                <p class="font-body-sm text-body-sm text-text-muted mt-1">Target: < 15m</p>
            </div>
        </x-card>

        <!-- KPI 4: SLA Fulfillment -->
        <x-card class="col-span-3 flex flex-col justify-between !p-card-padding">
            <div class="flex justify-between items-start mb-4">
                <span class="font-label-md text-label-md text-text-muted uppercase tracking-wider">SLA Fulfillment</span>
                <div class="w-8 h-8 rounded-full bg-surface-container-low flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined" style="font-size: 18px;">verified</span>
                </div>
            </div>
            <div>
                <div class="flex items-end justify-between mb-2">
                    <span class="font-headline-lg text-headline-lg text-primary">{{ $slaFulfillment }}%</span>
                </div>
                <div class="w-full bg-surface-container-high rounded-full h-1.5">
                    <div class="bg-stage-booked h-1.5 rounded-full" style="width: {{ $slaFulfillment }}%"></div>
                </div>
                <p class="font-body-sm text-body-sm text-text-muted mt-2">{{ $totalSlaBreaches }} breaches logged</p>
            </div>
        </x-card>

        <!-- Main Chart Area: Monthly Trend -->
        <x-card class="col-span-8 !p-card-padding">
            <div class="flex justify-between items-center mb-2">
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-primary">Lead Intake vs. Conversions</h3>
                    <p class="font-body-sm text-body-sm text-text-muted mt-0.5">Monthly breakdown for the last 9 months</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-2 font-label-sm text-label-sm text-text-muted">
                        <span class="inline-block w-10 h-3 rounded-sm bg-primary/80"></span> Intake
                    </span>
                    <span class="flex items-center gap-2 font-label-sm text-label-sm text-text-muted">
                        <span class="inline-block w-10 h-3 rounded-sm bg-stage-booked/80"></span> Conversions
                    </span>
                </div>
            </div>

            <!-- SVG Chart -->
            <div class="relative w-full mt-4" id="chart-wrap">
                <svg id="intake-chart" viewBox="0 0 700 260" preserveAspectRatio="xMidYMid meet" class="w-full overflow-visible" style="height: 260px;">
                    <defs>
                        <!-- Intake bar gradient -->
                        <linearGradient id="intakeGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#3B6B35" stop-opacity="0.9"/>
                            <stop offset="100%" stop-color="#3B6B35" stop-opacity="0.5"/>
                        </linearGradient>
                        <!-- Conversion bar gradient -->
                        <linearGradient id="convGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#6AB04C" stop-opacity="0.95"/>
                            <stop offset="100%" stop-color="#6AB04C" stop-opacity="0.5"/>
                        </linearGradient>
                    </defs>

                    @php
                        $maxVal   = max(1, collect($chartData)->max('intake') * 1.2);        // y-axis max with 20% headroom
                        $padL     = 44;         // left padding for y-axis labels
                        $padR     = 12;
                        $padT     = 10;
                        $padB     = 34;         // bottom padding for x-axis labels
                        $chartW   = 700 - $padL - $padR;
                        $chartH   = 260 - $padT - $padB;
                        $n        = count($chartData);
                        $groupW   = $chartW / max(1, $n);
                        $barW     = $groupW * 0.3;
                        $gap      = $groupW * 0.06;
                        
                        // Generate dynamic Y-axis steps based on maxVal
                        $stepSize = ceil($maxVal / 4);
                        $ySteps   = [0, $stepSize, $stepSize*2, $stepSize*3, $stepSize*4];
                        $maxVal   = $stepSize * 4; // Update max to highest step
                    @endphp

                    <!-- Y-axis grid lines + labels -->
                    @foreach($ySteps as $yVal)
                        @php
                            $yPos = $padT + $chartH - ($yVal / $maxVal) * $chartH;
                        @endphp
                        <line x1="{{ $padL }}" y1="{{ $yPos }}" x2="{{ 700 - $padR }}" y2="{{ $yPos }}"
                              stroke="#e0e4da" stroke-width="1" stroke-dasharray="{{ $yVal > 0 ? '4,4' : '' }}"/>
                        <text x="{{ $padL - 8 }}" y="{{ $yPos + 4 }}" text-anchor="end"
                              fill="#8A8D85" font-size="10" font-family="Inter, sans-serif">{{ $yVal }}</text>
                    @endforeach

                    <!-- Y-axis label -->
                    <text x="10" y="{{ $padT + $chartH / 2 }}" text-anchor="middle"
                          fill="#8A8D85" font-size="10" font-family="Inter, sans-serif"
                          transform="rotate(-90, 10, {{ $padT + $chartH / 2 }})">Leads</text>

                    <!-- Bars + X labels + Tooltips -->
                    @foreach($chartData as $i => $d)
                        @php
                            $cx          = $padL + $i * $groupW + $groupW / 2;
                            $intakeH     = ($d['intake'] / $maxVal) * $chartH;
                            $convH       = ($d['conv']   / $maxVal) * $chartH;
                            $intakeX     = $cx - $barW - $gap / 2;
                            $convX       = $cx + $gap / 2;
                            $intakeY     = $padT + $chartH - $intakeH;
                            $convY       = $padT + $chartH - $convH;
                            $baseline    = $padT + $chartH;
                            $convRate    = $d['intake'] > 0 ? round($d['conv'] / $d['intake'] * 100, 1) : 0;
                        @endphp

                        <!-- Group hover zone (transparent) -->
                        <rect x="{{ $padL + $i * $groupW + 4 }}" y="{{ $padT }}"
                              width="{{ $groupW - 8 }}" height="{{ $chartH }}"
                              fill="transparent" class="chart-hover-zone" rx="4"
                              data-month="{{ $d['month'] }}" data-intake="{{ $d['intake'] }}"
                              data-conv="{{ $d['conv'] }}" data-rate="{{ $convRate }}"/>

                        <!-- Intake bar -->
                        <rect x="{{ $intakeX }}" y="{{ $intakeY }}"
                              width="{{ $barW }}" height="{{ $intakeH }}"
                              fill="url(#intakeGrad)" rx="3" ry="3"
                              class="chart-bar intake-bar" style="transition: opacity .15s"/>

                        <!-- Conversion bar -->
                        <rect x="{{ $convX }}" y="{{ $convY }}"
                              width="{{ $barW }}" height="{{ $convH }}"
                              fill="url(#convGrad)" rx="3" ry="3"
                              class="chart-bar conv-bar" style="transition: opacity .15s"/>

                        <!-- Value labels on top of bars (only if tall enough) -->
                        @if($intakeH > 18)
                        <text x="{{ $intakeX + $barW / 2 }}" y="{{ $intakeY - 4 }}"
                              text-anchor="middle" fill="#3B6B35" font-size="9" font-weight="600"
                              font-family="Inter, sans-serif">{{ $d['intake'] }}</text>
                        @endif
                        @if($convH > 18)
                        <text x="{{ $convX + $barW / 2 }}" y="{{ $convY - 4 }}"
                              text-anchor="middle" fill="#4a7c2f" font-size="9" font-weight="600"
                              font-family="Inter, sans-serif">{{ $d['conv'] }}</text>
                        @endif

                        <!-- X-axis month label -->
                        <text x="{{ $cx }}" y="{{ $baseline + 18 }}" text-anchor="middle"
                              fill="#8A8D85" font-size="11" font-family="Inter, sans-serif"
                              font-weight="500">{{ $d['month'] }}</text>
                    @endforeach

                    <!-- Conversion rate trend line -->
                    @php
                        $linePoints = '';
                        foreach ($chartData as $i => $d) {
                            $cx = $padL + $i * $groupW + $groupW / 2;
                            $convH = ($d['conv'] / $maxVal) * $chartH;
                            $linePoints .= round($cx, 1) . ',' . round($padT + $chartH - $convH, 1) . ' ';
                        }
                    @endphp
                    <polyline points="{{ trim($linePoints) }}"
                              fill="none" stroke="#6AB04C" stroke-width="2"
                              stroke-dasharray="5,3" stroke-linejoin="round" stroke-linecap="round"
                              opacity="0.7"/>

                    <!-- Dot on each conversion point -->
                    @foreach($chartData as $i => $d)
                        @php
                            $cx    = $padL + $i * $groupW + $groupW / 2;
                            $convH = ($d['conv'] / $maxVal) * $chartH;
                            $cy    = $padT + $chartH - $convH;
                        @endphp
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3.5"
                                fill="#fff" stroke="#6AB04C" stroke-width="2"/>
                    @endforeach
                </svg>

                <!-- Tooltip (hidden, positioned by JS) -->
                <div id="chart-tooltip"
                     class="hidden absolute pointer-events-none z-20 bg-surface-container-lowest border border-border-subtle shadow-lg rounded-xl px-3 py-2 text-left min-w-[130px]">
                    <p id="tt-month"  class="font-label-md font-bold text-on-surface mb-1"></p>
                    <p id="tt-intake" class="font-body-sm text-primary flex justify-between gap-3">
                        <span>Intake</span><span class="font-bold"></span>
                    </p>
                    <p id="tt-conv"   class="font-body-sm text-stage-booked flex justify-between gap-3">
                        <span>Converted</span><span class="font-bold"></span>
                    </p>
                    <p id="tt-rate"   class="font-label-sm text-text-muted mt-1 pt-1 border-t border-border-subtle flex justify-between gap-3">
                        <span>Conv. Rate</span><span class="font-bold text-on-surface"></span>
                    </p>
                </div>
            </div>

            <script>
            (function() {
                const zones   = document.querySelectorAll('.chart-hover-zone');
                const tooltip = document.getElementById('chart-tooltip');
                const ttMonth = document.getElementById('tt-month');
                const ttIntake= document.getElementById('tt-intake').querySelector('span:last-child');
                const ttConv  = document.getElementById('tt-conv').querySelector('span:last-child');
                const ttRate  = document.getElementById('tt-rate').querySelector('span:last-child');
                const wrap    = document.getElementById('chart-wrap');

                zones.forEach(zone => {
                    zone.addEventListener('mouseenter', function(e) {
                        ttMonth.textContent   = this.dataset.month;
                        ttIntake.textContent  = this.dataset.intake + ' leads';
                        ttConv.textContent    = this.dataset.conv   + ' leads';
                        ttRate.textContent    = this.dataset.rate   + '%';
                        tooltip.classList.remove('hidden');

                        // Dim other bars
                        document.querySelectorAll('.chart-bar').forEach(b => b.style.opacity = '0.3');
                        const idx = Array.from(zones).indexOf(this);
                        document.querySelectorAll('.intake-bar')[idx].style.opacity = '1';
                        document.querySelectorAll('.conv-bar')[idx].style.opacity   = '1';
                    });

                    zone.addEventListener('mousemove', function(e) {
                        const rect = wrap.getBoundingClientRect();
                        let left = e.clientX - rect.left + 12;
                        let top  = e.clientY - rect.top  - 80;
                        // Keep tooltip inside wrap
                        if (left + 140 > rect.width) left = left - 160;
                        if (top < 0) top = 4;
                        tooltip.style.left = left + 'px';
                        tooltip.style.top  = top  + 'px';
                    });

                    zone.addEventListener('mouseleave', function() {
                        tooltip.classList.add('hidden');
                        document.querySelectorAll('.chart-bar').forEach(b => b.style.opacity = '1');
                    });
                });
            })();
            </script>
        </x-card>

        <!-- Side Panel: Source Distribution -->
        <x-card class="col-span-4 flex flex-col !p-card-padding">
            <h3 class="font-headline-sm text-headline-sm text-primary mb-6">Lead Source Distribution</h3>
            <div class="flex-1 flex flex-col justify-center gap-4">
                @forelse($sources as $s)
                <div>
                    <div class="flex justify-between font-label-md text-label-md mb-1">
                        <span class="text-primary flex items-center gap-2"><span class="w-2 h-2 rounded-full {{ $s['color'] }}"></span> {{ $s['name'] }}</span>
                        <span class="text-text-muted">{{ $s['percent'] }}% ({{ $s['count'] }})</span>
                    </div>
                    <div class="w-full bg-surface-container-high rounded-full h-1.5"><div class="{{ $s['color'] }} h-1.5 rounded-full" style="width: {{ $s['percent'] }}%"></div></div>
                </div>
                @empty
                <p class="text-text-muted font-body-sm text-center py-4">No source data available for this period.</p>
                @endforelse
            </div>
        </x-card>

        <!-- Agent Performance Table -->
        <x-card class="col-span-12 !p-0">
            <div class="p-4 border-b border-border-subtle flex justify-between items-center bg-surface">
                <h3 class="font-headline-sm text-headline-sm text-primary">Agent Performance</h3>
                <button class="font-label-sm text-label-sm text-text-muted hover:text-primary transition-colors flex items-center gap-1">
                    View All <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low font-label-sm text-label-sm text-text-muted uppercase tracking-wider border-b border-border-subtle">
                            <th class="p-4 font-medium">Agent Name</th>
                            <th class="p-4 font-medium">Leads Assigned</th>
                            <th class="p-4 font-medium">Conversion %</th>
                            <th class="p-4 font-medium">Avg. SLA Breaches</th>
                        </tr>
                    </thead>
                    <tbody class="font-body-sm text-body-sm divide-y divide-border-subtle">
                        @forelse($agents as $agent)
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-label-md text-label-md">{{ $agent['initials'] }}</div>
                                    <span class="font-medium text-primary">{{ $agent['name'] }}</span>
                                </div>
                            </td>
                            <td class="p-4 text-on-surface-variant">{{ $agent['assigned'] }}</td>
                            <td class="p-4"><span class="inline-flex items-center gap-1 text-stage-booked bg-stage-booked/10 px-2 py-0.5 rounded-full font-label-sm">{{ $agent['conversion'] }}%</span></td>
                            <td class="p-4 text-on-surface-variant">{{ $agent['avg_breach'] }} / lead</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-text-muted">No agent activity in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Heatmap Section -->
        <x-card class="col-span-12 !p-card-padding mt-6" id="heatmap-section">
            <h3 class="font-headline-sm text-headline-sm text-primary mb-2">Lead Engagement Heatmap</h3>
            <p class="font-body-sm text-body-sm text-text-muted mb-6">Activity concentration by time and day (real data).</p>
            <div class="w-full overflow-x-auto">
                <div class="min-w-[800px] flex">
                    <!-- Y Axis Labels -->
                    <div class="flex flex-col justify-between py-1 pr-4 text-label-sm text-text-muted font-medium w-[80px]">
                        @foreach(['12am', '4am', '8am', '12pm', '4pm', '8pm'] as $time)
                            <div class="flex-1 flex items-center justify-end">{{ $time }}</div>
                        @endforeach
                    </div>
                    <!-- Heatmap Grid -->
                    <div class="flex-1">
                        <div class="grid grid-cols-7 gap-2 h-64">
                            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayIndex => $dayLabel)
                                <div class="flex flex-col gap-2">
                                    @for($timeBlock = 0; $timeBlock < 6; $timeBlock++)
                                        @php
                                            $intensity = $heatmapData[($dayIndex * 6) + $timeBlock] ?? 0.05;
                                        @endphp
                                        <div class="flex-1 rounded-md transition-opacity hover:opacity-80" style="background-color: rgba(99, 102, 241, {{ $intensity }})" title="{{ $dayLabel }} {{ $timeBlock*4 }}:00 - {{ ($timeBlock+1)*4 }}:00 | Intensity: {{ round($intensity * 100) }}%"></div>
                                    @endfor
                                </div>
                            @endforeach
                        </div>
                        <!-- X Axis Labels -->
                        <div class="grid grid-cols-7 gap-2 mt-3">
                            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayLabel)
                                <div class="text-center text-label-sm text-text-muted font-medium uppercase tracking-wider">{{ $dayLabel }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
