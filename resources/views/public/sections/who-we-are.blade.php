@if (isset($whoWeAreSection))
    <section class="who-we-are">
        <div class="container who-we-are-container">
            <div class="who-we-are-pictures">
                <img
                    @php
$backImage = $whoWeAreSection->hasMedia('back-image') ? $whoWeAreSection->getFirstMedia('back-image') : '/img/default.svg';
                        $backImageSizes = [
                            'sm' => is_object($backImage) ? $backImage->getUrl('md-webp') : $backImage,
                            'md' => is_object($backImage) ? $backImage->getUrl('lg-webp') : $backImage
                        ]; @endphp
                    srcset="
                        {{ $backImageSizes['sm'] }},
                        {{ $backImageSizes['md'] }} 1.5x,
                        {{ $backImageSizes['md'] }} 2x
                    "
                    src="{{ $backImageSizes['md'] }}"
                    alt="Image"
                    class="img-cover who-we-are-image-back"
                    loading="lazy"
                >

                <img
                    @php
$frontImage = $whoWeAreSection->hasMedia('front-image') ? $whoWeAreSection->getFirstMedia('front-image') : '/img/default.svg';
                        $frontImageSizes = [
                            'sm' => is_object($frontImage) ? $frontImage->getUrl('md-webp') : $frontImage,
                            'md' => is_object($frontImage) ? $frontImage->getUrl('lg-webp') : $frontImage
                        ]; @endphp
                    srcset="
                        {{ $frontImageSizes['sm'] }},
                        {{ $frontImageSizes['md'] }} 1.5x,
                        {{ $frontImageSizes['md'] }} 2x
                    "
                    src="{{ $frontImageSizes['md'] }}"
                    alt="Image"
                    class="img-cover who-we-are-image-front"
                    loading="lazy"
                >
            </div>

            <div class="who-we-are-body">
                <div class="who-we-are-title">{{ $whoWeAreSection->title }}</div>

                <div class="formatted-text who-we-are-description">{!! data_get($whoWeAreSection->content_data, 'vision') !!}</div>

                <div class="formatted-text who-we-are-description">{!! data_get($whoWeAreSection->content_data, 'mission') !!}</div>
            </div>
        </div>
    </section>
@endif
