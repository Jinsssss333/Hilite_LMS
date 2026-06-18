@props(['stage'])

@php
    $stageClassMap = [
        'new' => 'bg-stage-new/10 text-stage-new border-stage-new/20',
        'contacted' => 'bg-stage-contacted/10 text-stage-contacted border-stage-contacted/20',
        'interested' => 'bg-stage-interested/10 text-stage-interested border-stage-interested/20',
        'site visit' => 'bg-stage-site-visit/10 text-stage-site-visit border-stage-site-visit/20',
        'negotiation' => 'bg-stage-negotiation/10 text-stage-negotiation border-stage-negotiation/20',
        'booked' => 'bg-stage-booked/10 text-stage-booked border-stage-booked/20',
        'lost' => 'bg-stage-lost/10 text-stage-lost border-stage-lost/20',
        'not interested' => 'bg-stage-not-interested/10 text-stage-not-interested border-stage-not-interested/20',
    ];
    $classes = $stageClassMap[strtolower($stage)] ?? 'bg-surface-container text-on-surface border-border-subtle';
@endphp

<span {{ $attributes->merge(['class' => "px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wide flex-shrink-0 $classes"]) }}>
    {{ $slot->isEmpty() ? $stage : $slot }}
</span>
