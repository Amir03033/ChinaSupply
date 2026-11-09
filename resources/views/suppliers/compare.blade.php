@php
    $rows = [
        'type' => fn ($s) => e($s->company_type->getLabel()),
        'location' => fn ($s) => e(collect([$s->city, $s->province])->filter()->implode(', ') ?: '—'),
        'moq' => fn ($s) => $s->moq_min ? e(number_format($s->moq_min, 0, ',', '.').' '.($s->moq_unit ?? __('suppliers.units'))) : '—',
        'lead_time' => fn ($s) => $s->lead_time_min_days ? e(__('suppliers.days', ['range' => $s->lead_time_min_days.($s->lead_time_max_days && $s->lead_time_max_days != $s->lead_time_min_days ? '–'.$s->lead_time_max_days : '')])) : '—',
        'capacity' => fn ($s) => e($s->production_capacity ?: '—'),
        'custom' => fn ($s) => e(collect(['OEM' => $s->offers_oem, 'ODM' => $s->offers_odm, __('portal.fields.private_label') => $s->offers_private_label])->filter()->keys()->implode(', ') ?: '—'),
        'markets' => fn ($s) => e(collect($s->export_markets ?? [])->map(fn ($m) => __('suppliers.markets.'.$m))->implode(', ') ?: '—'),
        'certs' => fn ($s) => e($s->certifications->filter(fn ($c) => $c->isVerifiedAndValid())->pluck('name')->implode(', ') ?: '—'),
        'checked' => fn ($s) => e($s->verificationChecks->filter(fn ($c) => $c->isValidAt())->count().' / '.count(\App\Enums\VerificationCheckType::cases())),
    ];
@endphp
<x-layouts.app :title="__('directory.compare_title')" noindex>
    <x-container class="py-12">
        <x-breadcrumbs :items="[[__('ui.nav.home'), localized_route('home')], [__('directory.title'), localized_route('suppliers.index')], [__('directory.compare_title'), null]]" />
        <h1 class="mt-6 text-3xl font-semibold text-ink-50">{{ __('directory.compare_title') }}</h1>

        @if ($suppliers->isEmpty())
            <x-empty-state class="mt-8" icon="heroicon-o-scale" :title="__('directory.compare_empty_title')">
                {{ __('directory.compare_empty_text') }}
                <x-slot:actions><x-button :href="localized_route('suppliers.index')">{{ __('directory.title') }}</x-button></x-slot:actions>
            </x-empty-state>
        @else
            <div class="mt-8 overflow-x-auto rounded-[var(--radius-card)] border border-ink-700">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <caption class="sr-only">{{ __('directory.compare_title') }}</caption>
                    <thead class="bg-ink-850">
                        <tr>
                            <th scope="col" class="w-44 px-4 py-3 text-ink-400"><span class="sr-only">{{ __('directory.attribute') }}</span></th>
                            @foreach ($suppliers as $s)
                                <th scope="col" class="px-4 py-3 align-top">
                                    <a href="{{ localized_route('suppliers.show', ['slug' => $s->slug]) }}" class="font-semibold text-ink-50 hover:text-brand-300">{{ $s->name }}</a>
                                    <form method="POST" action="{{ route('compare.toggle', $s) }}" class="mt-1">@csrf<button class="text-xs font-normal text-ink-400 hover:text-danger-400">{{ __('directory.remove') }}</button></form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        <tr>
                            <th scope="row" class="px-4 py-3 font-normal text-ink-400">{{ __('directory.rows.level') }}</th>
                            @foreach ($suppliers as $s)<td class="px-4 py-3"><x-verification-level-badge :level="$s->verification_level" /></td>@endforeach
                        </tr>
                        @foreach ($rows as $key => $render)
                            <tr>
                                <th scope="row" class="px-4 py-3 font-normal text-ink-400">{{ __('directory.rows.'.$key) }}</th>
                                @foreach ($suppliers as $s)<td class="px-4 py-3 text-ink-100">{!! $render($s) !!}</td>@endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <th scope="row" class="px-4 py-3"></th>
                            @foreach ($suppliers as $s)
                                <td class="px-4 py-3"><x-button size="sm" :href="localized_route('sourcing.create', ['supplier' => $s->slug])">{{ __('suppliers.request_intro') }}</x-button></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-ink-400">{{ __('directory.compare_note') }}</p>
        @endif
    </x-container>
</x-layouts.app>
