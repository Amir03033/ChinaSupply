<x-auth-card :title="__('auth_pages.login_title')">
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <x-form.input name="email" type="email" :label="__('auth_pages.email')" required autofocus autocomplete="username" />
        <x-form.input name="password" type="password" :label="__('auth_pages.password')" required autocomplete="current-password" />
        <div class="flex items-center justify-between gap-4">
            <x-form.checkbox name="remember">{{ __('auth_pages.remember') }}</x-form.checkbox>
            <a href="{{ route('password.request') }}" class="text-sm text-brand-400 hover:text-brand-300">{{ __('auth_pages.forgot') }}</a>
        </div>
        <x-button class="w-full">{{ __('auth_pages.login') }}</x-button>
    </form>
    <x-slot:footer>{{ __('auth_pages.no_account') }} <a href="{{ route('register') }}" class="text-brand-400 hover:text-brand-300">{{ __('auth_pages.register') }}</a></x-slot:footer>
</x-auth-card>
