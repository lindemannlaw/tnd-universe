<x-admin.tabs.wrapper>
    <x-slot:nav>
        @foreach(supported_languages_keys() as $lang)
            <x-admin.tabs.nav-item
                :is-active="$loop->first"
                :target="'description-locale-' . $lang"
                :title="$lang"
            />
        @endforeach
    </x-slot:nav>

    <x-slot:content>
        @foreach(supported_languages_keys() as $lang)
            <x-admin.tabs.pane :is-active="$loop->first" :id="'description-locale-' . $lang">
                <div class="d-flex flex-column gap-4">
                    <!-- description -->
                    <x-admin.field.wysiwyg
                        :name="'description['. $lang .']'"
                        :placeholder="__('admin.description')"
                        :value="old('description.' . $lang, isset($project) ? $project->getTranslation('description', $lang) : null)"
                        :height="300"
                        :buttons="'blockquote|list|image|video'"
                    />
                </div>
            </x-admin.tabs.pane>
        @endforeach
    </x-slot:content>
</x-admin.tabs.wrapper>
