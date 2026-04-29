@extends('public.layouts.base')

@section('content')
    <section class="container inner-page-head contacts-page-head">
        <h1 class="inner-page-title">{{ $contacts->title }}</h1>
    </section>

    <section class="contacts">
        <img
            @php
                $heroImage = $contacts->hasMedia('hero-image') ? $contacts->getFirstMedia('hero-image') : '/img/default.svg';
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
            alt="Image"
            class="img-fluid contacts-bg-image"
        >

        <div class="container contacts-container">
            @php
                $whatsappText = data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'whatsapp_text');
            @endphp

            @if(!empty(trim(strip_tags((string) $whatsappText))))
                <div class="contacts-whatsapp-intro">
                    {!! $whatsappText !!}
                </div>
            @endif

            @include('public.fragments.whatsapp-button', ['variant' => 'contacts', 'label' => 'WhatsApp'])

            <div class="contacts-links">
                @foreach(data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'phones', []) as $phone)
                    <p>
                        <a href="tel:{{ get_only_numbers($phone) }}" class="base-link">{{ $phone }}</a>
                    </p>
                @endforeach

                @foreach(data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'emails', []) as $email)
                    <p>
                        <a href="mailto:{{ $email }}" class="base-link">{{ $email }}</a>
                    </p>
                @endforeach
            </div>

            <address>{{ data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'address') }}</address>

            @include('public.fragments.socials')
        </div>
    </section>

    @include('public.sections.contact-section')
@endsection
