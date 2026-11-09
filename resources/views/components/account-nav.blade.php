@php($items = [['account', __('account.nav.overview')], ['account.requests.index', __('account.nav.requests')], ['account.favorites', __('account.nav.favorites')]])
<nav aria-label="{{ __('account.nav.label') }}" {{ $attributes->class('flex gap-1 overflow-x-auto border-b border-ink-700') }}>
    @foreach ($items as [$route, $label])
        @php($active = request()->routeIs($route) || ($route === 'account.requests.index' && request()->routeIs('account.requests.*')))
        <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
           @class(['-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm', 'border-brand-500 text-ink-50' => $active, 'border-transparent text-ink-300 hover:text-ink-50' => ! $active])>{{ $label }}</a>
    @endforeach
</nav>
