@props([
    'article'         => null,
    'showField',
    'sourceField',
    'mediaField',
    'pdfSourceField',
])

@php
    use Illuminate\Support\Number;

    $showImage   = old($showField, $article->{$showField} ?? false);
    $sourceValue = old($sourceField, $article->{$sourceField} ?? 'pdf');
    $imageId     = old($mediaField, $article->{$mediaField} ?? null);
    $image       = $imageId
        ? \Spatie\MediaLibrary\MediaCollections\Models\Media::find($imageId)
        : null;
@endphp

<div class="link-image-field d-flex flex-column gap-2 mt-2"
     data-link-image-wrapper
     data-link-media-field="{{ $mediaField }}"
     data-show-field="{{ $showField }}"
     data-source-field="{{ $sourceField }}"
     data-media-field="{{ $mediaField }}"
     data-pdf-source-field="{{ $pdfSourceField }}">

    <x-admin.field.radio-switch
        class="m-0"
        :name="$showField"
        :title="__('admin.show_image')"
        :checked="(bool) $showImage"
    />

    <div class="link-image-field-body d-flex flex-column gap-2 ps-3 border-start"
         data-link-image-body
         style="{{ $showImage ? '' : 'display: none;' }}">

        <div class="d-flex gap-3 align-items-center flex-wrap">
            <label class="text-gray small mb-0 me-1">{{ __('admin.image_source') }}:</label>

            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="radio"
                       name="{{ $sourceField }}" value="pdf"
                       id="{{ $sourceField }}_pdf"
                       data-link-image-source-radio
                       {{ $sourceValue === 'pdf' ? 'checked' : '' }}>
                <label class="form-check-label small" for="{{ $sourceField }}_pdf">
                    {{ __('admin.image_source_pdf') }}
                </label>
            </div>

            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="radio"
                       name="{{ $sourceField }}" value="custom"
                       id="{{ $sourceField }}_custom"
                       data-link-image-source-radio
                       {{ $sourceValue === 'custom' ? 'checked' : '' }}>
                <label class="form-check-label small" for="{{ $sourceField }}_custom">
                    {{ __('admin.image_source_custom') }}
                </label>
            </div>
        </div>

        <input type="hidden" name="{{ $mediaField }}" value="{{ $image?->id }}" data-link-image-input>

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="link-image-preview col" data-link-image-preview>
                @if ($image)
                    <span class="d-inline-flex align-items-center gap-2">
                        <img src="{{ $image->getUrl() }}" alt=""
                             style="height: 36px; width: 48px; object-fit: cover; border-radius: 4px; background: #eee;">
                        <span class="fw-semibold">{{ $image->file_name }}</span>
                        <span class="text-gray small">({{ Number::fileSize($image->size) }})</span>
                    </span>
                @else
                    <span class="text-gray">{{ __('admin.no_image_selected') }}</span>
                @endif
            </div>

            <button type="button"
                    class="btn btn-sm btn-secondary d-inline-flex align-items-center gap-2"
                    data-link-image-generate
                    style="{{ $sourceValue === 'pdf' ? '' : 'display: none;' }}">
                <x-admin.icon :name="'magic'" :width="16" :height="16" />
                <span data-link-image-generate-label>{{ __('admin.generate_from_pdf') }}</span>
            </button>

            <button type="button"
                    class="btn btn-sm btn-secondary d-inline-flex align-items-center gap-2"
                    data-ajax-view-modal-button
                    data-action="{{ route('admin.media.picker', ['mime_filter' => 'image/*', 'field' => $mediaField]) }}"
                    data-modal="media-picker-modal"
                    data-with-hide-modals="0"
                    data-link-image-pick
                    style="{{ $sourceValue === 'custom' ? '' : 'display: none;' }}">
                <x-admin.icon :name="'folder2-open'" :width="16" :height="16" />
                <span>{{ __('admin.choose_file') }}</span>
            </button>

            <button type="button"
                    class="btn btn-sm btn-outline-danger d-inline-flex align-items-center"
                    data-link-image-clear>
                <x-admin.icon :name="'x'" :width="16" :height="16" />
            </button>
        </div>

        <div class="text-gray small" data-link-image-status></div>
    </div>
</div>
