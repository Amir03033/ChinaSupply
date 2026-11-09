@props(['model', 'label', 'options' => [], 'required' => false, 'placeholder' => null])
@php($id = 'p-'.str_replace('.', '-', $model))
<div class="space-y-1.5">
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-100">{{ $label }}@if ($required)<span class="text-brand-400" aria-hidden="true"> *</span>@endif</label>
    <select id="{{ $id }}" wire:model.live="{{ $model }}" @error($model) aria-invalid="true" @enderror
        class="block h-11 w-full rounded-lg border border-ink-600 bg-ink-850 px-3 text-ink-50 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
    </select>
    @error($model)<p class="text-sm text-danger-400">{{ $message }}</p>@enderror
</div>
