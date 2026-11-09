@props(['model', 'label', 'type' => 'text', 'hint' => null, 'required' => false])
@php($id = 'p-'.str_replace('.', '-', $model))
<div class="space-y-1.5">
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-100">{{ $label }}@if ($required)<span class="text-brand-400" aria-hidden="true"> *</span>@endif</label>
    <input id="{{ $id }}" type="{{ $type }}" wire:model.live.blur="{{ $model }}"
        @error($model) aria-invalid="true" aria-describedby="{{ $id }}-e" @enderror
        {{ $attributes->class(['block h-11 w-full rounded-lg border bg-ink-850 px-3.5 text-ink-50 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30', 'border-danger-500' => $errors->has($model), 'border-ink-600' => ! $errors->has($model)]) }}>
    @if ($hint)<p class="text-xs text-ink-400">{{ $hint }}</p>@endif
    @error($model)<p id="{{ $id }}-e" class="text-sm text-danger-400">{{ $message }}</p>@enderror
</div>
