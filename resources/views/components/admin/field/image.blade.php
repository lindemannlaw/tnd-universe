@props([
    'name' => '',
    'required' => true,
    'placeholder' => null,
    'fieldAttrs' => null,
    'accept' => '.png, .jpeg, .jpg, .webp',
    'ratio' => '4x3',
    'src' => null,
    'fit' => 'cover',
    'rounded' => 'rounded',
    'shadow' => false,
    'viewImageClasses' => '',
    'viewImageAttributes' => '',
    'youtubeVideoId' => null,
    'compact' => false,
    'mime' => 'image/png,image/jpeg,image/webp,image/gif,image/svg+xml',
])

@php
    $hasExistingSrc = (bool) $src;
    if (!$compact) {
        if (!$src) {
            if ($youtubeVideoId) {
                $src = 'https://i3.ytimg.com/vi/' . $youtubeVideoId . '/maxresdefault.jpg';
            } else {
                $ratioExplode = explode('x', $ratio);
                $ratioWidth = $ratioExplode[0];
                $ratioHeight = $ratioExplode[1];
                $src = '/img/default' . ($ratioWidth < $ratioHeight ? '-vertical' : '') . '.svg';
            }
        }
    }

    $mediaIdField = $name . '_media_id';
    $pickerAction = route('admin.media.picker', ['mime_filter' => $mime, 'field' => $mediaIdField]);
@endphp

@if($compact)
    {{-- Compact mode --}}
    <div data-preview-image-file
         data-image-picker-field="{{ $mediaIdField }}"
         {{ $attributes->merge(['class' => 'd-inline-flex cursor-pointer align-items-center']) }}>
        <button type="button"
                data-image-picker-trigger
                data-ajax-view-modal-button
                data-action="{{ $pickerAction }}"
                data-modal="media-picker-modal"
                data-with-hide-modals="0"
                class="btn p-0 border-0 bg-transparent text-start">
            <div data-pif-picture data-pif-compact
                 class="d-flex align-items-center gap-2 rounded border px-2 py-1 {{ $hasExistingSrc ? 'border-solid bg-white' : 'border-dashed bg-light' }}"
                 style="border-color: rgba(0,0,0,.2);">
                <img
                    data-pif-image
                    src="{{ $src ?: '' }}"
                    alt="preview"
                    class="rounded img-cover {{ $hasExistingSrc ? '' : 'd-none' }}"
                    style="width: 64px; height: 42px; object-fit: cover;"
                >
                <span data-pif-compact-placeholder class="d-flex align-items-center gap-1 text-primary small {{ $hasExistingSrc ? 'd-none' : '' }}">
                    <span class="d-flex align-items-center justify-content-center rounded-circle border border-primary" style="width:22px;height:22px;flex-shrink:0;font-size:14px;line-height:1;">+</span>
                    {{ $placeholder ?: 'Bild hinzufügen' }}
                </span>
                @if($hasExistingSrc)
                    <span class="small text-muted" data-pif-compact-change>Ändern</span>
                @endif
            </div>
        </button>

        <input type="hidden"
               name="{{ $mediaIdField }}"
               value=""
               data-image-picker-input
               {{ $required && !$hasExistingSrc ? 'data-image-picker-required' : '' }}
               {{ $hasExistingSrc ? 'data-pif-has-image' : '' }}>
    </div>
@else
    <div data-preview-image-file
         data-image-picker-field="{{ $mediaIdField }}"
         {{ $attributes->merge(['class' => 'd-block position-relative']) }}>
        <button type="button"
                data-image-picker-trigger
                data-ajax-view-modal-button
                data-action="{{ $pickerAction }}"
                data-modal="media-picker-modal"
                data-with-hide-modals="0"
                class="btn p-0 border-0 bg-transparent w-100 d-block text-start">
            <span class="d-block ratio ratio-{{ $ratio }}">
                <picture data-pif-picture class="d-flex w-100 h-100 position-absolute border border-dark rounded border-opacity-25 bg-white {{ $src ? 'p-3 border-solid' : 'p-4 border-dashed' }}">
                    <img
                        data-pif-image
                        data-pif-view-classes="{{ $viewImageClasses }}"
                        {{ $viewImageAttributes }}
                        src="{{ $src }}"
                        alt="image"
                        class="{{ $rounded . ' img-' . $fit }}"
                        style="filter: {{ $shadow ? 'drop-shadow(0 0 5px rgba(0, 0, 0, 0.5))' : 'none' }}"
                    />
                </picture>
            </span>
        </button>

        <input type="hidden"
               name="{{ $mediaIdField }}"
               value=""
               data-image-picker-input
               {{ $required && !$hasExistingSrc ? 'data-image-picker-required' : '' }}
               {{ $hasExistingSrc ? 'data-pif-has-image' : '' }}>

        <span class="form-control-placeholder {{ $src ? 'glued' : '' }} pe-none">{{ $placeholder }} {!! $required && !$hasExistingSrc ? '<span class="text-danger opacity-75"> *</span>' : '' !!}</span>
    </div>
@endif
