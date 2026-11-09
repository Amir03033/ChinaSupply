@props(['name', 'required' => false])
@php($id = 'f-'.$name)
<div {{ $attributes->class('space-y-1.5') }}>
    <div class="flex items-start gap-3">
        <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1" @checked(old($name)) @required($required)
            @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
            class="mt-0.5 h-5 w-5 rounded border-ink-500 bg-ink-850 text-brand-600 focus:ring-brand-500/40">
        <label for="{{ $id }}" class="text-sm leading-6 text-ink-200">{{ $slot }}</label>
    </div>
    @error($name)
        <p id="{{ $id }}-error" class="text-sm text-danger-400">{{ $message }}</p>
    @enderror
</div>
