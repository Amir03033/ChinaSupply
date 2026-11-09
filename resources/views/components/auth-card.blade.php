@props(['title', 'lead' => null])
<x-layouts.app :title="$title" noindex>
    <x-container class="flex justify-center py-16">
        <div class="w-full max-w-md">
            <h1 class="text-2xl font-semibold tracking-tight text-ink-50">{{ $title }}</h1>
            @if ($lead)<p class="mt-2 text-sm text-ink-300">{{ $lead }}</p>@endif
            @if (session('status'))
                <x-alert type="success" class="mt-6">{{ \Illuminate\Support\Facades\Lang::has('auth_pages.status.'.session('status')) ? __('auth_pages.status.'.session('status')) : session('status') }}</x-alert>
            @endif
            <div class="surface mt-6 p-6 sm:p-8">{{ $slot }}</div>
            @isset($footer)<div class="mt-6 text-center text-sm text-ink-300">{{ $footer }}</div>@endisset
        </div>
    </x-container>
</x-layouts.app>
