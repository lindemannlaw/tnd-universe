@php
    $files = !(isset($isClone) && $isClone) && isset($project) && $project->hasMedia($project->mediaFiles) ? $project->getMedia($project->mediaFiles) : [];
@endphp

@foreach($files as $file)
    <div class="d-flex gap-3 {{ $loop->last ? 'mb-4' : ''}}">
        <!-- name -->
        <x-admin.field.text
            class="col"
            :name="'current_files[' . $file->id . '][name]'"
            :value="$file->custom_properties['name']"
            :placeholder="__('admin.file_name')"
        />

        <!-- download -->
        <x-admin.button
            :btnInFieldGroup="true"
            :href="$file->getUrl()"
            :icon-name="'download'"
            download="{{ $file->custom_properties['name'] }}"
        />

        <x-admin.ajax.delete-modal-button
            :subtitle="$file->custom_properties['name'] . '.' . $file->extension"
            :deleteAction="route('admin.portfolio.project.delete.file', $file->id)"
            :updateIdSection="'project-files'"
            :btnInFieldGroup="true"
            :withHideModals="false"
        />
    </div>
@endforeach
