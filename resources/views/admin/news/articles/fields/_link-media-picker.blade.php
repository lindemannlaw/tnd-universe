@props([
    'article' => null,
    'field',
    'mime' => 'application/pdf',
])

@php
    use Illuminate\Support\Number;

    $value = old($field, $article->{$field} ?? null);
    $media = $value
        ? \Spatie\MediaLibrary\MediaCollections\Models\Media::find($value)
        : null;
@endphp

<div class="link-media-picker-field d-flex flex-column gap-2"
     data-link-media-field="{{ $field }}"
     data-link-media-url="{{ $media?->getUrl() }}"
     data-link-media-mime="{{ $media?->mime_type }}">
    <input type="hidden" name="{{ $field }}" value="{{ $media?->id }}" data-link-media-input>

    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="link-media-preview col" data-preview>
            @if ($media)
                <span class="fw-semibold">{{ $media->file_name }}</span>
                <span class="text-gray small">({{ Number::fileSize($media->size) }})</span>
            @else
                <span class="text-gray">{{ __('admin.no_file_selected') }}</span>
            @endif
        </div>

        <button type="button"
                class="btn btn-sm btn-secondary d-inline-flex align-items-center gap-2"
                data-ajax-view-modal-button
                data-action="{{ route('admin.media.picker', ['mime_filter' => $mime, 'field' => $field]) }}"
                data-modal="media-picker-modal"
                data-with-hide-modals="0">
            <x-admin.icon :name="'folder2-open'" :width="16" :height="16" />
            <span>{{ __('admin.choose_file') }}</span>
        </button>

        @if ($media)
            <a href="{{ $media->getUrl() }}"
               class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2"
               download="{{ $media->file_name }}">
                <x-admin.icon :name="'download'" :width="16" :height="16" />
            </a>
        @endif

        <button type="button"
                class="btn btn-sm btn-outline-danger d-inline-flex align-items-center"
                data-link-media-clear>
            <x-admin.icon :name="'x'" :width="16" :height="16" />
        </button>
    </div>
</div>
