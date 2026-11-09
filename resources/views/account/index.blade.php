<x-layouts.app :title="__('account.title')" noindex>
    <x-container class="py-12">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <x-section-header as="h1" :title="__('account.welcome', ['name' => auth()->user()->name])" />
            <x-button :href="localized_route('sourcing.create')">{{ __('ui.cta.start_request') }}</x-button>
        </div>
        <x-account-nav class="mt-8" />

        @if (session('status'))<x-alert type="success" class="mt-6">{{ \Illuminate\Support\Facades\Lang::has('account.status.'.session('status')) ? __('account.status.'.session('status')) : session('status') }}</x-alert>@endif
        @if ($actionNeeded > 0)<x-alert type="warning" class="mt-6">{{ trans_choice('account.action_needed', $actionNeeded, ['count' => $actionNeeded]) }} <a href="{{ route('account.requests.index') }}" class="underline">{{ __('account.view_requests') }}</a></x-alert>@endif

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <section class="lg:col-span-2" aria-labelledby="open-req">
                <h2 id="open-req" class="text-lg font-semibold text-ink-50">{{ __('account.open_requests') }}</h2>
                @if ($openRequests->isEmpty())
                    <x-empty-state class="mt-4" icon="heroicon-o-clipboard-document-list" :title="__('account.requests_title')">
                        {{ __('account.requests_text') }}
                        <x-slot:actions><x-button :href="localized_route('sourcing.create')">{{ __('ui.cta.start_request') }}</x-button></x-slot:actions>
                    </x-empty-state>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($openRequests as $r)
                            <li><a href="{{ route('account.requests.show', $r->reference) }}" class="surface block p-5 hover:border-brand-500/60">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-medium text-ink-50">{{ $r->title }}</p><x-request-status :status="$r->status" />
                                </div>
                                <p class="mt-1 text-sm text-ink-400">{{ $r->reference }} · {{ $r->submitted_at->translatedFormat('j M Y') }}</p>
                                <p class="mt-2 text-sm text-ink-200">{{ $r->status->buyerNextStep() }}</p>
                            </a></li>
                        @endforeach
                    </ul>
                @endif
            </section>
            <aside class="space-y-4">
                <div class="surface p-5">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-ink-400">{{ __('account.nav.requests') }}</dt><dd class="mt-1 text-2xl font-semibold text-ink-50">{{ $requestCount }}</dd></div>
                        <div><dt class="text-ink-400">{{ __('account.nav.favorites') }}</dt><dd class="mt-1 text-2xl font-semibold text-ink-50">{{ $favoriteCount }}</dd></div>
                    </dl>
                </div>
                <div class="surface p-5">
                    <h2 class="font-semibold text-ink-50">{{ __('account.details') }}</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div><dt class="text-ink-400">{{ __('auth_pages.email') }}</dt><dd class="text-ink-100">{{ auth()->user()->email }}</dd></div>
                    </dl>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<x-button variant="secondary" size="sm">{{ __('auth_pages.logout') }}</x-button></form>
                </div>
            </aside>
        </div>
    </x-container>
</x-layouts.app>
