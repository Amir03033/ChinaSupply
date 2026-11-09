@props(['name', 'label', 'hint' => null, 'required' => false, 'rows' => 5, 'value' => null])
@php($id = 'f-'.str_replace(['.', '[', ']'], '-', $name))
<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required">
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @else @if ($hint) aria-describedby="{{ $id }}-hint" @endif @enderror
        {{ $attributes->class(['block w-full rounded-lg border bg-ink-850 px-3.5 py-3 text-ink-50 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30', 'border-danger-500' => $errors->has($name), 'border-ink-600' => ! $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
</x-form.field>
