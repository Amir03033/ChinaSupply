@props(['href' => null, 'variant' => 'primary', 'size' => 'md', 'type' => 'submit', 'icon' => null])
@php
    $classes = [
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
        match ($size) { 'sm' => 'h-9 px-3.5 text-sm', 'lg' => 'h-12 px-6 text-base', default => 'h-11 px-5 text-sm' },
        match ($variant) {
            'secondary' => 'border border-ink-600 bg-ink-800 text-ink-50 hover:border-ink-500 hover:bg-ink-700',
            'ghost' => 'text-ink-200 hover:bg-ink-800 hover:text-ink-50',
            default => 'bg-brand-600 text-white shadow-[0_0_0_1px_rgb(96_165_250/0.3),0_8px_24px_-8px_rgb(37_99_235/0.6)] hover:bg-brand-500',
        },
    ];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}@if ($icon)<x-dynamic-component :component="$icon" class="h-4 w-4" />@endif</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}@if ($icon)<x-dynamic-component :component="$icon" class="h-4 w-4" />@endif</button>
@endif
