<x-layouts.app :title="__('contact.title')" :description="__('contact.meta')">
    <x-container class="grid gap-12 py-14 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <x-section-header as="h1" :title="__('contact.title')" :lead="__('contact.lead')" />
            <x-alert class="mt-8">{{ __('contact.sourcing_notice') }} <a href="{{ localized_route('sourcing.create') }}" class="underline">{{ __('ui.cta.start_request') }}</a></x-alert>
        </div>
        <div class="lg:col-span-3">
            @if (session('status'))
                <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
            @endif
            @if ($errors->any())
                <x-alert type="error" class="mb-6">{{ __('ui.form_errors') }}</x-alert>
            @endif
            <form method="POST" action="{{ localized_route('contact.store') }}" class="surface space-y-5 p-6 sm:p-8" novalidate>
                @csrf
                <x-form.select name="topic" :label="__('contact.attributes.topic')" required :selected="$topic->value"
                    :options="collect(\App\Enums\ContactTopic::cases())->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])->all()" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="name" :label="__('contact.attributes.name')" required autocomplete="name" />
                    <x-form.input name="email" type="email" :label="__('contact.attributes.email')" required autocomplete="email" />
                    <x-form.input name="company" :label="__('contact.attributes.company')" autocomplete="organization" />
                    <x-form.input name="phone" type="tel" :label="__('contact.attributes.phone')" autocomplete="tel" />
                </div>
                <x-form.textarea name="message" :label="__('contact.attributes.message')" required rows="6" :hint="__('contact.message_hint')" :value="$prefill" />
                {{-- Honeypot --}}
                <div class="hidden" aria-hidden="true"><label for="f-website">Website</label><input id="f-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
                @php($privacy = \App\Models\Page::where('key', 'privacy')->active()->with('translations')->first()?->translation())
                <x-form.checkbox name="privacy" required>
                    {{ __('contact.privacy_label') }}
                    @if ($privacy)<a href="{{ localized_route('pages.show', ['slug' => $privacy->slug]) }}" class="text-brand-400 underline underline-offset-2" target="_blank">{{ $privacy->title }}</a>@endif
                </x-form.checkbox>
                <x-button size="lg" class="w-full sm:w-auto">{{ __('contact.submit') }}</x-button>
            </form>
        </div>
    </x-container>
</x-layouts.app>
