@props(['items'])
{{-- $items: list of [label, url|null] --}}
<nav aria-label="{{ __('ui.breadcrumbs') }}" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-ink-400">
        @foreach ($items as $i => [$label, $url])
            @if ($i > 0)<li aria-hidden="true"><x-heroicon-m-chevron-right class="h-4 w-4" /></li>@endif
            <li>
                @if ($url)
                    <a href="{{ $url }}" class="hover:text-ink-100">{{ $label }}</a>
                @else
                    <span class="text-ink-200" aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
