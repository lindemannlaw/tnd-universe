<div class="d-flex flex-column gap-4">
    <!-- address -->
    <x-admin.field.text
        :name="'content_data['. config('app.fallback_locale') .'][address]'"
        :value="old('content_data.'. config('app.fallback_locale') . '.address', data_get($page->getTranslation('content_data', config('app.fallback_locale')), 'address'))"
        :placeholder="__('admin.address')"
    />

    <x-admin.dynamic-fields.wrapper>
        @foreach(data_get($page->getTranslation('content_data', config('app.fallback_locale')), 'phones', []) as $phone)
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
        @foreach(data_get($page->getTranslation('content_data', config('app.fallback_locale')), 'emails', []) as $email)
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

    <!-- whatsapp number -->
    <x-admin.field.tel
        :name="'content_data['. config('app.fallback_locale') .'][whatsapp]'"
        :value="old('content_data.'. config('app.fallback_locale') . '.whatsapp', data_get($page->getTranslation('content_data', config('app.fallback_locale')), 'whatsapp'))"
        :placeholder="__('admin.whatsapp_number')"
        :required="false"
    />

    @php $lang = config('app.fallback_locale'); @endphp
    <!-- whatsapp intro text (Contacts page) -->
    <x-admin.field.wysiwyg
        :name="'content_data['. $lang .'][whatsapp_text]'"
        :value="old('content_data.' . $lang . '.whatsapp_text', data_get($page->getTranslation('content_data', $lang, false), 'whatsapp_text'))"
        :placeholder="__('admin.whatsapp_intro_text')"
        :buttons="'list|fontColor'"
        :required="false"
    />
</div>
