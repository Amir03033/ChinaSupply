@php
    $steps = \App\Livewire\SourcingWizard::STEPS;
    $step = $steps[$stepIndex];
    $select = 'block h-11 w-full rounded-lg border border-ink-600 bg-ink-850 px-3 text-ink-50 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp
<x-container class="py-12" x-data x-on:wizard-step.window="$nextTick(() => document.getElementById('wizard-top')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
    @if ($submittedReference)
        <div class="mx-auto max-w-2xl">
            <x-alert type="success">{{ __('requests.wizard.done_title', ['reference' => $submittedReference]) }}</x-alert>
            <div class="surface mt-6 p-6 sm:p-8">
                <h1 class="text-2xl font-semibold text-ink-50">{{ __('requests.wizard.done_heading') }}</h1>
                <ol class="mt-6 space-y-4 text-sm text-ink-200">
                    @foreach (__('requests.wizard.done_steps') as $i => $line)
                        <li class="flex gap-3"><span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-ink-600 font-mono text-xs text-brand-400">{{ $i + 1 }}</span><span class="leading-6">{{ $line }}</span></li>
                    @endforeach
                </ol>
                <p class="mt-6 text-sm text-ink-300">{{ __('requests.wizard.done_account') }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <x-button :href="route('register')">{{ __('auth_pages.register') }}</x-button>
                    <x-button :href="localized_route('suppliers.index')" variant="secondary">{{ __('directory.title') }}</x-button>
                </div>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-3xl">
            <x-section-header as="h1" :title="__('requests.wizard.title')" :lead="__('requests.wizard.lead')" />

            @if ($this->preferredSupplier)
                <x-alert class="mt-6">{{ __('requests.wizard.preferred', ['name' => $this->preferredSupplier->name]) }}</x-alert>
            @endif

            {{-- Progress --}}
            <nav id="wizard-top" class="mt-8 scroll-mt-24" aria-label="{{ __('requests.wizard.progress') }}">
                <p class="text-sm text-ink-300">{{ __('requests.wizard.step_of', ['current' => $stepIndex + 1, 'total' => count($steps)]) }} — <span class="text-ink-50">{{ __('requests.wizard.steps.'.$step) }}</span></p>
                <ol class="mt-3 grid grid-cols-5 gap-1.5">
                    @foreach ($steps as $i => $s)
                        <li>
                            <button type="button" wire:click="goTo({{ $i }})" @disabled($i >= $stepIndex) aria-label="{{ __('requests.wizard.steps.'.$s) }}"
                                @class(['block h-1.5 w-full rounded-full', 'bg-brand-500' => $i <= $stepIndex, 'bg-ink-700' => $i > $stepIndex, 'cursor-pointer' => $i < $stepIndex])></button>
                        </li>
                    @endforeach
                </ol>
            </nav>

            <form wire:submit="{{ $stepIndex === count($steps) - 1 ? 'submit' : 'next' }}" class="surface mt-6 space-y-5 p-6 sm:p-8" novalidate>
                @if ($step === 'product')
                    <x-wizard.text model="form.title" :label="__('requests.fields.title')" required :placeholder="__('requests.placeholders.title')" maxlength="120" />
                    <x-wizard.text model="form.description" :label="__('requests.fields.description')" required rows="6" :placeholder="__('requests.placeholders.description')" :hint="__('requests.hints.description')" />
                    <div class="space-y-1.5">
                        <label for="w-category" class="block text-sm font-medium text-ink-100">{{ __('requests.fields.category_id') }} <span class="font-normal text-ink-400">({{ __('ui.optional') }})</span></label>
                        <select id="w-category" wire:model="form.category_id" class="{{ $select }}">
                            <option value="">{{ __('requests.placeholders.category') }}</option>
                            @foreach ($this->categoryOptions as $root)
                                <optgroup label="{{ $root->name() }}">
                                    <option value="{{ $root->id }}">{{ $root->name() }} — {{ __('requests.general') }}</option>
                                    @foreach ($root->children as $child)<option value="{{ $child->id }}">{{ $child->name() }}</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('form.category_id')<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="w-uploads" class="block text-sm font-medium text-ink-100">{{ __('requests.fields.files') }} <span class="font-normal text-ink-400">({{ __('ui.optional') }})</span></label>
                        <input id="w-uploads" type="file" multiple wire:model="uploads" accept=".pdf,.jpg,.jpeg,.png,.webp"
                               class="block w-full text-sm text-ink-300 file:mr-3 file:rounded-md file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-ink-50">
                        <p class="text-xs text-ink-400">{{ __('requests.hints.files', ['max' => \App\Actions\Sourcing\StoreRequestFile::MAX_FILES]) }}</p>
                        <div wire:loading wire:target="uploads" class="text-xs text-ink-400">{{ __('portal.uploading') }}</div>
                        @error('uploads')<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
                        @error('uploads.*')<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
                        @if ($uploads)
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach ($uploads as $i => $u)
                                    <li class="flex items-center justify-between gap-3 rounded-md border border-ink-700 px-3 py-2" wire:key="up-{{ $i }}">
                                        <span class="truncate text-ink-200">{{ $u->getClientOriginalName() }}</span>
                                        <button type="button" wire:click="removeUpload({{ $i }})" class="text-xs text-ink-400 hover:text-danger-400">{{ __('portal.delete') }}</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @elseif ($step === 'details')
                    <x-wizard.text model="form.specifications" :label="__('requests.fields.specifications')" rows="4" :placeholder="__('requests.placeholders.specifications')" />
                    <x-wizard.text model="form.materials" :label="__('requests.fields.materials')" :placeholder="__('requests.placeholders.materials')" />
                    <fieldset>
                        <legend class="text-sm font-medium text-ink-100">{{ __('requests.fields.customization') }}</legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach (\App\Support\SourcingOptions::CUSTOMIZATION as $c)
                                <label class="flex cursor-pointer gap-3 rounded-lg border border-ink-700 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-500/10">
                                    <input type="radio" value="{{ $c }}" wire:model="form.customization" class="mt-1 h-4 w-4 border-ink-500 bg-ink-850 text-brand-600">
                                    <span><span class="block text-sm font-medium text-ink-50">{{ __('requests.customization.'.$c.'.label') }}</span><span class="block text-xs text-ink-400">{{ __('requests.customization.'.$c.'.hint') }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="text-sm font-medium text-ink-100">{{ __('requests.fields.required_certifications') }} <span class="font-normal text-ink-400">({{ __('ui.optional') }})</span></legend>
                        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach (\App\Support\SourcingOptions::CERTIFICATIONS as $c)
                                <label class="flex items-center gap-2 text-sm text-ink-200"><input type="checkbox" value="{{ $c }}" wire:model="form.required_certifications" class="h-4 w-4 rounded border-ink-500 bg-ink-850 text-brand-600">{{ __('requests.certs.'.$c) }}</label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-ink-400">{{ __('requests.hints.certifications') }}</p>
                    </fieldset>
                @elseif ($step === 'quantity')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-wizard.text model="form.quantity" type="number" min="1" :label="__('requests.fields.quantity')" required :hint="__('requests.hints.quantity')" />
                        <x-wizard.text model="form.quantity_unit" :label="__('requests.fields.quantity_unit')" :placeholder="__('requests.placeholders.unit')" />
                        <x-wizard.text model="form.target_price" type="number" step="0.01" min="0" :label="__('requests.fields.target_price')" :hint="__('requests.hints.target_price')" />
                        <div class="space-y-1.5">
                            <label for="w-currency" class="block text-sm font-medium text-ink-100">{{ __('requests.fields.currency') }}</label>
                            <select id="w-currency" wire:model="form.currency" class="{{ $select }}">@foreach (\App\Support\SourcingOptions::CURRENCIES as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select>
                        </div>
                        <x-wizard.text model="form.desired_delivery_date" type="date" :label="__('requests.fields.desired_delivery_date')" />
                    </div>
                @elseif ($step === 'delivery')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="w-country" class="block text-sm font-medium text-ink-100">{{ __('requests.fields.destination_country') }}<span class="text-brand-400"> *</span></label>
                            <select id="w-country" wire:model="form.destination_country" class="{{ $select }}">@foreach (\App\Support\SourcingOptions::countries() as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select>
                        </div>
                        <div class="space-y-1.5">
                            <label for="w-incoterm" class="block text-sm font-medium text-ink-100">{{ __('requests.fields.incoterm') }} <span class="font-normal text-ink-400">({{ __('ui.optional') }})</span></label>
                            <select id="w-incoterm" wire:model="form.incoterm" class="{{ $select }}">
                                <option value="">{{ __('requests.incoterms.unsure') }}</option>
                                @foreach (\App\Support\SourcingOptions::INCOTERMS as $i)<option value="{{ $i }}">{{ $i }} — {{ __('requests.incoterms.'.$i) }}</option>@endforeach
                            </select>
                            <p class="text-xs text-ink-400">{{ __('requests.hints.incoterm') }}</p>
                        </div>
                    </div>
                    <x-wizard.text model="form.additional_requirements" :label="__('requests.fields.additional_requirements')" rows="4" />
                @else
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-wizard.text model="form.contact_name" :label="__('requests.fields.contact_name')" required autocomplete="name" />
                        <x-wizard.text model="form.contact_email" type="email" :label="__('requests.fields.contact_email')" required autocomplete="email" :readonly="auth()->check()" />
                        <x-wizard.text model="form.company_name" :label="__('requests.fields.company_name')" autocomplete="organization" />
                        <x-wizard.text model="form.phone" type="tel" :label="__('requests.fields.phone')" autocomplete="tel" />
                    </div>
                    <div class="hidden" aria-hidden="true"><input type="text" wire:model="website" tabindex="-1" autocomplete="off"></div>

                    <section class="rounded-lg border border-ink-700 p-4 text-sm" aria-labelledby="summary">
                        <h2 id="summary" class="font-semibold text-ink-50">{{ __('requests.wizard.summary') }}</h2>
                        <dl class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                            <div><dt class="text-ink-400">{{ __('requests.fields.title') }}</dt><dd class="text-ink-100">{{ $form['title'] }}</dd></div>
                            <div><dt class="text-ink-400">{{ __('requests.fields.quantity') }}</dt><dd class="text-ink-100">{{ $form['quantity'] }} {{ $form['quantity_unit'] }}</dd></div>
                            <div><dt class="text-ink-400">{{ __('requests.fields.target_price') }}</dt><dd class="text-ink-100">{{ filled($form['target_price']) ? $form['currency'].' '.$form['target_price'] : '—' }}</dd></div>
                            <div><dt class="text-ink-400">{{ __('requests.fields.destination_country') }}</dt><dd class="text-ink-100">{{ __('requests.countries.'.$form['destination_country']) }}</dd></div>
                        </dl>
                    </section>

                    @php($privacy = \App\Models\Page::where('key', 'privacy')->active()->with('translations')->first()?->translation())
                    <div class="space-y-1.5">
                        <div class="flex items-start gap-3">
                            <input id="w-consent" type="checkbox" wire:model="consent" class="mt-0.5 h-5 w-5 rounded border-ink-500 bg-ink-850 text-brand-600" @error('consent') aria-invalid="true" @enderror>
                            <label for="w-consent" class="text-sm leading-6 text-ink-200">{{ __('requests.wizard.consent') }}
                                @if ($privacy)<a href="{{ localized_route('pages.show', ['slug' => $privacy->slug]) }}" target="_blank" class="text-brand-400 underline underline-offset-2">{{ $privacy->title }}</a>@endif
                            </label>
                        </div>
                        @error('consent')<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-xs text-ink-400">{{ __('requests.wizard.free_note') }}</p>
                @endif

                <div class="flex items-center justify-between border-t border-ink-700 pt-5">
                    @if ($stepIndex > 0)
                        <x-button type="button" variant="ghost" wire:click="back">← {{ __('portal.previous') }}</x-button>
                    @else <span></span> @endif
                    <x-button wire:loading.attr="disabled" wire:target="next,submit,uploads">
                        {{ $stepIndex === count($steps) - 1 ? __('requests.wizard.submit') : __('portal.next').' →' }}
                    </x-button>
                </div>
            </form>
        </div>
    @endif
</x-container>
