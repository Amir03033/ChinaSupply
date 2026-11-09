@props(['category', 'count' => null])
{{-- Row layout on phones (scannable list), tile layout from sm upwards. --}}
<a href="{{ localized_route('categories.show', ['slug' => $category->slug() ?? $category->translation()?->slug]) }}"
   class="group surface flex h-full items-center gap-4 p-4 transition hover:border-brand-500/60 hover:bg-ink-800 sm:flex-col sm:items-stretch sm:p-5">
    <div class="flex shrink-0 items-start justify-between gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-ink-600 bg-ink-800 text-brand-400 group-hover:border-brand-500/50">
            <x-category-icon :icon="$category->icon" class="h-5 w-5" />
        </span>
        <x-heroicon-o-arrow-up-right class="hidden h-4 w-4 text-ink-400 transition group-hover:text-brand-400 sm:block" aria-hidden="true" />
    </div>
    <div class="min-w-0 flex-1">
        <h3 class="font-medium leading-snug text-ink-50">{{ $category->name() }}</h3>
        @if ($count !== null && $count > 0)
            <p class="mt-1 text-sm text-ink-400">{{ trans_choice('categories.subcategory_count', $count, ['count' => $count]) }}</p>
        @endif
    </div>
    <x-heroicon-m-chevron-right class="h-5 w-5 shrink-0 text-ink-500 sm:hidden" aria-hidden="true" />
</a>
