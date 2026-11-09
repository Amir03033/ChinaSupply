@props(['alternates' => []])
@php
    $nav = [
        ['route' => 'categories.index', 'label' => __('ui.nav.categories')],
        ['route' => 'how-it-works', 'label' => __('ui.nav.how_it_works')],
        ['route' => 'verification', 'label' => __('ui.nav.verification')],
        ['route' => 'for-suppliers', 'label' => __('ui.nav.for_suppliers')],
    ];
@endphp
<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-ink-700/70 bg-ink-900/85 backdrop-blur">
    <x-container class="flex h-16 items-center justify-between gap-6">
        <a href="{{ localized_route('home') }}" class="flex items-center gap-2.5 font-semibold tracking-tight text-ink-50" aria-label="{{ config('chinasupply.company.name') }} — {{ __('ui.nav.home') }}">
            <x-logo class="h-7 w-7" />
            <span class="text-[1.05rem]">China<span class="text-brand-400">Supply</span></span>
        </a>

        <nav class="hidden items-center gap-1 xl:flex" aria-label="{{ __('ui.nav.main') }}">
            @foreach ($nav as $item)
                @php($active = request()->routeIs(app()->getLocale().'.'.$item['route'].'*'))
                <a href="{{ localized_route($item['route']) }}"
                   @class(['whitespace-nowrap rounded-md px-3 py-2 text-sm transition', 'text-ink-50 bg-ink-800' => $active, 'text-ink-300 hover:text-ink-50' => ! $active])
                   @if ($active) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-3 xl:flex">
            <x-lang-switcher :alternates="$alternates" />
            @auth
                <a href="{{ auth()->user()->isStaff() ? url('/admin') : route('account') }}" class="text-sm text-ink-300 hover:text-ink-50">{{ __('ui.nav.account') }}</a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-ink-300 hover:text-ink-50">{{ __('ui.nav.login') }}</a>
            @endauth
            <x-button :href="localized_route('contact', ['topic' => 'sourcing'])" size="sm">{{ __('ui.cta.start_request') }}</x-button>
        </div>

        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-ink-200 hover:bg-ink-800 xl:hidden"
                @click="open = !open" :aria-expanded="open.toString()" aria-controls="mobile-nav" aria-label="{{ __('ui.nav.toggle') }}">
            <x-heroicon-o-bars-3 class="h-6 w-6" x-show="!open" />
            <x-heroicon-o-x-mark class="h-6 w-6" x-show="open" x-cloak />
        </button>
    </x-container>

    <div id="mobile-nav" x-show="open" x-cloak x-transition.opacity class="border-t border-ink-700 bg-ink-900 xl:hidden">
        <x-container class="flex flex-col gap-1 py-4">
            @foreach ($nav as $item)
                <a href="{{ localized_route($item['route']) }}" class="rounded-md px-3 py-3 text-ink-100 hover:bg-ink-800">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ auth()->check() ? route('account') : route('login') }}" class="rounded-md px-3 py-3 text-ink-300 hover:bg-ink-800">{{ auth()->check() ? __('ui.nav.account') : __('ui.nav.login') }}</a>
            <div class="mt-3 flex items-center justify-between gap-3 px-3">
                <x-lang-switcher :alternates="$alternates" />
                <x-button :href="localized_route('contact', ['topic' => 'sourcing'])" size="sm">{{ __('ui.cta.start_request') }}</x-button>
            </div>
        </x-container>
    </div>
</header>
