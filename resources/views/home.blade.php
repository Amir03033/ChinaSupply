<x-layouts.app :description="__('home.meta_description')">
    {{-- Hero: one sentence on what, one on for whom, two routes. --}}
    <section class="grid-glow border-b border-ink-800">
        <x-container class="py-16 sm:py-24">
            <div class="max-w-3xl">
                <p class="inline-flex items-center gap-2 rounded-full border border-ink-600 bg-ink-850 px-3 py-1 text-xs text-ink-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-400" aria-hidden="true"></span>{{ __('home.hero.eyebrow') }}
                </p>
                <h1 class="mt-6 text-4xl font-semibold leading-[1.1] tracking-tight text-ink-50 sm:text-6xl">{{ __('home.hero.title') }}</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-ink-300">{{ __('home.hero.lead') }}</p>
            </div>

            <div class="mt-12 grid gap-4 md:grid-cols-2">
                <div class="surface flex flex-col p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-ink-800 text-brand-400"><x-heroicon-o-magnifying-glass class="h-5 w-5" aria-hidden="true" /></span>
                        <h2 class="text-lg font-semibold text-ink-50">{{ __('home.routes.self.title') }}</h2>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-ink-300">{{ __('home.routes.self.text') }}</p>
                    <form method="GET" action="{{ localized_route('suppliers.index') }}" class="mt-6 flex flex-col gap-3 sm:flex-row" role="search">
                        <label for="home-q" class="sr-only">{{ __('home.routes.self.label') }}</label>
                        <input id="home-q" name="q" type="search" maxlength="80" placeholder="{{ __('home.routes.self.placeholder') }}"
                               class="h-11 flex-1 rounded-lg border border-ink-600 bg-ink-900 px-3.5 text-ink-50 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <x-button variant="secondary">{{ __('home.routes.self.button') }}</x-button>
                    </form>
                </div>

                <div class="surface relative flex flex-col overflow-hidden border-brand-500/40 p-6 sm:p-8">
                    <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-brand-600/20 blur-3xl" aria-hidden="true"></div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600/20 text-brand-300"><x-heroicon-o-user-group class="h-5 w-5" aria-hidden="true" /></span>
                        <h2 class="text-lg font-semibold text-ink-50">{{ __('home.routes.managed.title') }}</h2>
                    </div>
                    <ul class="mt-4 space-y-2.5 text-sm text-ink-200">
                        @foreach (__('home.routes.managed.points') as $point)
                            <li class="flex gap-2.5"><x-heroicon-m-check class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" aria-hidden="true" />{{ $point }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-6">
                        <x-button :href="localized_route('sourcing.create')" icon="heroicon-m-arrow-right">{{ __('home.routes.managed.button') }}</x-button>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    {{-- Trust strip: statements, never invented numbers. --}}
    <section class="border-b border-ink-800 bg-ink-950/60">
        <x-container class="grid gap-6 py-8 text-sm text-ink-300 sm:grid-cols-3">
            @foreach (__('home.trust') as $item)
                <p class="flex items-center gap-3"><x-heroicon-o-check-badge class="h-5 w-5 shrink-0 text-ink-400" aria-hidden="true" />{{ $item }}</p>
            @endforeach
        </x-container>
    </section>

    <section>
        <x-container class="py-20">
            <x-section-header :eyebrow="__('home.steps.eyebrow')" :title="__('home.steps.title')" />
            <ol class="mt-10 grid gap-4 md:grid-cols-3">
                @foreach (__('home.steps.items') as $i => $step)
                    <li class="surface p-6">
                        <span class="font-mono text-sm text-brand-400">0{{ $i + 1 }}</span>
                        <h3 class="mt-3 font-semibold text-ink-50">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-ink-300">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
            <div class="mt-8"><a href="{{ localized_route('how-it-works') }}" class="text-sm font-medium text-brand-400 hover:text-brand-300">{{ __('home.steps.more') }} →</a></div>
        </x-container>
    </section>

    @if ($featuredCategories->isNotEmpty())
        <section class="border-y border-ink-800 bg-ink-950/40">
            <x-container class="py-20">
                <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                    <x-section-header :eyebrow="__('home.categories.eyebrow')" :title="__('home.categories.title')" />
                    <x-button :href="localized_route('categories.index')" variant="secondary" size="sm">{{ __('home.categories.all') }}</x-button>
                </div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($featuredCategories as $category)
                        <x-category-card :category="$category" :count="$category->children_count" />
                    @endforeach
                </div>
            </x-container>
        </section>
    @endif

    <section>
        <x-container class="grid items-center gap-12 py-20 lg:grid-cols-2">
            <x-section-header :eyebrow="__('home.verification.eyebrow')" :title="__('home.verification.title')" :lead="__('home.verification.lead')" />
            <div class="surface divide-y divide-ink-700">
                @foreach (__('verification.levels') as $level)
                    <div class="flex items-start gap-4 p-5">
                        <x-verification-level-badge :level="$level['level']" class="mt-0.5" />
                        <p class="text-sm leading-6 text-ink-300">{{ $level['short'] }}</p>
                    </div>
                @endforeach
                <div class="p-5"><a href="{{ localized_route('verification') }}" class="text-sm font-medium text-brand-400 hover:text-brand-300">{{ __('home.verification.more') }} →</a></div>
            </div>
        </x-container>
    </section>

    <section>
        <x-container class="pb-4">
            <div class="surface grid-glow flex flex-col items-start justify-between gap-6 p-8 sm:p-12 md:flex-row md:items-center">
                <div class="max-w-xl">
                    <h2 class="text-2xl font-semibold text-ink-50">{{ __('home.cta.title') }}</h2>
                    <p class="mt-3 text-ink-300">{{ __('home.cta.text') }}</p>
                </div>
                <x-button :href="localized_route('sourcing.create')" size="lg" icon="heroicon-m-arrow-right">{{ __('ui.cta.start_request') }}</x-button>
            </div>
        </x-container>
    </section>
</x-layouts.app>
