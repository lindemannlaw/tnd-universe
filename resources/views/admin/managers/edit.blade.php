@extends('admin.layouts.base')

@section('title', __('titles.edit') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel
        :title="__('titles.edit') . ' - ' . $manager->first_name . ' ' . $manager->last_name"
    >
        <x-admin.button
            data-submit-loader
            :type="'submit'"
            :form="'controlForm'"
            :title="__('admin.save')"
            :iconName="'floppy'"
            :withLoader="true"
        />
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container
        :id="'controlForm'"
        :action="route('admin.manager.update', $manager->id)"
        :method="'PATCH'"
    >

        <div class="d-flex flex-wrap gap-4">
            <!-- avatar -->
            <x-admin.field.image
                style="width: 108px;"

                :name="'avatar'"
                :placeholder="__('admin.avatar')"
                :ratio="'1x1'"
                :rounded="'rounded-circle'"
                :required="false"
                :src="old('avatar', $manager->hasMedia($manager->mediaCollection) ? $manager->getFirstMediaUrl($manager->mediaCollection, 'avatar') : null )"
            />

            <div style="min-width: 150px;" class="d-flex flex-column gap-4 flex-auto">
                <!-- first name -->
                <x-admin.field.text
                    :name="'first_name'"
                    :value="old('first_name', $manager->first_name)"
                    :placeholder="__('admin.first_name')"
                />

                <!-- last name -->
                <x-admin.field.text
                    :name="'last_name'"
                    :value="old('last_name', $manager->last_name)"
                    :placeholder="__('admin.last_name')"
                />
            </div>

            <div style="min-width: 280px;" class="d-flex flex-column gap-4 flex-auto">
                <!-- username -->
                <x-admin.field.text
                    :name="'username'"
                    :value="old('username', $manager->username)"
                    :placeholder="__('admin.username')"
                    :pattern="'.{4,}'"
                />

                <!-- email -->
                <x-admin.field.email
                    :name="'email'"
                    :value="old('email', $manager->email)"
                    :placeholder="__('admin.email')"
                />
            </div>

            <div style="min-width: 280px;" class="d-flex flex-column gap-4 flex-auto">
                <!-- password -->
                <x-admin.field.password
                    :name="'password'"
                    :value="old('password')"
                    :placeholder="__('admin.password')"
                    :required="false"
                />

                <!-- roles -->
                <x-admin.field.select
                    class="flex-auto"
                    :name="'role'"
                    :placeholder="__('admin.role')"
                >
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" {{ array_key_exists($key, $manager->getRolesLabel()) ? 'selected' : null }}>{{ __('roles.' . $key) }}</option>
                    @endforeach
                </x-admin.field.select>
            </div>
        </div>

    </x-admin.container>
@endsection
