@php
    $crumbs = [[__('ui.nav.home'), localized_route('home')], [__('categories.title'), localized_route('categories.index')]];
    foreach ($ancestors as $a) { $crumbs[] = [$a->name(), localized_route('categories.show', ['slug' => $a->slug()])]; }
    $crumbs[] = [$translation->name, null];
@endphp
<x-layouts.app :title="$translation->seo_title ?: $translation->name"
               :description="$translation->seo_description ?: __('categories.show_meta', ['name' => $translation->name])"
               :alternates="$alternates">
    <x-container class="py-12">
        <x-breadcrumbs :items="$crumbs" />

        <div class="mt-8 flex flex-col gap-6 md:flex-row md:items-start">
            @if ($category->imageUrl())
                <img src="{{ $category->imageUrl() }}" alt="" width="160" height="160" class="h-28 w-28 rounded-xl border border-ink-700 object-cover md:h-40 md:w-40" loading="eager">
            @else
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl border border-ink-600 bg-ink-850 text-brand-400"><x-category-icon :icon="$category->icon" class="h-7 w-7" /></span>
            @endif
            <div class="max-w-3xl">
                <h1 class="text-3xl font-semibold tracking-tight text-ink-50 sm:text-4xl">{{ $translation->name }}</h1>
                @if ($translation->description)
                    <p class="mt-4 text-lg leading-8 text-ink-300">{{ $translation->description }}</p>
                @endif
            </div>
        </div>

        @if ($children->isNotEmpty())
            <section class="mt-12" aria-labelledby="subcats">
                <h2 id="subcats" class="text-lg font-semibold text-ink-50">{{ __('categories.subcategories') }}</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($children as $child)
                        <x-category-card :category="$child" :count="$child->children_count" />
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-12" aria-labelledby="suppliers">
            <h2 id="suppliers" class="text-lg font-semibold text-ink-50">{{ __('categories.suppliers') }}</h2>
            @if ($suppliers->isEmpty())
                <x-empty-state class="mt-5" icon="heroicon-o-building-office-2" :title="__('categories.no_suppliers_title')">
                    {{ __('categories.no_suppliers_text', ['name' => $translation->name]) }}
                    <x-slot:actions>
                        <x-button :href="localized_route('contact', ['topic' => 'sourcing'])">{{ __('ui.cta.start_request') }}</x-button>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <p class="mt-1 text-sm text-ink-400">{{ __('categories.suppliers_sorted') }}</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($suppliers as $supplier)
                        <x-supplier-card :supplier="$supplier" />
                    @endforeach
                </div>
                <div class="mt-6">{{ $suppliers->links() }}</div>
            @endif
        </section>
    </x-container>
</x-layouts.app>
