@php($terms = \App\Models\Page::where('key', 'terms')->active()->with('translations')->first()?->translation())
<x-auth-card :title="__('auth_pages.register_title')" :lead="__('auth_pages.register_lead')">
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <x-form.input name="name" :label="__('auth_pages.name')" required autofocus autocomplete="name" />
        <x-form.input name="email" type="email" :label="__('auth_pages.email')" required autocomplete="email" />
        <x-form.input name="password" type="password" :label="__('auth_pages.password')" required autocomplete="new-password" :hint="__('auth_pages.password_hint')" />
        <x-form.input name="password_confirmation" type="password" :label="__('auth_pages.password_confirm')" required autocomplete="new-password" />
        <x-form.checkbox name="terms" required>
            {{ __('auth_pages.terms_label') }}
            @if ($terms)<a href="{{ localized_route('pages.show', ['slug' => $terms->slug]) }}" class="text-brand-400 underline underline-offset-2" target="_blank">{{ $terms->title }}</a>@endif
        </x-form.checkbox>
        <x-button class="w-full">{{ __('auth_pages.register') }}</x-button>
    </form>
    <x-slot:footer>{{ __('auth_pages.have_account') }} <a href="{{ route('login') }}" class="text-brand-400 hover:text-brand-300">{{ __('auth_pages.login') }}</a></x-slot:footer>
</x-auth-card>
