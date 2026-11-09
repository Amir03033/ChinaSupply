@php($agreement = \App\Models\Page::where('key', 'supplier_agreement')->active()->with('translations')->first()?->translation())
<x-layouts.app :title="__('portal.register.title')" :description="__('portal.register.lead')">
    <x-container class="grid gap-12 py-14 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <x-section-header as="h1" :title="__('portal.register.title')" :lead="__('portal.register.lead')" />
            <ul class="mt-8 space-y-3 text-sm text-ink-300">
                @foreach (__('portal.register.points') as $p)
                    <li class="flex gap-2.5"><x-heroicon-m-check class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" aria-hidden="true" />{{ $p }}</li>
                @endforeach
            </ul>
        </div>
        <div class="lg:col-span-3">
            @if ($errors->any())<x-alert type="error" class="mb-6">{{ __('ui.form_errors') }}</x-alert>@endif
            <form method="POST" action="{{ localized_route('supplier.register.store') }}" class="surface space-y-5 p-6 sm:p-8" novalidate>
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="company_name" :label="__('portal.register.attributes.company_name')" required autocomplete="organization" />
                    <x-form.select name="country" :label="__('portal.register.attributes.country')" required selected="CN" :options="\App\Support\SupplierOptions::countries()" />
                    <x-form.input name="name" :label="__('portal.register.attributes.name')" required autocomplete="name" />
                    <x-form.input name="email" type="email" :label="__('portal.register.attributes.email')" required autocomplete="email" :hint="__('portal.register.email_hint')" />
                    <x-form.input name="password" type="password" :label="__('portal.register.attributes.password')" required autocomplete="new-password" :hint="__('auth_pages.password_hint')" />
                    <x-form.input name="password_confirmation" type="password" :label="__('auth_pages.password_confirm')" required autocomplete="new-password" />
                </div>
                <div class="hidden" aria-hidden="true"><input type="text" name="website_url" tabindex="-1" autocomplete="off"></div>
                <x-form.checkbox name="agreement" required>
                    {{ __('portal.register.agreement') }}
                    @if ($agreement)<a href="{{ localized_route('pages.show', ['slug' => $agreement->slug]) }}" target="_blank" class="text-brand-400 underline underline-offset-2">{{ $agreement->title }}</a>@endif
                </x-form.checkbox>
                <x-button size="lg">{{ __('portal.register.submit') }}</x-button>
            </form>
            <p class="mt-4 text-sm text-ink-400">{{ __('auth_pages.have_account') }} <a href="{{ route('login') }}" class="text-brand-400">{{ __('auth_pages.login') }}</a></p>
        </div>
    </x-container>
</x-layouts.app>
