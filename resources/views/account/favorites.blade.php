<x-layouts.app :title="__('account.nav.favorites')" noindex>
    <x-container class="py-12">
        <x-section-header as="h1" :title="__('account.nav.favorites')" />
        <x-account-nav class="mt-8" />
        @if ($suppliers->isEmpty())
            <x-empty-state class="mt-6" icon="heroicon-o-heart" :title="__('account.favorites_empty_title')">
                {{ __('account.favorites_empty_text') }}
                <x-slot:actions><x-button :href="localized_route('suppliers.index')">{{ __('directory.title') }}</x-button></x-slot:actions>
            </x-empty-state>
        @else
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($suppliers as $supplier)
                    <li class="flex flex-col gap-2"><x-supplier-card :supplier="$supplier" /><x-supplier-actions :supplier="$supplier" :compare-ids="$compareIds" :favorite-ids="$favoriteIds" /></li>
                @endforeach
            </ul>
        @endif
    </x-container>
</x-layouts.app>
