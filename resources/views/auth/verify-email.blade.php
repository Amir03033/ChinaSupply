<x-auth-card :title="__('auth_pages.verify_title')" :lead="__('auth_pages.verify_lead')">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-button class="w-full">{{ __('auth_pages.verify_resend') }}</x-button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-ink-300 hover:text-ink-50">{{ __('auth_pages.logout') }}</button>
    </form>
</x-auth-card>
