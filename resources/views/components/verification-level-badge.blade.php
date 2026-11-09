@props(['level'])
{{-- Green is reserved for verification. Level 0 is explicitly shown as "not verified". --}}
@php
    $classes = match ((int) $level) {
        3 => 'border-verified-500 bg-verified-500/20 text-verified-400',
        2 => 'border-verified-500/60 text-verified-400',
        1 => 'border-ink-400 text-ink-200',
        default => 'border-ink-600 text-ink-400',
    };
@endphp
<span {{ $attributes->class(['inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-medium', $classes]) }}>
    @if ((int) $level >= 2)<x-heroicon-m-check-badge class="h-3.5 w-3.5" aria-hidden="true" />@endif
    {{ __('verification.level_names.'.(int) $level) }}
</span>
