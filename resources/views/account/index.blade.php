<x-layouts.app :title="__('account.title')" noindex>
    <x-container class="py-14">
        <x-section-header as="h1" :title="__('account.welcome', ['name' => auth()->user()->name])" :lead="__('account.lead')" />
        @if (session('status'))
            <x-alert type="success" class="mt-6 max-w-2xl">{{ \Illuminate\Support\Facades\Lang::has('account.status.'.session('status')) ? __('account.status.'.session('status')) : session('status') }}</x-alert>
        @endif
        <div class="mt-10 grid max-w-4xl gap-6 md:grid-cols-2">
            <x-empty-state icon="heroicon-o-clipboard-document-list" :title="__('account.requests_title')">
                {{ __('account.requests_text') }}
                <x-slot:actions><x-button :href="localized_route('contact', ['topic' => 'sourcing'])">{{ __('ui.cta.start_request') }}</x-button></x-slot:actions>
            </x-empty-state>
            <div class="surface p-6">
                <h2 class="font-semibold text-ink-50">{{ __('account.details') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-ink-400">{{ __('auth_pages.email') }}</dt><dd class="text-ink-100">{{ auth()->user()->email }}</dd></div>
                    <div><dt class="text-ink-400">{{ __('account.verified') }}</dt><dd class="text-ink-100">{{ auth()->user()->hasVerifiedEmail() ? __('ui.yes') : __('ui.no') }}</dd></div>
                </dl>
                <form method="POST" action="{{ route('logout') }}" class="mt-6">@csrf<x-button variant="secondary" size="sm">{{ __('auth_pages.logout') }}</x-button></form>
            </div>
        </div>
    </x-container>
</x-layouts.app>
