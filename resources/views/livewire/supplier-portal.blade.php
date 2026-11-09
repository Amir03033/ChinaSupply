@php
    $supplier = $this->supplier;
    $stepIndex = array_search($step, \App\Livewire\SupplierPortal::STEPS, true);
    $statusKey = match ($supplier->pipeline_status->value) {
        'published' => 'published', 'rejected' => 'rejected', 'verified', 'verification_pending' => 'review',
        default => $supplier->onboarding_submitted_at ? 'review' : 'draft',
    };
@endphp
<x-container class="py-10">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm text-ink-400">{{ __('portal.title') }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-50 sm:text-3xl">{{ $supplier->name }}</h1>
        </div>
        <div class="surface grid grid-cols-2 gap-x-8 gap-y-2 p-4 text-sm sm:grid-cols-3">
            <div><p class="text-ink-400">{{ __('portal.status.label') }}</p><p class="font-medium text-ink-50">{{ __('portal.status.'.$statusKey) }}</p></div>
            <div><p class="text-ink-400">{{ __('portal.status.verification') }}</p><x-verification-level-badge :level="$supplier->verification_level" class="mt-1" /></div>
            <div class="col-span-2 sm:col-span-1" aria-live="polite"><p class="text-ink-400">{{ __('portal.autosave') }}</p><p class="text-ink-200">{{ $savedAt ? __('portal.saved_at', ['time' => $savedAt]) : '—' }}</p></div>
        </div>
    </div>

    @if (session('portal_status'))<x-alert type="success" class="mt-6">{{ session('portal_status') }}</x-alert>@endif
    @if ($supplier->pipeline_status->value === 'rejected')<x-alert type="error" class="mt-6">{{ __('portal.status.rejected_text') }}</x-alert>@endif
    @if ($resetChecks)<x-alert type="warning" class="mt-6">{{ __('portal.checks_reset') }}</x-alert>@endif

    <div class="mt-8 grid gap-8 lg:grid-cols-[15rem_1fr]">
        <nav aria-label="{{ __('portal.steps_label') }}">
            <ol class="flex gap-2 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible">
                @foreach (\App\Livewire\SupplierPortal::STEPS as $i => $s)
                    <li class="shrink-0">
                        <button type="button" wire:click="goTo('{{ $s }}')" @if ($s === $step) aria-current="step" @endif
                            @class(['flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition', 'bg-ink-800 text-ink-50 ring-1 ring-brand-500/50' => $s === $step, 'text-ink-300 hover:bg-ink-850 hover:text-ink-50' => $s !== $step])>
                            <span @class(['inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border font-mono text-xs', 'border-brand-500 text-brand-300' => $s === $step, 'border-ink-600' => $s !== $step])>{{ $i + 1 }}</span>
                            <span class="whitespace-nowrap">{{ __('portal.steps.'.$s) }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </nav>

        <section class="surface p-6 sm:p-8" aria-labelledby="step-title">
            <h2 id="step-title" class="text-lg font-semibold text-ink-50">{{ __('portal.steps.'.$step) }}</h2>
            <p class="mt-1 text-sm text-ink-300">{{ __('portal.step_help.'.$step) }}</p>

            <div class="mt-6 space-y-5">
                @if ($step === 'company')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-portal.input model="form.name" :label="__('portal.fields.name')" required />
                        <x-portal.input model="form.name_local" :label="__('portal.fields.name_local')" lang="zh" />
                        <x-portal.input model="form.legal_name" :label="__('portal.fields.legal_name')" required />
                        <x-portal.input model="form.registration_number" :label="__('portal.fields.registration_number')" required :hint="__('portal.hints.registration_number')" />
                        <x-portal.select model="form.company_type" :label="__('portal.fields.company_type')" required :options="collect(\App\Enums\CompanyType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])->all()" />
                        <x-portal.input model="form.founded_year" type="number" :label="__('portal.fields.founded_year')" />
                        <x-portal.select model="form.employee_range" :label="__('portal.fields.employee_range')" :placeholder="'—'" :options="\App\Support\SupplierOptions::employeeRanges()" />
                        <x-portal.input model="form.website" type="url" :label="__('portal.fields.website')" placeholder="https://" />
                    </div>
                    <div class="space-y-1.5">
                        <label for="p-description" class="block text-sm font-medium text-ink-100">{{ __('portal.fields.description') }}<span class="text-brand-400"> *</span></label>
                        <textarea id="p-description" rows="5" wire:model.live.blur="description" class="block w-full rounded-lg border border-ink-600 bg-ink-850 px-3.5 py-3 text-ink-50 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                        <p class="text-xs text-ink-400">{{ __('portal.hints.description') }}</p>
                        @error('description')<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
                    </div>
                @elseif ($step === 'location')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-portal.select model="form.country" :label="__('portal.fields.country')" required :options="\App\Support\SupplierOptions::countries()" />
                        <x-portal.input model="form.province" :label="__('portal.fields.province')" required />
                        <x-portal.input model="form.city" :label="__('portal.fields.city')" required />
                        <x-portal.input model="form.business_phone" type="tel" :label="__('portal.fields.business_phone')" />
                        <x-portal.input model="form.business_email" type="email" :label="__('portal.fields.business_email')" required />
                    </div>
                    <div class="space-y-1.5">
                        <label for="p-address" class="block text-sm font-medium text-ink-100">{{ __('portal.fields.address') }}<span class="text-brand-400"> *</span></label>
                        <textarea id="p-address" rows="2" wire:model.live.blur="form.address" class="block w-full rounded-lg border border-ink-600 bg-ink-850 px-3.5 py-3 text-ink-50 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                        @error('form.address')<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
                    </div>
                    <x-alert>{{ __('portal.hints.private_contact') }}</x-alert>
                @elseif ($step === 'categories')
                    <p class="text-sm text-ink-300">{{ __('portal.categories.selected', ['count' => count($categoryIds), 'max' => \App\Livewire\SupplierPortal::MAX_CATEGORIES]) }}</p>
                    @error('categoryIds')<x-alert type="error">{{ $message }}</x-alert>@enderror
                    <div class="space-y-3">
                        @foreach ($this->categoryTree as $root)
                            <details class="rounded-lg border border-ink-700" @if ($root->children->pluck('id')->intersect($categoryIds)->isNotEmpty() || in_array($root->id, $categoryIds)) open @endif>
                                <summary class="flex cursor-pointer items-center gap-3 px-4 py-3 text-sm font-medium text-ink-50">
                                    <x-category-icon :icon="$root->icon" class="h-5 w-5 text-brand-400" />{{ $root->name() }}
                                </summary>
                                <div class="grid gap-2 border-t border-ink-700 px-4 py-3 sm:grid-cols-2">
                                    @foreach ($root->children as $child)
                                        <label class="flex items-center gap-2.5 text-sm text-ink-200">
                                            <input type="checkbox" wire:click="toggleCategory({{ $child->id }})" @checked(in_array($child->id, $categoryIds)) class="h-4 w-4 rounded border-ink-500 bg-ink-850 text-brand-600">
                                            {{ $child->name() }}
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                @elseif ($step === 'production')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-portal.input model="form.moq_min" type="number" :label="__('portal.fields.moq_min')" :hint="__('portal.hints.moq')" />
                        <x-portal.input model="form.moq_unit" :label="__('portal.fields.moq_unit')" :placeholder="__('portal.hints.moq_unit')" />
                        <x-portal.input model="form.production_capacity" :label="__('portal.fields.production_capacity')" :placeholder="__('portal.hints.capacity')" />
                        <div class="grid grid-cols-2 gap-3">
                            <x-portal.input model="form.lead_time_min_days" type="number" :label="__('portal.fields.lead_time_min_days')" />
                            <x-portal.input model="form.lead_time_max_days" type="number" :label="__('portal.fields.lead_time_max_days')" />
                        </div>
                    </div>
                    <fieldset>
                        <legend class="text-sm font-medium text-ink-100">{{ __('portal.fields.services') }}</legend>
                        <div class="mt-2 flex flex-wrap gap-5">
                            @foreach (['offers_oem' => 'OEM', 'offers_odm' => 'ODM', 'offers_private_label' => __('portal.fields.private_label')] as $f => $l)
                                <label class="flex items-center gap-2 text-sm text-ink-200"><input type="checkbox" wire:model.live="form.{{ $f }}" class="h-4 w-4 rounded border-ink-500 bg-ink-850 text-brand-600">{{ $l }}</label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-ink-400">{{ __('portal.hints.oem_odm') }}</p>
                    </fieldset>
                    @foreach (['export_markets' => \App\Support\SupplierOptions::exportMarkets(), 'languages' => \App\Support\SupplierOptions::languages()] as $f => $opts)
                        <fieldset>
                            <legend class="text-sm font-medium text-ink-100">{{ __('portal.fields.'.$f) }}</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ($opts as $v => $l)
                                    <label class="flex items-center gap-2 text-sm text-ink-200"><input type="checkbox" value="{{ $v }}" wire:model.live="form.{{ $f }}" class="h-4 w-4 rounded border-ink-500 bg-ink-850 text-brand-600">{{ $l }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                    <div class="space-y-1.5">
                        <label for="p-cap" class="block text-sm font-medium text-ink-100">{{ __('portal.fields.capabilities') }}</label>
                        <textarea id="p-cap" rows="4" wire:model.live.blur="capabilities" class="block w-full rounded-lg border border-ink-600 bg-ink-850 px-3.5 py-3 text-ink-50 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                        <p class="text-xs text-ink-400">{{ __('portal.hints.capabilities') }}</p>
                    </div>
                @elseif ($step === 'documents')
                    <x-alert>{{ __('portal.documents.privacy') }}</x-alert>
                    <form wire:submit="uploadDocument" class="grid gap-4 rounded-lg border border-ink-700 p-4 sm:grid-cols-3 sm:items-end">
                        <x-portal.select model="documentType" :label="__('portal.documents.type')" :options="collect(\App\Enums\DocumentType::supplierUploadable())->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])->all()" />
                        <div class="space-y-1.5">
                            <label for="p-doc" class="block text-sm font-medium text-ink-100">{{ __('portal.documents.file') }}</label>
                            <input id="p-doc" type="file" wire:model="documentUpload" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm text-ink-300 file:mr-3 file:rounded-md file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-ink-50">
                            <div wire:loading wire:target="documentUpload" class="text-xs text-ink-400">{{ __('portal.uploading') }}</div>
                            @error('documentUpload')<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
                        </div>
                        <x-portal.input model="documentValidUntil" type="date" :label="__('portal.documents.valid_until')" />
                        <div class="sm:col-span-3"><x-button size="sm" wire:loading.attr="disabled">{{ __('portal.documents.upload') }}</x-button> <span class="ml-2 text-xs text-ink-400">{{ __('portal.documents.limits') }}</span></div>
                    </form>
                    @php($docs = $supplier->documents->where('type', '!=', \App\Enums\DocumentType::Evidence))
                    @if ($docs->isEmpty())
                        <x-empty-state icon="heroicon-o-document-arrow-up" :title="__('portal.documents.empty_title')">{{ __('portal.documents.empty_text') }}</x-empty-state>
                    @else
                        <ul class="divide-y divide-ink-700 rounded-lg border border-ink-700">
                            @foreach ($docs as $doc)
                                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm" wire:key="doc-{{ $doc->id }}">
                                    <div class="min-w-0">
                                        <a href="{{ route('documents.download', $doc) }}" class="font-medium text-ink-50 hover:text-brand-300">{{ $doc->original_name }}</a>
                                        <p class="text-xs text-ink-400">{{ $doc->type->getLabel() }} · {{ $doc->created_at->format('d-m-Y') }}@if ($doc->valid_until) · {{ __('portal.documents.valid_until') }} {{ $doc->valid_until->format('d-m-Y') }}@endif</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span @class(['rounded-full border px-2 py-0.5 text-xs', 'border-verified-500/60 text-verified-400' => $doc->status->value === 'approved', 'border-danger-500/60 text-danger-400' => $doc->status->value === 'rejected', 'border-ink-500 text-ink-300' => $doc->status->value === 'pending'])>{{ $doc->status->getLabel() }}</span>
                                        @if ($doc->status->value === 'pending')
                                            <button type="button" wire:click="deleteDocument({{ $doc->id }})" wire:confirm="{{ __('portal.documents.confirm_delete') }}" class="text-xs text-ink-400 hover:text-danger-400">{{ __('portal.delete') }}</button>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @elseif ($step === 'products')
                    <form wire:submit="saveProduct" class="grid gap-4 rounded-lg border border-ink-700 p-4 sm:grid-cols-2">
                        <x-portal.input model="product.name" :label="__('portal.products.name')" required />
                        <x-portal.select model="product.category_id" :label="__('portal.products.category')" :placeholder="'—'" :options="\App\Models\Category::active()->whereIn('id', $categoryIds)->withCurrentTranslation()->get()->mapWithKeys(fn ($c) => [$c->id => $c->name()])->all()" />
                        <x-portal.input model="product.moq" type="number" :label="__('portal.fields.moq_min')" />
                        <div class="grid grid-cols-3 gap-3">
                            <x-portal.input model="product.price_min" type="number" step="0.01" :label="__('portal.products.price_from')" />
                            <x-portal.input model="product.price_max" type="number" step="0.01" :label="__('portal.products.price_to')" />
                            <x-portal.select model="product.currency" :label="__('portal.products.currency')" :options="['USD' => 'USD', 'EUR' => 'EUR', 'CNY' => 'CNY']" />
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label for="p-pdesc" class="block text-sm font-medium text-ink-100">{{ __('portal.products.description') }}</label>
                            <textarea id="p-pdesc" rows="3" wire:model="product.description" class="block w-full rounded-lg border border-ink-600 bg-ink-850 px-3.5 py-3 text-ink-50"></textarea>
                        </div>
                        <div class="space-y-1.5">
                            <label for="p-pimg" class="block text-sm font-medium text-ink-100">{{ __('portal.products.image') }}</label>
                            <input id="p-pimg" type="file" wire:model="productImage" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm text-ink-300 file:mr-3 file:rounded-md file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-ink-50">
                            @error('productImage')<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-end gap-3">
                            <x-button size="sm">{{ $product['id'] ? __('portal.products.update') : __('portal.products.add') }}</x-button>
                            @if ($product['id'])<x-button size="sm" variant="ghost" type="button" wire:click="resetProduct">{{ __('portal.cancel') }}</x-button>@endif
                        </div>
                    </form>
                    @if ($supplier->products->isEmpty())
                        <x-empty-state icon="heroicon-o-cube" :title="__('portal.products.empty_title')">{{ __('portal.products.empty_text') }}</x-empty-state>
                    @else
                        <ul class="grid gap-3 sm:grid-cols-2">
                            @foreach ($supplier->products as $p)
                                <li class="flex gap-3 rounded-lg border border-ink-700 p-3" wire:key="prod-{{ $p->id }}">
                                    @if ($p->imageUrl())<img src="{{ $p->imageUrl() }}" alt="" class="h-16 w-16 rounded-md object-cover">@endif
                                    <div class="min-w-0 flex-1 text-sm">
                                        <p class="font-medium text-ink-50">{{ $p->name }}</p>
                                        <p class="text-ink-400">{{ \App\Support\Money::range($p->price_min_cents, $p->price_max_cents, $p->currency) }}@if ($p->moq) · MOQ {{ number_format($p->moq) }}@endif</p>
                                        <div class="mt-1 flex gap-3 text-xs">
                                            <button type="button" wire:click="editProduct({{ $p->id }})" class="text-brand-400">{{ __('portal.edit') }}</button>
                                            <button type="button" wire:click="deleteProduct({{ $p->id }})" wire:confirm="{{ __('portal.products.confirm_delete') }}" class="text-ink-400 hover:text-danger-400">{{ __('portal.delete') }}</button>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    @if ($this->missing === [])
                        <x-alert type="success">{{ $supplier->onboarding_submitted_at ? __('portal.review.already_submitted', ['date' => $supplier->onboarding_submitted_at->format('d-m-Y')]) : __('portal.review.ready') }}</x-alert>
                    @else
                        <x-alert type="warning">
                            {{ __('portal.review.missing') }}
                            <ul class="mt-2 list-disc pl-5">@foreach ($this->missing as $m)<li>{{ __($m) }}</li>@endforeach</ul>
                        </x-alert>
                    @endif
                    @error('submit')<x-alert type="error">{{ $message }}</x-alert>@enderror
                    <div>
                        <h3 class="text-sm font-semibold text-ink-50">{{ __('portal.review.verification_title') }}</h3>
                        <p class="mt-1 text-sm text-ink-300">{{ __('portal.review.verification_text') }}</p>
                        <ul class="mt-3 divide-y divide-ink-700 rounded-lg border border-ink-700 text-sm">
                            @foreach ($supplier->verificationChecks->sortBy(fn ($c) => array_search($c->type, \App\Enums\VerificationCheckType::cases())) as $check)
                                <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                                    <span class="text-ink-200">{{ $check->type->getLabel() }}</span>
                                    <span class="text-xs text-ink-400">{{ $check->isExpired() ? __('enums.check_status.expired') : $check->status->getLabel() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <x-button type="button" wire:click="submit" :disabled="$this->missing !== []" size="lg">{{ $supplier->onboarding_submitted_at ? __('portal.review.resubmit') : __('portal.review.submit') }}</x-button>
                @endif
            </div>

            <div class="mt-8 flex justify-between border-t border-ink-700 pt-6">
                @if ($stepIndex > 0)
                    <x-button type="button" variant="secondary" size="sm" wire:click="goTo('{{ \App\Livewire\SupplierPortal::STEPS[$stepIndex - 1] }}')">← {{ __('portal.previous') }}</x-button>
                @else <span></span> @endif
                @if ($stepIndex < count(\App\Livewire\SupplierPortal::STEPS) - 1)
                    <x-button type="button" size="sm" wire:click="goTo('{{ \App\Livewire\SupplierPortal::STEPS[$stepIndex + 1] }}')">{{ __('portal.next') }} →</x-button>
                @endif
            </div>
        </section>
    </div>
</x-container>
