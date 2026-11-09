@props(['supplier'])
<a href="{{ localized_route('suppliers.show', ['slug' => $supplier->slug]) }}" class="group surface flex h-full flex-col gap-4 p-5 transition hover:border-brand-500/60 hover:bg-ink-800">
    <div class="flex items-start gap-4">
        @if ($supplier->logoUrl())
            <img src="{{ $supplier->logoUrl() }}" alt="" width="48" height="48" loading="lazy" class="h-12 w-12 rounded-lg border border-ink-700 bg-white object-contain">
        @else
            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-ink-600 bg-ink-800 text-ink-300"><x-heroicon-o-building-office-2 class="h-6 w-6" aria-hidden="true" /></span>
        @endif
        <div class="min-w-0">
            <h3 class="font-medium leading-snug text-ink-50 group-hover:text-brand-300">{{ $supplier->name }}</h3>
            <p class="mt-0.5 text-sm text-ink-400">{{ collect([$supplier->city, $supplier->province])->filter()->implode(', ') }}</p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <x-verification-level-badge :level="$supplier->verification_level" />
        <span class="inline-flex items-center rounded-full border border-ink-600 px-2.5 py-0.5 text-xs text-ink-300">{{ $supplier->company_type->getLabel() }}</span>
        @if ($supplier->offers_oem)<span class="inline-flex items-center rounded-full border border-ink-600 px-2.5 py-0.5 text-xs text-ink-300">OEM</span>@endif
        @if ($supplier->offers_odm)<span class="inline-flex items-center rounded-full border border-ink-600 px-2.5 py-0.5 text-xs text-ink-300">ODM</span>@endif
    </div>
    @if ($supplier->moq_min)
        <p class="mt-auto text-sm text-ink-300">{{ __('suppliers.moq_from', ['qty' => number_format($supplier->moq_min, 0, ',', '.'), 'unit' => $supplier->moq_unit ?? __('suppliers.units')]) }}</p>
    @endif
</a>
