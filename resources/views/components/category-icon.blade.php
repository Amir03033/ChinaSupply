@props(['icon' => null])
@php
    // Only render icons that exist in the installed Heroicons set; admins type
    // the name in Filament, so an unknown name must never break the page.
    $name = $icon && preg_match('/^heroicon-[os]-[a-z0-9-]+$/', $icon)
        && file_exists(base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/'.substr($icon, 9).'.svg'))
        ? $icon : 'heroicon-o-squares-2x2';
@endphp
<x-dynamic-component :component="$name" {{ $attributes }} aria-hidden="true" />
