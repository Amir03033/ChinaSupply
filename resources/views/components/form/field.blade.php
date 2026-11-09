@props(['name', 'label', 'hint' => null, 'required' => false])
@php($id = 'f-'.str_replace(['.', '[', ']'], '-', $name))
<div {{ $attributes->class('space-y-1.5') }}>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-100">
        {{ $label }}@if ($required)<span class="text-brand-400" aria-hidden="true"> *</span>@else <span class="font-normal text-ink-400">({{ __('ui.optional') }})</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-ink-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="text-sm text-danger-400">{{ $message }}</p>
    @enderror
</div>
