@props(['alternates' => []])
@if (count($alternates) > 1)
    <div class="flex items-center rounded-md border border-ink-700 p-0.5 text-xs" role="group" aria-label="{{ __('ui.language') }}">
        @foreach (\App\Support\Locales::all() as $code => $name)
            @if (isset($alternates[$code]))
                <a href="{{ $alternates[$code] }}" hreflang="{{ $code }}" lang="{{ $code }}" title="{{ $name }}"
                   @class(['rounded px-2 py-1 font-medium uppercase tracking-wide', 'bg-ink-700 text-ink-50' => $code === app()->getLocale(), 'text-ink-300 hover:text-ink-50' => $code !== app()->getLocale()])
                   @if ($code === app()->getLocale()) aria-current="true" @endif>{{ $code }}</a>
            @endif
        @endforeach
    </div>
@endif
