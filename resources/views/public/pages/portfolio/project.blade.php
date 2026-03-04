@extends('public.layouts.base')

@section('content')
    <section class="project-hero">
        <x-swiper.container
            id="project-gallery-carousel"
            class="project-gallery-carousel"
            :withPagination="true"
            :withNavigation="true"
            :withNavbarContainer="true"
        >
            @php
                $defaultImage = '/img/default.svg';
                $gallery = [$defaultImage];

                if ($project->hasMedia($project->mediaGallery)) {
                    $gallery = $project->getMedia($project->mediaGallery);
                } elseif ($project->hasMedia($project->mediaHero)) {
                    $gallery = [$project->getFirstMedia($project->mediaHero)];
                }
            @endphp

            @foreach ($gallery as $image)
                <x-swiper.slide>
                    <img
                        @php
$galleryImageSizes = [
                                'lg' => is_object($image) ? $image->getUrl('lg-webp') : $defaultImage,
                                'hd' => is_object($image) ? $image->getUrl('hd-webp') : $defaultImage,
                                'xl' => is_object($image) ? $image->getUrl('xl-webp') : $defaultImage,
                            ]; @endphp
                        srcset="
                            {{ $galleryImageSizes['lg'] }},
                            {{ $galleryImageSizes['hd'] }} 1.5x,
                            {{ $galleryImageSizes['xl'] }} 2x
                        "
                        src="{{ $galleryImageSizes['lg'] }}"
                        alt="{{ $project->title }} gallery image"
                        class="img-cover"
                        loading="lazy"
                    >
                </x-swiper.slide>
            @endforeach
        </x-swiper.container>

        <div class="container project-hero-container">
            <nav class="breadcrumbs is-breadcrumbs-bg-dark project-hero-breadcrumbs">
                <ul>
                    <li><a href="{{ route('public.portfolio') }}">{{ __('base.portfolio') }}</a></li>
                    <li>{{ $project->title }}</li>
                </ul>
            </nav>

            <div class="project-hero-body">
                <h1 class="project-title">{{ $project->title }}</h1>

                <div class="project-hero-info">{{ $project->location }}</div>
            </div>
        </div>

        <x-public.icon.building-outline class="project-hero-icon" />
    </section>

    <section class="project-content">
        <div class="container project-content-container">
            <article class="formatted-text project-description">
                @if ($project->hasAnyPropertyDetail())
                    <div class="project-property-details">
                        <h2><strong>{{ __('base.property_details') }}</strong></h2>

                        @foreach ($project->getSortedDetails() as $key => $value)
                            @if ($value)
                                <p class="project-property-details-item">
                                    <span>{{ __('base.' . $key) }}</span>
                                    <br>
                                    <span class="h2"><strong>{{ $value }}</strong></span>
                                </p>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="project-description-content">
                    {!! $project->description !!}
                </div>
            </article>

            @if ($project->hasMedia($project->mediaFiles))
                <div class="project-files">
                    @foreach ($project->getMedia($project->mediaFiles) as $file)
                        <a
                            href="{{ $file->getUrl() }}"
                            class="project-file"
                            download="{{ $file->custom_properties['name'] }}"
                        >
                            <span class="project-file-name">{{ $file->custom_properties['name'] }}</span>
                            <span class="project-file-size">{{ number_format($file->size / (1024 * 1024), 2) }}
                                Mb</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($projects->isNotEmpty())
        <section class="container other-projects">
            <div class="other-projects-head">
                <h3 class="other-projects-title">{{ __('public.other_cases') }}</h3>
                <a
                    href="{{ route('public.portfolio') }}"
                    class="btn btn-submit btn-view-all home-portfolio-link"
                >{{ __('base.view_all') }}</a>
            </div>

            <div class="portfolio-projects-simple-cards">
                @foreach ($projects as $project)
                    @include('public.pages.portfolio.simple-card')
                @endforeach
            </div>
        </section>
    @endif
@endsection
