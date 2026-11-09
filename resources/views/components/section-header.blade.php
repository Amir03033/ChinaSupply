@props(['eyebrow' => null, 'title', 'lead' => null, 'as' => 'h2', 'align' => 'left'])
<div {{ $attributes->class(['max-w-3xl', 'mx-auto text-center' => $align === 'center']) }}>
    @if ($eyebrow)
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-400">{{ $eyebrow }}</p>
    @endif
    <{{ $as }} @class(['font-semibold tracking-tight text-ink-50', 'mt-3' => $eyebrow, $as === 'h1' ? 'text-4xl sm:text-5xl' : 'text-2xl sm:text-3xl'])>{{ $title }}</{{ $as }}>
    @if ($lead)
        <p class="mt-4 text-base leading-7 text-ink-300 sm:text-lg">{{ $lead }}</p>
    @endif
</div>
