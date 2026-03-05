@if ($serviceCategories)
    <section class="services @if (Route::is('public.home')) services--home @elseif (Route::is('public.services')) services--expertise @endif">
        @if (Route::is('public.home'))
            <div class="container services-head">
                <h4 class="services-head-title">{{ __('public.we_offer') }}</h4>
            </div>
        @endif

        @if (Route::is('public.home') || Route::is('public.services'))
            <div class="container services-wrapper">
                <nav class="services-overview" aria-label="{{ __('public.expertise_overview') }}">
                    <ul class="services-overview-list">
                        @foreach ($serviceCategories as $category)
                            <li>
                                <a
                                    href="{{ Route::is('public.services') ? '#expertise-' . $category->slug : route('public.services') . '#expertise-' . $category->slug }}"
                                    class="services-overview-link"
                                >{{ $category->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="services-container">
                    @foreach ($serviceCategories as $category)
                        <div class="services-row" @if (Route::is('public.services')) id="expertise-{{ $category->slug }}" @endif>
                            <div class="services-row-body">
                                <h3 class="services-row-title">{{ $category->name }}</h3>
                                <ul>
                                    @foreach ($category->services as $service)
                                        <li><a
                                                href="{{ route('public.services.post', $service->slug) }}"
                                                class="primary-link"
                                            >{{ $service->title }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                            <img
                                @php
$categoryImage = $category->hasMedia($category->mediaHero) ? $category->getFirstMedia($category->mediaHero) : '/img/default.svg';
                                    $categoryImageSizes = [
                                        'md' => is_object($categoryImage) ? $categoryImage->getUrl('md-webp') : $categoryImage,
                                        'lg' => is_object($categoryImage) ? $categoryImage->getUrl('lg-webp') : $categoryImage
                                    ]; @endphp
                                srcset="
                                    {{ $categoryImageSizes['md'] }},
                                    {{ $categoryImageSizes['lg'] }} 1.5x,
                                    {{ $categoryImageSizes['lg'] }} 2x
                                "
                                src="{{ $categoryImageSizes['lg'] }}"
                                alt="{{ $category->name }}"
                                class="img services-row-image"
                                loading="lazy"
                            >
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="container services-container">
                @foreach ($serviceCategories as $category)
                    <div class="services-row">
                        <div class="services-row-body">
                            <h3 class="services-row-title">{{ $category->name }}</h3>
                            <ul>
                                @foreach ($category->services as $service)
                                    <li><a
                                            href="{{ route('public.services.post', $service->slug) }}"
                                            class="primary-link"
                                        >{{ $service->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                        <img
                            @php
$categoryImage = $category->hasMedia($category->mediaHero) ? $category->getFirstMedia($category->mediaHero) : '/img/default.svg';
                                $categoryImageSizes = [
                                    'md' => is_object($categoryImage) ? $categoryImage->getUrl('md-webp') : $categoryImage,
                                    'lg' => is_object($categoryImage) ? $categoryImage->getUrl('lg-webp') : $categoryImage
                                ]; @endphp
                            srcset="
                                {{ $categoryImageSizes['md'] }},
                                {{ $categoryImageSizes['lg'] }} 1.5x,
                                {{ $categoryImageSizes['lg'] }} 2x
                            "
                            src="{{ $categoryImageSizes['lg'] }}"
                            alt="{{ $category->name }}"
                            class="img services-row-image"
                            loading="lazy"
                        >
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endif
