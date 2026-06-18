@props(['label', 'value', 'subtext' => null, 'valueColor' => 'text-on-surface'])

<div class="flex-1">
    <p class="text-label-sm text-secondary uppercase tracking-wider font-bold mb-1">{{ $label }}</p>
    <p class="text-headline-lg {{ $valueColor }} font-bold">
        {{ $value }} 
        @if($subtext)
            <span class="text-body-md text-text-muted font-normal ml-2">{{ $subtext }}</span>
        @endif
    </p>
</div>
