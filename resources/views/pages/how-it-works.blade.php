<x-layouts.app :title="__('pages.how.title')" :description="__('pages.how.meta')">
    <x-container class="py-14">
        <x-section-header as="h1" :title="__('pages.how.title')" :lead="__('pages.how.lead')" />

        <div class="mt-14 grid gap-12 lg:grid-cols-2">
            @foreach (['self', 'managed'] as $route)
                <section class="surface p-6 sm:p-8" aria-labelledby="route-{{ $route }}">
                    <h2 id="route-{{ $route }}" class="text-xl font-semibold text-ink-50">{{ __("pages.how.$route.title") }}</h2>
                    <p class="mt-2 text-sm text-ink-300">{{ __("pages.how.$route.lead") }}</p>
                    <ol class="mt-6 space-y-5">
                        @foreach (__("pages.how.$route.steps") as $i => $step)
                            <li class="flex gap-4">
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-ink-600 font-mono text-xs text-brand-400">{{ $i + 1 }}</span>
                                <div>
                                    <h3 class="font-medium text-ink-50">{{ $step['title'] }}</h3>
                                    <p class="mt-1 text-sm leading-6 text-ink-300">{{ $step['text'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </div>

        <section class="mt-16 max-w-3xl" aria-labelledby="role">
            <h2 id="role" class="text-xl font-semibold text-ink-50">{{ __('pages.how.role.title') }}</h2>
            <ul class="mt-4 space-y-3 text-ink-300">
                @foreach (__('pages.how.role.points') as $point)
                    <li class="flex gap-3"><x-heroicon-m-minus class="mt-1 h-4 w-4 shrink-0 text-ink-400" aria-hidden="true" /><span class="leading-7">{{ $point }}</span></li>
                @endforeach
            </ul>
        </section>

        <section class="mt-16 max-w-3xl" aria-labelledby="faq">
            <h2 id="faq" class="text-xl font-semibold text-ink-50">{{ __('pages.how.faq_title') }}</h2>
            <div class="mt-4 divide-y divide-ink-700 border-y border-ink-700">
                @foreach (__('pages.how.faq') as $qa)
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-ink-50">
                            {{ $qa['q'] }}<x-heroicon-m-chevron-down class="h-5 w-5 shrink-0 text-ink-400 transition group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <p class="mt-3 text-sm leading-6 text-ink-300">{{ $qa['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <div class="mt-12"><x-button :href="localized_route('contact', ['topic' => 'sourcing'])" size="lg">{{ __('ui.cta.start_request') }}</x-button></div>
    </x-container>
</x-layouts.app>
