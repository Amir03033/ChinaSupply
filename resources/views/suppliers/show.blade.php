@php
    $description = $supplier->description() ?? $supplier->description('en');
    $capabilities = $supplier->translated('capabilities') ?? $supplier->translated('capabilities', 'en');
    $markets = collect($supplier->export_markets ?? [])->map(fn ($m) => __('suppliers.markets.'.$m))->implode(', ');
    $languages = collect($supplier->languages ?? [])->map(fn ($l) => __('suppliers.languages.'.$l))->implode(', ');
    $primary = $supplier->categories->first();
@endphp
<x-layouts.app :title="$supplier->name" :description="\Illuminate\Support\Str::limit(strip_tags((string) $description) ?: __('suppliers.meta', ['name' => $supplier->name]), 155)" :alternates="$alternates">
    @if ($supplier->coverUrl())
        <div class="h-40 w-full bg-ink-850 sm:h-56"><img src="{{ $supplier->coverUrl() }}" alt="" class="h-full w-full object-cover opacity-80"></div>
    @endif
    <x-container class="py-10">
        <x-breadcrumbs :items="array_values(array_filter([[__('ui.nav.home'), localized_route('home')], [__('categories.title'), localized_route('categories.index')], $primary ? [$primary->name(), localized_route('categories.show', ['slug' => $primary->slug()])] : null, [$supplier->name, null]]))" />

        <header class="mt-8 flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
            <div class="flex items-start gap-5">
                @if ($supplier->logoUrl())
                    <img src="{{ $supplier->logoUrl() }}" alt="{{ $supplier->name }}" width="72" height="72" class="h-18 w-18 rounded-xl border border-ink-700 bg-white object-contain">
                @endif
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-ink-50">{{ $supplier->name }}</h1>
                    @if ($supplier->name_local)<p class="mt-1 text-ink-300" lang="zh">{{ $supplier->name_local }}</p>@endif
                    <p class="mt-2 text-sm text-ink-400">{{ collect([$supplier->city, $supplier->province, \App\Support\SupplierOptions::countries()[$supplier->country] ?? $supplier->country])->filter()->implode(', ') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="#verification"><x-verification-level-badge :level="$supplier->verification_level" /></a>
                        <span class="inline-flex items-center rounded-full border border-ink-600 px-2.5 py-0.5 text-xs text-ink-300">{{ $supplier->company_type->getLabel() }}</span>
                        @if ($supplier->verification_review_required)<span class="inline-flex items-center rounded-full border border-warning-400/60 px-2.5 py-0.5 text-xs text-warning-400">{{ __('suppliers.review_required') }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-2 md:items-end">
                <x-button :href="localized_route('sourcing.create', ['supplier' => $supplier->slug])" size="lg">{{ __('suppliers.request_intro') }}</x-button>
                <p class="max-w-xs text-xs text-ink-400 md:text-right">{{ __('suppliers.request_intro_hint') }}</p>
            </div>
        </header>

        <div class="mt-10 grid gap-8 lg:grid-cols-3">
            <div class="space-y-10 lg:col-span-2">
                @if ($description)
                    <section aria-labelledby="about"><h2 id="about" class="text-lg font-semibold text-ink-50">{{ __('suppliers.about') }}</h2>
                        <p class="mt-3 whitespace-pre-line leading-7 text-ink-200">{{ $description }}</p>
                        <p class="mt-2 text-xs text-ink-400">{{ __('suppliers.provided_by_supplier') }}</p>
                    </section>
                @endif

                <section aria-labelledby="facts"><h2 id="facts" class="text-lg font-semibold text-ink-50">{{ __('suppliers.facts') }}</h2>
                    <dl class="mt-4 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                        @foreach (array_filter([
                            __('suppliers.f.moq') => $supplier->moq_min ? number_format($supplier->moq_min, 0, ',', '.').' '.($supplier->moq_unit ?? __('suppliers.units')) : null,
                            __('suppliers.f.lead_time') => $supplier->lead_time_min_days ? __('suppliers.days', ['range' => $supplier->lead_time_min_days.($supplier->lead_time_max_days && $supplier->lead_time_max_days != $supplier->lead_time_min_days ? '–'.$supplier->lead_time_max_days : '')]) : null,
                            __('suppliers.f.capacity') => $supplier->production_capacity,
                            __('suppliers.f.services') => collect(['OEM' => $supplier->offers_oem, 'ODM' => $supplier->offers_odm, __('portal.fields.private_label') => $supplier->offers_private_label])->filter()->keys()->implode(', ') ?: null,
                            __('suppliers.f.founded') => $supplier->founded_year,
                            __('suppliers.f.employees') => $supplier->employee_range,
                            __('suppliers.f.markets') => $markets ?: null,
                            __('suppliers.f.languages') => $languages ?: null,
                        ]) as $label => $value)
                            <div class="border-b border-ink-800 pb-3"><dt class="text-ink-400">{{ $label }}</dt><dd class="mt-1 text-ink-100">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    <p class="mt-3 text-xs text-ink-400">{{ $capabilitiesVerified ? __('suppliers.capabilities_checked') : __('suppliers.provided_by_supplier') }}</p>
                </section>

                @if ($capabilities)
                    <section aria-labelledby="caps"><h2 id="caps" class="text-lg font-semibold text-ink-50">{{ __('suppliers.capabilities') }}</h2>
                        <p class="mt-3 whitespace-pre-line leading-7 text-ink-200">{{ $capabilities }}</p>
                    </section>
                @endif

                @if ($supplier->products->isNotEmpty())
                    <section aria-labelledby="products"><h2 id="products" class="text-lg font-semibold text-ink-50">{{ __('suppliers.products') }}</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach ($supplier->products as $p)
                                <article class="surface flex gap-4 p-4">
                                    @if ($p->imageUrl())<img src="{{ $p->imageUrl() }}" alt="{{ $p->name }}" loading="lazy" class="h-20 w-20 shrink-0 rounded-lg object-cover">@endif
                                    <div class="min-w-0 text-sm">
                                        <h3 class="font-medium text-ink-50">{{ $p->name }}</h3>
                                        @if ($p->description)<p class="mt-1 line-clamp-2 text-ink-300">{{ $p->description }}</p>@endif
                                        <p class="mt-2 text-ink-400">
                                            @if ($price = \App\Support\Money::range($p->price_min_cents, $p->price_max_cents, $p->currency)){{ $price }} · @endif
                                            @if ($p->moq)MOQ {{ number_format($p->moq, 0, ',', '.') }}@endif
                                        </p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-ink-400">{{ __('suppliers.prices_indicative') }}</p>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                <section id="verification" class="surface p-5" aria-labelledby="ver-title">
                    <h2 id="ver-title" class="font-semibold text-ink-50">{{ __('suppliers.verification_title') }}</h2>
                    <p class="mt-1 text-xs text-ink-400">@if ($supplier->last_verified_at){{ __('suppliers.last_checked', ['date' => $supplier->last_verified_at->translatedFormat('j F Y')]) }}@endif</p>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ($checks as $c)
                            <li class="flex gap-3">
                                @if ($c['state'] === 'passed')
                                    <x-heroicon-m-check-circle class="mt-0.5 h-5 w-5 shrink-0 text-verified-400" aria-hidden="true" />
                                @elseif ($c['state'] === 'expired')
                                    <x-heroicon-m-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0 text-warning-400" aria-hidden="true" />
                                @else
                                    <x-heroicon-m-minus-circle class="mt-0.5 h-5 w-5 shrink-0 text-ink-500" aria-hidden="true" />
                                @endif
                                <div>
                                    <p class="text-ink-100">{{ $c['type']->getLabel() }} <span class="sr-only">— {{ __('suppliers.check_state.'.$c['state']) }}</span></p>
                                    @if ($c['state'] === 'passed')
                                        <p class="text-xs text-ink-400">{{ $c['checked_at']?->translatedFormat('j M Y') }}@if ($c['method']) · {{ $c['method'] }}@endif</p>
                                    @else
                                        <p class="text-xs text-ink-400">{{ __('suppliers.check_state.'.$c['state']) }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 border-t border-ink-700 pt-4 text-xs leading-5 text-ink-400">{{ __('suppliers.verification_disclaimer') }} <a href="{{ localized_route('verification') }}" class="text-brand-400">{{ __('suppliers.verification_more') }}</a></p>
                </section>

                @if ($supplier->certifications->isNotEmpty())
                    <section class="surface p-5" aria-labelledby="certs">
                        <h2 id="certs" class="font-semibold text-ink-50">{{ __('suppliers.certifications') }}</h2>
                        <ul class="mt-3 space-y-2.5 text-sm">
                            @foreach ($supplier->certifications->where('status', '!=', 'rejected') as $cert)
                                <li>
                                    <p class="text-ink-100">{{ $cert->name }}</p>
                                    <p class="text-xs {{ $cert->isVerifiedAndValid() ? 'text-verified-400' : 'text-ink-400' }}">
                                        {{ $cert->isVerifiedAndValid() ? __('suppliers.cert_verified') : ($cert->valid_until?->isPast() ? __('suppliers.cert_expired') : __('suppliers.cert_claimed')) }}@if ($cert->valid_until) · {{ __('suppliers.valid_until', ['date' => $cert->valid_until->format('m-Y')]) }}@endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($supplier->categories->isNotEmpty())
                    <section class="surface p-5" aria-labelledby="cats">
                        <h2 id="cats" class="font-semibold text-ink-50">{{ __('categories.title') }}</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($supplier->categories as $cat)
                                <a href="{{ localized_route('categories.show', ['slug' => $cat->slug()]) }}" class="rounded-full border border-ink-600 px-3 py-1 text-xs text-ink-200 hover:border-brand-500">{{ $cat->name() }}</a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($supplier->show_contact_publicly && ($supplier->website || $supplier->business_email))
                    <section class="surface p-5 text-sm" aria-labelledby="contact-h">
                        <h2 id="contact-h" class="font-semibold text-ink-50">{{ __('suppliers.contact') }}</h2>
                        @if ($supplier->website)<p class="mt-2"><a href="{{ $supplier->website }}" rel="nofollow noopener" target="_blank" class="text-brand-400">{{ parse_url($supplier->website, PHP_URL_HOST) }}</a></p>@endif
                        @if ($supplier->business_email)<p class="mt-1 text-ink-200">{{ $supplier->business_email }}</p>@endif
                    </section>
                @endif
            </aside>
        </div>
    </x-container>
</x-layouts.app>
