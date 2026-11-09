@props(['type' => 'info'])
@php
    [$classes, $icon] = match ($type) {
        'success' => ['border-verified-500/40 bg-verified-500/10 text-ink-100', 'heroicon-o-check-circle'],
        'warning' => ['border-warning-400/40 bg-warning-400/10 text-ink-100', 'heroicon-o-exclamation-triangle'],
        'error' => ['border-danger-500/40 bg-danger-500/10 text-ink-100', 'heroicon-o-x-circle'],
        default => ['border-brand-500/40 bg-brand-500/10 text-ink-100', 'heroicon-o-information-circle'],
    };
@endphp
<div {{ $attributes->class(['flex gap-3 rounded-lg border px-4 py-3 text-sm', $classes]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">
    <x-dynamic-component :component="$icon" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
    <div class="leading-6">{{ $slot }}</div>
</div>
