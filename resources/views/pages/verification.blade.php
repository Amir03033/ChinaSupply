@php($disclaimer = \App\Models\Page::where('key', 'verification_disclaimer')->active()->with('translations')->first())
<x-layouts.app :title="__('pages.verification.title')" :description="__('pages.verification.meta')">
    <x-container class="py-14">
        <x-section-header as="h1" :title="__('pages.verification.title')" :lead="__('pages.verification.lead')" />

        <section class="mt-12" aria-labelledby="levels">
            <h2 id="levels" class="text-xl font-semibold text-ink-50">{{ __('pages.verification.levels_title') }}</h2>
            <div class="mt-6 overflow-hidden rounded-[var(--radius-card)] border border-ink-700">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ __('pages.verification.levels_title') }}</caption>
                    <thead class="bg-ink-850 text-ink-300">
                        <tr><th scope="col" class="px-5 py-3 font-medium">{{ __('pages.verification.col_level') }}</th><th scope="col" class="px-5 py-3 font-medium">{{ __('pages.verification.col_checked') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach (__('verification.levels') as $level)
                            <tr class="align-top">
                                <td class="px-5 py-4"><x-verification-level-badge :level="$level['level']" /></td>
                                <td class="px-5 py-4 leading-6 text-ink-300">{{ $level['checked'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-12 grid gap-6 md:grid-cols-2">
            @foreach (['means', 'not_means'] as $block)
                <section class="surface p-6" aria-labelledby="v-{{ $block }}">
                    <h2 id="v-{{ $block }}" class="font-semibold text-ink-50">{{ __("pages.verification.$block.title") }}</h2>
                    <ul class="mt-4 space-y-2.5 text-sm text-ink-300">
                        @foreach (__("pages.verification.$block.points") as $p)
                            <li class="flex gap-2.5">
                                @if ($block === 'means')<x-heroicon-m-check class="mt-0.5 h-4 w-4 shrink-0 text-verified-400" aria-hidden="true" />@else<x-heroicon-m-x-mark class="mt-0.5 h-4 w-4 shrink-0 text-ink-400" aria-hidden="true" />@endif
                                <span class="leading-6">{{ $p }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        <section class="mt-12 max-w-3xl" aria-labelledby="independence">
            <h2 id="independence" class="text-xl font-semibold text-ink-50">{{ __('pages.verification.independence.title') }}</h2>
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-ink-300">
                @foreach (__('pages.verification.independence.rules') as $rule)
                    <li class="leading-7">{{ $rule }}</li>
                @endforeach
            </ol>
            <p class="mt-6 text-sm text-ink-400">
                {{ __('pages.verification.expiry') }}
                @if ($disclaimer && ($t = $disclaimer->translation()))
                    <a href="{{ localized_route('pages.show', ['slug' => $t->slug]) }}" class="text-brand-400 hover:text-brand-300">{{ $t->title }}</a>.
                @endif
            </p>
        </section>
    </x-container>
</x-layouts.app>
