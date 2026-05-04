@extends('admin.layouts.base')

@section('title', __('admin.managers') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel
        :title="__('admin.managers')"
    >
        <x-admin.button
            :href="route('admin.manager.create')"
            :title="__('admin.create')"
            :iconName="'plus-circle'"
        />
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container>
        <div class="grid gap-3">
            @foreach($managers as $manager)
                <!-- card -->
                <div class="d-flex flex-column gap-3 border border-dark border-opacity-25 rounded shadow-md overflow-hidden hover-container text-center p-3 g-col-12 g-col-sm-6 g-col-md-4 g-col-lg-3 g-col-hd-2">
                    <x-admin.picture
                        class="rounded-circle overflow-hidden w-50 mx-auto border border-dark border-opacity-25"
                        :src="$manager->hasMedia($manager->mediaCollection) ? $manager->getFirstMediaUrl($manager->mediaCollection, 'avatar') : null"
                        :ratio="'1x1'"
                    />

                    <h5 class="m-0 text-center">{{ $manager->first_name . ' ' . $manager->last_name }}</h5>

                    <div class="d-flex flex-column gap-1 lh-1">
                        <div class="fw-semibold">{{ __('titles.role') }}:</div>

                        @foreach($manager->getRolesLabel() as $key => $label)
                            <div class="text-gray">{{ __('roles.' . $key) }}</div>
                        @endforeach

                        <div class="mt-2 fw-semibold">{{ __('titles.lastActivity') }}:</div>

                        <div class="text-gray">{{ $manager->last_activity_at ? $manager->last_activity_at->format('d.m.Y / H:i:s') : __('titles.noActivityYet') }}</div>
                    </div>

                    <x-admin.card.action :editHref="route('admin.manager.edit', $manager->id)" :deleteId="'#managerDelete-' . $manager->id" />
                </div>
            @endforeach
        </div>

        @if($managers->isEmpty())
            <x-admin.empty-message :message="__('admin.empty_list')"/>
        @endif
    </x-admin.container>

    @foreach($managers as $manager)
        <x-admin.confirm-delete
            :id="'managerDelete-' . $manager->id"
            :title="__('texts.confirmDeleteManager')"
            :subtitle="$manager->first_name . ' ' . $manager->last_name"
            :url="route('admin.manager.delete', $manager->id)"
        />
    @endforeach
@endsection
