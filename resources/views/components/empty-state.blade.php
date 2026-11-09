@props(['title', 'icon' => 'heroicon-o-magnifying-glass'])
<div {{ $attributes->class('surface flex flex-col items-center px-6 py-12 text-center') }}>
    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-ink-600 bg-ink-800 text-ink-300">
        <x-dynamic-component :component="$icon" class="h-6 w-6" aria-hidden="true" />
    </span>
    <h3 class="mt-4 text-lg font-semibold text-ink-50">{{ $title }}</h3>
    <div class="mt-2 max-w-md text-sm leading-6 text-ink-300">{{ $slot }}</div>
    @isset($actions)
        <div class="mt-6 flex flex-wrap justify-center gap-3">{{ $actions }}</div>
    @endisset
</div>
