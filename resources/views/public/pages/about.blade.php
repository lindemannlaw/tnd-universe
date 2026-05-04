@extends('public.layouts.base')

@section('content')
    <section class="container about-hero">
        <p class="about-hero-subtitle">{{ $page->title }}</p>
        <h1 class="about-hero-title">{{ data_get($page->content_data, 'subtitle') }}</h1>

        <div class="about-hero-picture">
            <img
                @php
                    $heroImage = $page->hasMedia('hero-image') ? $page->getFirstMedia('hero-image') : '/img/default.svg';
                    $heroImageSizes = [
                        'lg' => is_object($heroImage) ? $heroImage->getUrl('lg-webp') : $heroImage,
                        'hd' => is_object($heroImage) ? $heroImage->getUrl('hd-webp') : $heroImage
                    ];
                @endphp

                srcset="
                    {{ $heroImageSizes['lg'] }},
                    {{ $heroImageSizes['hd'] }} 1.5x,
                    {{ $heroImageSizes['hd'] }} 2x
                "
                src="{{ $heroImageSizes['hd'] }}"
                alt="build"
                class="img-fluid"
                loading="lazy"
            >
        </div>

        <p class="about-hero-description">{{ data_get($page->content_data, 'description') }}</p>
    </section>

    @include('public.sections.who-we-are')

    <section class="container about-leaders">
        @foreach($leaders as $leader)
            <div class="leader-card">
                <div class="leader-card-picture">
                    <x-public.icon.building-outline/>
                    <img
                        @php
                            $leaderPhoto = $leader->hasMedia($leader->mediaPhoto) ? $leader->getFirstMedia($leader->mediaPhoto) : '/img/default.svg';
                            $leaderPhotoSizes = [
                                'md' => is_object($leaderPhoto) ? $leaderPhoto->getUrl('md-webp') : $leaderPhoto,
                                'lg' => is_object($leaderPhoto) ? $leaderPhoto->getUrl('lg-webp') : $leaderPhoto
                            ];
                        @endphp

                        srcset="
                            {{ $leaderPhotoSizes['md'] }},
                            {{ $leaderPhotoSizes['lg'] }} 1.5x,
                            {{ $leaderPhotoSizes['lg'] }} 2x
                        "
                        src="{{ $leaderPhotoSizes['lg'] }}"
                        alt="image"
                        class="img-fluid leader-card-photo"
                    >
                </div>

                <div class="leader-card-body">
                    <h3 class="leader-card-name">{{ $leader->name }}</h3>
                    <div class="leader-card-position">{{ $leader->position }}</div>
                    <ul class="leader-card-info">
                        @foreach($leader->info as $item)
                            <li>
                                <p>{{ trim($item['head']) }}</p>
                                <p>{{ $item['description'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                    @if($leader->hasMedia($leader->mediaResume))
                        @php
                            $resume = $leader->getFirstMedia($leader->mediaResume);
                        @endphp
                        <a href="{{ $resume->getUrl() }}" class="primary-link leader-card-download-link" download="{{ $leader->getTranslation('name', current_locale()) . '-' . __('public.resume') }}">{{ __('public.download_resume') }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </section>

    @include('public.sections.contact-section')
@endsection
