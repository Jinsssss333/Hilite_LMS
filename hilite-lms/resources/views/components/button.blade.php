@props(['variant' => 'primary'])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-full font-label-md text-label-md transition-colors';
    $variants = [
        'primary' => 'bg-primary text-on-primary hover:bg-tertiary',
        'secondary' => 'bg-surface-container-lowest border border-border-subtle text-on-surface hover:bg-surface-container-low shadow-sm',
        'ghost' => 'text-text-muted hover:text-on-surface hover:bg-surface-container-low'
    ];
    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
