<x-layouts.app :title="__('pages.suppliers.title')" :description="__('pages.suppliers.meta')">
    <x-container class="py-14">
        <x-section-header as="h1" :title="__('pages.suppliers.title')" :lead="__('pages.suppliers.lead')" />
        <div class="mt-12 grid gap-4 md:grid-cols-3">
            @foreach (__('pages.suppliers.benefits') as $b)
                <div class="surface p-6">
                    <h2 class="font-semibold text-ink-50">{{ $b['title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-300">{{ $b['text'] }}</p>
                </div>
            @endforeach
        </div>
        <section class="mt-14 max-w-3xl" aria-labelledby="sup-process">
            <h2 id="sup-process" class="text-xl font-semibold text-ink-50">{{ __('pages.suppliers.process_title') }}</h2>
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-ink-300">
                @foreach (__('pages.suppliers.process') as $step)
                    <li class="leading-7">{{ $step }}</li>
                @endforeach
            </ol>
        </section>
        <x-alert class="mt-10 max-w-3xl">{{ __('pages.suppliers.registration_notice') }}</x-alert>
        <div class="mt-6"><x-button :href="localized_route('contact', ['topic' => 'supplier'])" size="lg">{{ __('pages.suppliers.cta') }}</x-button></div>
    </x-container>
</x-layouts.app>
