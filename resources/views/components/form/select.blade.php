@props(['name', 'label', 'options' => [], 'selected' => null, 'hint' => null, 'required' => false])
@php($id = 'f-'.str_replace(['.', '[', ']'], '-', $name))
<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required">
    <select id="{{ $id }}" name="{{ $name }}" @required($required) @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        {{ $attributes->class(['block h-11 w-full rounded-lg border bg-ink-850 px-3 text-ink-50 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30', 'border-danger-500' => $errors->has($name), 'border-ink-600' => ! $errors->has($name)]) }}>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) old($name, $selected) === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
</x-form.field>
