@extends('public.layouts.base')

@section('content')
    <section class="container inner-page-head contacts-page-head">
        <h1 class="inner-page-title">{{ $contacts->title }}</h1>
    </section>

    <section class="contacts">
        <img
            @php
                $heroImage = $contacts->hasAttachedMedia('hero-image') ? $contacts->firstAttachedMedia('hero-image') : '/img/default.svg';
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
                $contentData = $contacts->getTranslation('content_data', config('app.fallback_locale'));
                $whatsappText = data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'whatsapp_text');
                $phone = data_get($contentData, 'phones.0');
                $whatsapp = data_get($contentData, 'whatsapp');
                $email = data_get($contentData, 'emails.0');
            @endphp

            @if(!empty(trim(strip_tags((string) $whatsappText))))
                <div class="contacts-whatsapp-intro">
                    {!! $whatsappText !!}
                </div>
            @endif

            <div class="contacts-links">
                @if(!empty(trim((string) $phone)))
                    <p>
                        <a
                            href="tel:{{ get_only_numbers($phone) }}"
                            class="base-link footer-contact-link footer-contact-link--phone"
                        >{{ $phone }}</a>
                    </p>
                @endif

                @if(!empty(trim((string) $whatsapp)))
                    <p>
                        <a
                            href="https://wa.me/{{ get_only_numbers($whatsapp) }}"
                            target="_blank"
                            rel="noopener"
                            class="base-link footer-contact-link footer-contact-link--whatsapp"
                        >WhatsApp</a>
                    </p>
                @endif

                @if(!empty(trim((string) $email)))
                    <p>
                        <a
                            href="mailto:{{ $email }}"
                            class="base-link footer-contact-link footer-contact-link--mail"
                        >{{ $email }}</a>
                    </p>
                @endif
            </div>

            <address>{{ data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'address') }}</address>

            @include('public.fragments.socials')
        </div>
    </section>

    @include('public.sections.contact-section')
@endsection
