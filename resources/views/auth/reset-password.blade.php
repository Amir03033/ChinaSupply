<x-auth-card :title="__('auth_pages.reset_title')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-form.input name="email" type="email" :label="__('auth_pages.email')" required autocomplete="email" :value="$request->email" />
        <x-form.input name="password" type="password" :label="__('auth_pages.password')" required autocomplete="new-password" :hint="__('auth_pages.password_hint')" />
        <x-form.input name="password_confirmation" type="password" :label="__('auth_pages.password_confirm')" required autocomplete="new-password" />
        <x-button class="w-full">{{ __('auth_pages.reset') }}</x-button>
    </form>
</x-auth-card>
