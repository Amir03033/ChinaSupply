@props(['name', 'label', 'type' => 'text', 'hint' => null, 'required' => false, 'value' => null])
@php($id = 'f-'.str_replace(['.', '[', ']'], '-', $name))
<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required">
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @else @if ($hint) aria-describedby="{{ $id }}-hint" @endif @enderror
        {{ $attributes->class(['block h-11 w-full rounded-lg border bg-ink-850 px-3.5 text-ink-50 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30', 'border-danger-500' => $errors->has($name), 'border-ink-600' => ! $errors->has($name)]) }}>
</x-form.field>
