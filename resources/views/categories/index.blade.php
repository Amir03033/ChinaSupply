<x-layouts.app :title="__('categories.title')" :description="__('categories.meta_description')" :noindex="$search !== ''">
    <x-container class="py-14">
        <x-section-header as="h1" :title="__('categories.title')" :lead="__('categories.lead')" />

        <form method="GET" action="{{ localized_route('categories.index') }}" class="mt-8 flex max-w-xl gap-3" role="search">
            <label for="cat-q" class="sr-only">{{ __('categories.search_label') }}</label>
            <input id="cat-q" type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="{{ __('categories.search_placeholder') }}"
                   class="h-11 flex-1 rounded-lg border border-ink-600 bg-ink-850 px-3.5 text-ink-50 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
            <x-button variant="secondary">{{ __('categories.search_button') }}</x-button>
        </form>

        @if ($search !== '')
            <p class="mt-8 text-sm text-ink-300" aria-live="polite">{{ trans_choice('categories.results', $matches->count(), ['count' => $matches->count(), 'q' => $search]) }}</p>
            @if ($matches->isEmpty())
                <x-empty-state class="mt-6" :title="__('categories.no_results_title')">
                    {{ __('categories.no_results_text') }}
                    <x-slot:actions>
                        <x-button :href="localized_route('contact', ['topic' => 'sourcing'])">{{ __('ui.cta.start_request') }}</x-button>
                        <x-button :href="localized_route('categories.index')" variant="secondary">{{ __('categories.show_all') }}</x-button>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($matches as $match)
                        <x-category-card :category="$match" />
                    @endforeach
                </div>
            @endif
        @else
            <div class="mt-12 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($roots as $root)
                    <section aria-labelledby="cat-{{ $root->id }}">
                        <a href="{{ localized_route('categories.show', ['slug' => $root->slug()]) }}" class="group flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-ink-600 bg-ink-850 text-brand-400"><x-category-icon :icon="$root->icon" class="h-5 w-5" /></span>
                            <h2 id="cat-{{ $root->id }}" class="font-semibold text-ink-50 group-hover:text-brand-300">{{ $root->name() }}</h2>
                        </a>
                        @if ($root->children->isNotEmpty())
                            <ul class="mt-3 space-y-1.5 border-l border-ink-700 pl-[1.6rem] text-sm">
                                @foreach ($root->children as $child)
                                    <li><a href="{{ localized_route('categories.show', ['slug' => $child->slug()]) }}" class="text-ink-300 hover:text-ink-50">{{ $child->name() }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </x-container>
</x-layouts.app>
