@extends('admin.layouts.base')

@section('title', __('admin.edit') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel
        :title="__('admin.edit')"
    >
        <x-admin.button
            data-submit-loader
            :type="'submit'"
            :form="'controlForm'"
            :title="__('admin.save')"
            :iconName="'floppy'"
            :withLoader="true"
            :withMiniStyle="true"
        />
    </x-admin.main-panel>
@endsection

@section('content')
    @php $lang = config('app.fallback_locale', 'en'); @endphp

    <x-admin.container
        :id="'controlForm'"
        :action="route('admin.site-sections.contact-us.update', $section->id)"
        :method="'PATCH'"
    >
        <div class="grid gap-4">
            <div class="g-col-12 g-col-md-4">
                <!-- image -->
                <x-admin.field.image
                    :name="'bg_image'"
                    :placeholder="__('admin.bg_image') . ' ( 16 / 9 )'"
                    :ratio="'16x9'"
                    :src="$section->hasMedia('bg-image') ? $section->getFirstMediaUrl('bg-image', 'md-webp') : null"
                    :required="!$section->hasMedia('bg-image')"
                />
            </div>

            <div class="d-flex flex-column gap-4 g-col-12 g-col-md-8">
                <!-- title -->
                <x-admin.field.text
                    :name="'title['. $lang .']'"
                    :value="old('title.' . $lang, $section->getTranslation('title', $lang, false) ?? '')"
                    :placeholder="__('admin.title')"
                />

                <!-- description -->
                <x-admin.field.textarea
                    :name="'content_data['. $lang .'][description]'"
                    :value="old('content_data.' . $lang . '.description', data_get($section->getTranslation('content_data', $lang, false), 'description'))"
                    :placeholder="__('admin.description')"
                />

                <x-admin.dynamic-fields.wrapper>
                    @foreach(data_get($section->getTranslation('content_data', config('app.fallback_locale')), 'phones', []) as $phone)
                        <x-admin.dynamic-fields.group>
                            <div class="d-flex flex-column gap-4">
                                <!-- phone -->
                                <x-admin.field.tel
                                    :name="'content_data['. config('app.fallback_locale') .'][phones][' . $loop->index . ']'"
                                    :value="$phone"
                                    :placeholder="__('admin.phone_number')"
                                />
                            </div>
                        </x-admin.dynamic-fields.group>
                    @endforeach

                    <x-slot:template>
                        <x-admin.dynamic-fields.group>
                            <div class="d-flex flex-column gap-4">
                                <!-- phone -->
                                <x-admin.field.tel
                                    :name="'content_data['. config('app.fallback_locale') .'][phones][0]'"
                                    :placeholder="__('admin.phone_number')"
                                />
                            </div>
                        </x-admin.dynamic-fields.group>
                    </x-slot:template>
                </x-admin.dynamic-fields.wrapper>

                <x-admin.dynamic-fields.wrapper>
                    @foreach(data_get($section->getTranslation('content_data', config('app.fallback_locale')), 'emails', []) as $email)
                        <x-admin.dynamic-fields.group>
                            <div class="d-flex flex-column gap-4">
                                <!-- email -->
                                <x-admin.field.email
                                    :name="'content_data['. config('app.fallback_locale') .'][emails][' . $loop->index . ']'"
                                    :value="$email"
                                    :placeholder="__('admin.email')"
                                />
                            </div>
                        </x-admin.dynamic-fields.group>
                    @endforeach

                    <x-slot:template>
                        <x-admin.dynamic-fields.group>
                            <div class="d-flex flex-column gap-4">
                                <!-- email -->
                                <x-admin.field.email
                                    :name="'content_data['. config('app.fallback_locale') .'][emails][0]'"
                                    :placeholder="__('admin.email')"
                                />
                            </div>
                        </x-admin.dynamic-fields.group>
                    </x-slot:template>
                </x-admin.dynamic-fields.wrapper>
            </div>
        </div>
    </x-admin.container>
@endsection
