@props([
    'title' => null,
    'description' => null,
    'alternates' => null,
    'canonical' => null,
    'noindex' => false,
])
@php
    $siteName = config('chinasupply.company.name');
    $fullTitle = $title ? $title.' · '.$siteName : $siteName.' — '.__('ui.tagline');
    $alternates ??= \App\Support\Alternates::forCurrentRoute();
    $canonical ??= url()->current();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @if ($noindex)
        <meta name="robots" content="noindex, follow">
    @else
        <link rel="canonical" href="{{ $canonical }}">
        @foreach ($alternates as $locale => $url)
            <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
        @endforeach
        @if (isset($alternates[\App\Support\Locales::default()]))
            <link rel="alternate" hreflang="x-default" href="{{ $alternates[\App\Support\Locales::default()] }}">
        @endif
    @endif
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    @if ($description)<meta property="og:description" content="{{ $description }}">@endif
    <meta name="theme-color" content="#0b0f15">
    <link rel="icon" href="/favicon.ico" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand-600 focus:px-4 focus:py-2">{{ __('ui.skip_to_content') }}</a>

    <x-site-header :alternates="$alternates" />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-site-footer />
    @livewireScriptConfig
</body>
</html>
