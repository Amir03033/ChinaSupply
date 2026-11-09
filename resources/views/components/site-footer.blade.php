@php
    $company = config('chinasupply.company');
    $legalPages = \App\Models\PageTranslation::query()
        ->where('locale', app()->getLocale())
        ->whereHas('page', fn ($q) => $q->where('status', \App\Enums\PublicationStatus::Active)->where('show_in_footer', true))
        ->join('pages', 'pages.id', '=', 'page_translations.page_id')
        ->orderBy('pages.sort_order')
        ->get(['page_translations.title', 'page_translations.slug']);
@endphp
<footer class="mt-24 border-t border-ink-700 bg-ink-950">
    <x-container class="grid gap-10 py-14 md:grid-cols-4">
        <div class="md:col-span-1">
            <a href="{{ localized_route('home') }}" class="flex items-center gap-2.5 font-semibold text-ink-50">
                <x-logo class="h-6 w-6" /> China<span class="-ml-2.5 text-brand-400">Supply</span>
            </a>
            <p class="mt-4 text-sm leading-6 text-ink-300">{{ __('ui.footer.about') }}</p>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-ink-50">{{ __('ui.footer.platform') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-300">
                <li><a class="hover:text-ink-50" href="{{ localized_route('categories.index') }}">{{ __('ui.nav.categories') }}</a></li>
                <li><a class="hover:text-ink-50" href="{{ localized_route('how-it-works') }}">{{ __('ui.nav.how_it_works') }}</a></li>
                <li><a class="hover:text-ink-50" href="{{ localized_route('verification') }}">{{ __('ui.nav.verification') }}</a></li>
                <li><a class="hover:text-ink-50" href="{{ localized_route('for-suppliers') }}">{{ __('ui.nav.for_suppliers') }}</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-ink-50">{{ __('ui.footer.company') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-300">
                <li><a class="hover:text-ink-50" href="{{ localized_route('about') }}">{{ __('ui.nav.about') }}</a></li>
                <li><a class="hover:text-ink-50" href="{{ localized_route('contact') }}">{{ __('ui.nav.contact') }}</a></li>
                @if ($company['email'])
                    <li><a class="hover:text-ink-50" href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a></li>
                @endif
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-ink-50">{{ __('ui.footer.legal') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-300">
                @foreach ($legalPages as $legal)
                    <li><a class="hover:text-ink-50" href="{{ localized_route('pages.show', ['slug' => $legal->slug]) }}">{{ $legal->title }}</a></li>
                @endforeach
            </ul>
        </div>
    </x-container>
    <div class="border-t border-ink-800">
        <x-container class="flex flex-col gap-2 py-6 text-xs text-ink-400 md:flex-row md:items-center md:justify-between">
            <p>&copy; {{ now()->year }} {{ $company['legal_name'] ?: $company['name'] }}@if ($company['kvk']) · {{ __('ui.footer.kvk') }} {{ $company['kvk'] }}@endif @if ($company['vat']) · {{ __('ui.footer.vat') }} {{ $company['vat'] }}@endif</p>
            <p>{{ __('ui.footer.disclaimer') }}</p>
        </x-container>
    </div>
</footer>
