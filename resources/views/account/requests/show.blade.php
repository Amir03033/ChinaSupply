<x-layouts.app :title="$sourcingRequest->reference" noindex>
    <x-container class="py-12">
        <x-account-nav />
        @if (session('status'))<x-alert type="success" class="mt-6">{{ session('status') }}</x-alert>@endif
        <div class="mt-8">@include('account.requests._detail')</div>
    </x-container>
</x-layouts.app>
