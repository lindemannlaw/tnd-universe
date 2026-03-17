@props([
    'name' => '',
    'value' => null,
    'required' => true,
    'autocomplete' => 'off',
    'placeholder' => null,
    'fieldAttrs' => null,
    'min' => 0,
    'max' => null,
    'step' => 1,
])

<label {{ $attributes->merge(['class' => 'd-block position-relative']) }}>
    <input
        data-form-control
        class="form-control"
        {{ $fieldAttrs ?? null }}
        type="number"
        name="{{ $name }}"
        value="{{ $value ?? '' }}"
        min="{{ $min }}"
        max="{{ $max }}"
        step="{{ $step }}"
        {{ $required ? 'required' : null }}
        autocomplete="{{ $autocomplete }}"
    />

    @if($placeholder)
        <span class="form-control-placeholder">{{ $placeholder }} {!! $required ? '<span class="text-danger opacity-75"> *</span>' : null !!}</span>
    @endif
</label>
