@php($company = config('chinasupply.company'))
<x-layouts.app :title="__('pages.about.title')" :description="__('pages.about.meta')">
    <x-container class="py-14">
        <x-section-header as="h1" :title="__('pages.about.title')" :lead="__('pages.about.lead')" />
        <div class="prose-cs mt-10 max-w-3xl">
            @foreach (__('pages.about.paragraphs') as $p)
                <p>{{ $p }}</p>
            @endforeach
        </div>
        <dl class="surface mt-10 grid max-w-3xl gap-4 p-6 text-sm sm:grid-cols-2">
            <div><dt class="text-ink-400">{{ __('pages.about.company') }}</dt><dd class="mt-1 text-ink-100">{{ $company['legal_name'] ?: $company['name'] }}</dd></div>
            @if ($company['kvk'])<div><dt class="text-ink-400">{{ __('ui.footer.kvk') }}</dt><dd class="mt-1 text-ink-100">{{ $company['kvk'] }}</dd></div>@endif
            @if ($company['vat'])<div><dt class="text-ink-400">{{ __('ui.footer.vat') }}</dt><dd class="mt-1 text-ink-100">{{ $company['vat'] }}</dd></div>@endif
            @if ($company['address'])<div><dt class="text-ink-400">{{ __('pages.about.address') }}</dt><dd class="mt-1 text-ink-100">{{ $company['address'] }}</dd></div>@endif
            <div><dt class="text-ink-400">{{ __('pages.about.email') }}</dt><dd class="mt-1"><a class="text-brand-400 hover:text-brand-300" href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a></dd></div>
        </dl>
    </x-container>
</x-layouts.app>
