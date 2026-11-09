@props(['supplier', 'compareIds' => [], 'favoriteIds' => []])
@php($inCompare = in_array($supplier->id, $compareIds, true))
@php($isFavorite = in_array($supplier->id, $favoriteIds, true))
<div class="flex items-center gap-2">
    <form method="POST" action="{{ route('compare.toggle', $supplier) }}">@csrf
        <button type="submit" aria-pressed="{{ $inCompare ? 'true' : 'false' }}"
            @class(['inline-flex h-8 items-center gap-1.5 rounded-md border px-2.5 text-xs transition', 'border-brand-500 bg-brand-500/15 text-brand-300' => $inCompare, 'border-ink-600 text-ink-300 hover:text-ink-50' => ! $inCompare])>
            <x-heroicon-m-scale class="h-4 w-4" aria-hidden="true" />{{ $inCompare ? __('directory.in_compare') : __('directory.compare') }}
        </button>
    </form>
    @auth
        @if (auth()->user()->isBuyer())
            <form method="POST" action="{{ route('favorites.toggle', $supplier) }}">@csrf
                <button type="submit" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" aria-label="{{ $isFavorite ? __('directory.unsave') : __('directory.save') }}" title="{{ $isFavorite ? __('directory.unsave') : __('directory.save') }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-ink-600 text-ink-300 hover:text-ink-50">
                    @if ($isFavorite)<x-heroicon-s-heart class="h-4 w-4 text-brand-400" aria-hidden="true" />@else<x-heroicon-o-heart class="h-4 w-4" aria-hidden="true" />@endif
                </button>
            </form>
        @endif
    @else
        <a href="{{ route('login') }}" aria-label="{{ __('directory.save_login') }}" title="{{ __('directory.save_login') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-ink-600 text-ink-300 hover:text-ink-50"><x-heroicon-o-heart class="h-4 w-4" aria-hidden="true" /></a>
    @endauth
</div>
