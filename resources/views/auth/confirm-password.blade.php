<x-auth-card :title="__('auth_pages.confirm_title')" :lead="__('auth_pages.confirm_lead')">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf
        <x-form.input name="password" type="password" :label="__('auth_pages.password')" required autofocus autocomplete="current-password" />
        <x-button class="w-full">{{ __('auth_pages.confirm') }}</x-button>
    </form>
</x-auth-card>
