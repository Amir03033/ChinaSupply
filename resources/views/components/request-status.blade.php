@props(['status'])
@php
    $classes = match ($status->value) {
        'needs_info' => 'border-warning-400/60 text-warning-400',
        'shortlist_ready', 'completed' => 'border-brand-500/60 text-brand-300',
        'cancelled', 'declined' => 'border-ink-600 text-ink-400',
        default => 'border-ink-500 text-ink-200',
    };
@endphp
<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-medium', $classes]) }}>{{ $status->buyerLabel() }}</span>
