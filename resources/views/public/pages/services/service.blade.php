@extends('public.layouts.base')

@section('content')
    <section class="container inner-page-head service-head">
        <h1 class="inner-page-title service-head-title">{{ $service->inner_title ?: $service->title }}</h1>
        <p class="service-head-description">{{ $service->description }}</p>
    </section>

    <div class="container service-main-picture">
        <img
            @php
                $serviceHeroImage = $service->hasMedia($service->mediaHero) ? $service->getFirstMedia($service->mediaHero) : '/img/default.svg';
            @endphp

            srcset="
                {{ $serviceHeroImage?->getUrl('lg-webp') ?: $serviceHeroImage }},
                {{ $serviceHeroImage?->getUrl('hd-webp') ?: $serviceHeroImage }} 1.5x,
                {{ $serviceHeroImage?->getUrl('hd-webp') ?: $serviceHeroImage }} 2x
            "
            src="{{ $serviceHeroImage?->getUrl('hd-webp') ?: $serviceHeroImage }}"
            alt="{{ $service->title }}"
            class="img-fluid"
            loading="lazy"
        >
    </div>

    <section class="container service-info">
        <h3 class="service-info-title">{{ data_get($service->details, 'title') }}</h3>

        <ul data-accordion class="service-info-list">
            @foreach(data_get($service->details, 'list', []) as $item)
                <li data-accordion-item class="service-info-card">
                    <div data-accordion-target class="service-info-card-head">
                        <div class="service-info-card-head-icon"></div>
                        <div class="service-info-card-title">{{ $item['title'] }}</div>
                    </div>
                    <div data-accordion-content class="service-info-card-content">
                        <div class="service-info-card-description">{!! $item['description'] !!}</div>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    @include('public.sections.contact-section')

    <div class="container service-more-info">
        <h3 class="service-more-info-title">Learn more about Tax regulations</h3>

        <div class="service-more-info-body">
            <div class="service-more-info-links">
                <a href="#">Tax Compliance</a>
                <a href="#">Tax for Asset Managers and Financial Service Providers</a>
                <a href="#">Tax for Corporate Clients</a>
                <a href="#">Tax for Private Clients</a>
            </div>

            <img src="/img/temp/service-info.webp" alt="image" class="img-cover service-more-info-image" loading="lazy">
        </div>
    </div>
@endsection
