<x-admin.dynamic-fields.wrapper :wrapperStyles="'grid gap-4'">
    @php
        $gallery = [];

        if (isset($project)) {
            $gallery = $project->getMedia($project->mediaGallery)->sortBy('order_column');
        }
    @endphp

    @foreach ($gallery as $index => $media)
        <x-admin.dynamic-fields.group class="g-col-12 g-col-xs-6 g-col-lg-4">
            <!-- gallery image -->
            <x-admin.field.image
                :name="'gallery[' . $index . '][image]'"
                :placeholder="__('admin.image') . ' ( 16 / 9 )'"
                :ratio="'16x9'"
                :src="$media->getUrl('md-webp')"
                :required="isset($project) ? !$project->hasMedia($project->mediaGallery) : true"
            />

            <x-admin.field.hidden
                :name="'gallery[' . $index . '][media_id]'"
                :value="$media->id"
                :required="false"
            />
        </x-admin.dynamic-fields.group>
    @endforeach

    <x-slot:template>
        <x-admin.dynamic-fields.group class="g-col-12 g-col-xs-6 g-col-lg-4">
            <!-- gallery image -->
            <x-admin.field.image
                :name="'gallery[0][image]'"
                :placeholder="__('admin.image') . ' ( 16 / 9 )'"
                :ratio="'16x9'"
                :required="isset($project) ? !$project->hasMedia($project->mediaHero) : true"
            />

            <x-admin.field.hidden :name="'gallery[0][media_id]'" />
        </x-admin.dynamic-fields.group>
    </x-slot:template>
</x-admin.dynamic-fields.wrapper>
