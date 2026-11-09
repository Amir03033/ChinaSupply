<x-auth-card :title="__('auth_pages.two_factor_title')" :lead="__('auth_pages.two_factor_lead')">
    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5" x-data="{ recovery: false }">
        @csrf
        <div x-show="!recovery"><x-form.input name="code" :label="__('auth_pages.code')" inputmode="numeric" autocomplete="one-time-code" autofocus /></div>
        <div x-show="recovery" x-cloak><x-form.input name="recovery_code" :label="__('auth_pages.recovery_code')" autocomplete="off" /></div>
        <x-button class="w-full">{{ __('auth_pages.login') }}</x-button>
        <button type="button" class="text-sm text-brand-400 hover:text-brand-300" @click="recovery = !recovery"
                x-text="recovery ? @js(__('auth_pages.use_code')) : @js(__('auth_pages.use_recovery'))"></button>
    </form>
</x-auth-card>
