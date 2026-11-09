@php
    $selectClass = 'block h-10 w-full rounded-lg border border-ink-600 bg-ink-850 px-3 text-sm text-ink-50 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
    $activeCount = collect($filters)->except(['q', 'sort'])->filter()->count();
@endphp
<x-layouts.app :title="__('directory.title')" :description="__('directory.meta')" :noindex="$hasFilters || $suppliers->currentPage() > 1">
    <x-container class="py-12">
        <x-section-header as="h1" :title="__('directory.title')" :lead="__('directory.lead')" />

        @if (session('directory_status'))<x-alert type="warning" class="mt-6">{{ session('directory_status') }}</x-alert>@endif

        <form method="GET" action="{{ localized_route('suppliers.index') }}" class="mt-8" role="search" x-data="{ more: {{ ($filters['cert'] || $filters['province'] || $filters['level']) ? 'true' : 'false' }} }">
            <div class="flex flex-col gap-3 sm:flex-row">
                <label for="dir-q" class="sr-only">{{ __('directory.search_label') }}</label>
                <input id="dir-q" type="search" name="q" value="{{ $filters['q'] }}" maxlength="80" placeholder="{{ __('directory.search_placeholder') }}"
                       class="h-11 flex-1 rounded-lg border border-ink-600 bg-ink-850 px-3.5 text-ink-50 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                <x-button>{{ __('directory.search') }}</x-button>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="dir-cat" class="mb-1 block text-xs text-ink-400">{{ __('directory.f.category') }}</label>
                    <select id="dir-cat" name="category" class="{{ $selectClass }}">
                        <option value="">{{ __('directory.all') }}</option>
                        @foreach ($categories as $c)<option value="{{ $c->slug() }}" @selected($filters['category'] === $c->slug())>{{ $c->name() }}</option>@endforeach
                        @if ($filters['category'] && ! $categories->contains(fn ($c) => $c->slug() === $filters['category']) && ($sel = \App\Support\SupplierDirectory::category($filters['category'])))
                            <option value="{{ $filters['category'] }}" selected>{{ $sel->name() }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label for="dir-type" class="mb-1 block text-xs text-ink-400">{{ __('directory.f.type') }}</label>
                    <select id="dir-type" name="type" class="{{ $selectClass }}">
                        <option value="">{{ __('directory.all') }}</option>
                        @foreach (['manufacturer', 'trading_company', 'hybrid'] as $t)<option value="{{ $t }}" @selected($filters['type'] === $t)>{{ __('enums.company_type.'.$t) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="dir-moq" class="mb-1 block text-xs text-ink-400">{{ __('directory.f.moq') }}</label>
                    <select id="dir-moq" name="moq_max" class="{{ $selectClass }}">
                        <option value="">{{ __('directory.any') }}</option>
                        @foreach (\App\Support\SupplierDirectory::MOQ_STEPS as $m)<option value="{{ $m }}" @selected($filters['moq_max'] === $m)>{{ __('directory.moq_max', ['qty' => number_format($m, 0, ',', '.')]) }}</option>@endforeach
                    </select>
                </div>
                <fieldset class="flex items-end gap-4 pb-2">
                    <legend class="mb-1 text-xs text-ink-400">{{ __('directory.f.custom') }}</legend>
                    <label class="flex items-center gap-2 text-sm text-ink-200"><input type="checkbox" name="oem" value="1" @checked($filters['oem']) class="h-4 w-4 rounded border-ink-500 bg-ink-850 text-brand-600">OEM</label>
                    <label class="flex items-center gap-2 text-sm text-ink-200"><input type="checkbox" name="odm" value="1" @checked($filters['odm']) class="h-4 w-4 rounded border-ink-500 bg-ink-850 text-brand-600">ODM</label>
                </fieldset>
                <div class="flex items-end">
                    <button type="button" @click="more = !more" :aria-expanded="more.toString()" aria-controls="more-filters" class="h-10 w-full rounded-lg border border-ink-600 px-3 text-sm text-ink-200 hover:text-ink-50">
                        {{ __('directory.more_filters') }}
                    </button>
                </div>
            </div>

            <div id="more-filters" x-show="more" x-cloak class="mt-3 grid gap-3 sm:grid-cols-3">
                <div>
                    <label for="dir-level" class="mb-1 block text-xs text-ink-400">{{ __('directory.f.level') }}</label>
                    <select id="dir-level" name="level" class="{{ $selectClass }}">
                        <option value="">{{ __('directory.any') }}</option>
                        @foreach ([1, 2, 3] as $l)<option value="{{ $l }}" @selected($filters['level'] === $l)>{{ __('directory.level_min', ['name' => __('verification.level_names.'.$l)]) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="dir-prov" class="mb-1 block text-xs text-ink-400">{{ __('directory.f.province') }}</label>
                    <select id="dir-prov" name="province" class="{{ $selectClass }}">
                        <option value="">{{ __('directory.all') }}</option>
                        @foreach ($provinces as $p)<option value="{{ $p }}" @selected($filters['province'] === $p)>{{ $p }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="dir-cert" class="mb-1 block text-xs text-ink-400">{{ __('directory.f.cert') }}</label>
                    <select id="dir-cert" name="cert" class="{{ $selectClass }}">
                        <option value="">{{ __('directory.any') }}</option>
                        @foreach ($certifications as $c)<option value="{{ $c->code }}" @selected($filters['cert'] === $c->code)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
        </form>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-ink-300" aria-live="polite">{{ trans_choice('directory.results', $suppliers->total(), ['count' => $suppliers->total()]) }}
                @if ($hasFilters)<a href="{{ localized_route('suppliers.index') }}" class="ml-2 text-brand-400 hover:text-brand-300">{{ __('directory.reset') }}</a>@endif
            </p>
            <div class="flex items-center gap-3">
                @if (count($compareIds) > 0)
                    <x-button :href="localized_route('suppliers.compare')" size="sm" variant="secondary">{{ __('directory.compare_n', ['count' => count($compareIds)]) }}</x-button>
                @endif
                <form method="GET" action="{{ localized_route('suppliers.index') }}" class="flex items-center gap-2">
                    @foreach (collect($filters)->except('sort')->filter() as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ is_bool($v) ? 1 : $v }}">@endforeach
                    <label for="dir-sort" class="text-xs text-ink-400">{{ __('directory.sort') }}</label>
                    <select id="dir-sort" name="sort" onchange="this.form.submit()" class="h-9 rounded-lg border border-ink-600 bg-ink-850 px-2 text-sm text-ink-50">
                        @foreach (['relevance', 'level', 'newest'] as $s)<option value="{{ $s }}" @selected($filters['sort'] === $s)>{{ __('directory.sorts.'.$s) }}</option>@endforeach
                    </select>
                    <noscript><x-button size="sm" variant="secondary">OK</x-button></noscript>
                </form>
            </div>
        </div>

        @if ($suppliers->isEmpty())
            <x-empty-state class="mt-6" icon="heroicon-o-building-office-2" :title="__('directory.empty_title')">
                {{ __('directory.empty_text') }}
                <x-slot:actions>
                    <x-button :href="localized_route('sourcing.create')">{{ __('ui.cta.start_request') }}</x-button>
                    @if ($hasFilters)<x-button :href="localized_route('suppliers.index')" variant="secondary">{{ __('directory.reset') }}</x-button>@endif
                </x-slot:actions>
            </x-empty-state>
        @else
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($suppliers as $supplier)
                    <li class="flex flex-col gap-2">
                        <x-supplier-card :supplier="$supplier" />
                        <x-supplier-actions :supplier="$supplier" :compare-ids="$compareIds" :favorite-ids="$favoriteIds" />
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $suppliers->links() }}</div>
        @endif

        <p class="mt-10 text-xs text-ink-400">{{ __('directory.ranking_note') }}</p>
    </x-container>
</x-layouts.app>
