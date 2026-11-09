<x-auth-card :title="__('auth_pages.forgot_title')" :lead="__('auth_pages.forgot_lead')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <x-form.input name="email" type="email" :label="__('auth_pages.email')" required autofocus autocomplete="email" />
        <x-button class="w-full">{{ __('auth_pages.send_link') }}</x-button>
    </form>
</x-auth-card>
