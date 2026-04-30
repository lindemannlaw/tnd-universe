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
            <article class="formatted-text project-description {{ $project->hasAnyPropertyDetail() ? 'has-details' : 'no-details' }}">
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

                        @if ($project->inquiry_button_active)
                            @php
                                $btnText = data_get(
                                    $project->getTranslation('property_details', app()->getLocale()),
                                    'inquiry_button_text'
                                );
                            @endphp
                            @if ($btnText)
                                <a href="{{ static_page_url('contacts') }}" class="btn btn-submit project-inquiry-btn">
                                    {{ $btnText }}
                                </a>
                            @endif
                        @endif
                    </div>
                @endif

                @php
                    $descriptionBlocks = $project->getTranslation('description_blocks', app()->getLocale(), false) ?: [];

                    // Fallback 1: use EN (master) blocks if current locale has none
                    if (empty($descriptionBlocks)) {
                        $descriptionBlocks = $project->getTranslation('description_blocks', config('app.fallback_locale', 'en'), false) ?: [];
                    }

                    // Fallback 2: plain description text as a single text block
                    if (empty($descriptionBlocks)) {
                        $descriptionBlocks = [[
                            'type' => 'text',
                            'content' => $project->description,
                        ]];
                    }

                    $projectMediaGroup = 'project-media-' . $project->id;
                    $floatingImageUrls = [];
                    $textBlockContents = [];
                    $lightboxImageUrls = [];
                    $lightboxVideoUrls = [];

                    foreach ($descriptionBlocks as $block) {
                        if (data_get($block, 'type') === 'floating_gallery') {
                            foreach ((data_get($block, 'items') ?: []) as $item) {
                                $itemImage = data_get($item, 'image');

                                if (filled($itemImage)) {
                                    $floatingImageUrls[] = $itemImage;
                                }
                            }
                        }

                        if (data_get($block, 'type') === 'text' && filled(data_get($block, 'content'))) {
                            $textBlockContents[] = (string)data_get($block, 'content');
                        }
                    }

                    if ($project->hasMedia($project->mediaHero)) {
                        $heroImage = $project->getFirstMedia($project->mediaHero);
                        $lightboxImageUrls[] = $heroImage?->getUrl('xl-webp') ?: $heroImage?->getUrl();
                    }

                    foreach ($project->getMedia($project->mediaGallery) as $galleryImage) {
                        $lightboxImageUrls[] = $galleryImage->getUrl('xl-webp') ?: $galleryImage->getUrl();
                    }

                    $lightboxImageUrls = array_merge($lightboxImageUrls, $floatingImageUrls);

                    foreach ($textBlockContents as $html) {
                        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $imageMatches);
                        preg_match_all('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $html, $iframeMatches);
                        preg_match_all('/<video[^>]+src=["\']([^"\']+)["\']/i', $html, $videoMatches);
                        preg_match_all('/<video[^>]*>.*?<source[^>]+src=["\']([^"\']+)["\']/is', $html, $videoSourceMatches);

                        $lightboxImageUrls = array_merge($lightboxImageUrls, $imageMatches[1] ?? []);
                        $lightboxVideoUrls = array_merge(
                            $lightboxVideoUrls,
                            $iframeMatches[1] ?? [],
                            $videoMatches[1] ?? [],
                            $videoSourceMatches[1] ?? []
                        );
                    }

                    $lightboxImageUrls = array_values(array_unique(array_filter($lightboxImageUrls)));
                    $lightboxVideoUrls = array_values(array_unique(array_filter($lightboxVideoUrls)));
                    $hiddenLightboxImageUrls = array_values(array_diff($lightboxImageUrls, $floatingImageUrls));
                @endphp

                @foreach($descriptionBlocks as $blockOrRow)
                    @if(data_get($blockOrRow, 'type') === 'text_column_row')
                        @php
                            $rowPtop    = max(0, min(300, (int)data_get($blockOrRow, 'padding_top', 0)));
                            $rowPbottom = max(0, min(300, (int)data_get($blockOrRow, 'padding_bottom', 0)));
                            $rowItems   = data_get($blockOrRow, 'items', []);
                        @endphp
                        <div class="project-text-columns"
                             style="{{ $rowPtop > 0 ? 'padding-top:' . $rowPtop . 'px;' : '' }}{{ $rowPbottom > 0 ? 'padding-bottom:' . $rowPbottom . 'px;' : '' }}">
                            @foreach($rowItems as $block)
                                @php
                                    $colStart     = max(1, min(12, (int)data_get($block, 'col_start', 1)));
                                    $colSpan      = max(1, min(12, (int)data_get($block, 'col_span', 12)));
                                    $imgUrl       = data_get($block, 'image');
                                    $hasImage     = filled($imgUrl);
                                    $imgAlignment = $hasImage ? (data_get($block, 'image_alignment', 'top')) : 'top';
                                    $rawImgSpan   = data_get($block, 'image_col_span');
                                    $rawTxtSpan   = data_get($block, 'text_col_span');
                                    $imgColSpan   = filled($rawImgSpan) ? max(1, min(12, (int)$rawImgSpan)) : null;
                                    $txtColSpan   = filled($rawTxtSpan) ? max(1, min(12, (int)$rawTxtSpan)) : null;
                                    $headlineColors = [
                                        'emerald-950' => 'var(--color-primary-brand-950-darkest)',
                                        'emerald-900' => 'var(--color-primary-brand-900-darker-silent)',
                                        'emerald-800' => 'var(--color-primary-brand-800-dark)',
                                        'primary'     => 'var(--color-font-primary)',
                                        'gold-bright' => 'var(--color-gold-lighter)',
                                    ];
                                    $hColor = $headlineColors[data_get($block, 'headline_color', 'primary')] ?? 'var(--color-font-primary)';
                                    $hFont  = data_get($block, 'headline_font', 'pangea') === 'nicevar' ? 'font-nicevar' : '';
                                @endphp
                                <div class="project-text-column-item"
                                     style="--col-start: {{ $colStart }}; --col-span: {{ $colSpan }};">
                                    <div class="project-text-column-inner{{ $hasImage ? ' has-image image-' . $imgAlignment : '' }}"
                                         style="{{ $hasImage && $imgColSpan !== null ? '--img-col-span: ' . $imgColSpan . '; ' : '' }}{{ $txtColSpan !== null ? '--text-col-span: ' . $txtColSpan . ';' : '' }}">
                                        @if($hasImage)
                                            <img class="project-text-column-image" src="{{ $imgUrl }}" alt="{{ data_get($block, 'headline', '') }}" loading="lazy">
                                        @endif
                                        <div class="project-text-column-text">
                                            @if(filled(data_get($block, 'headline')))
                                                <h3
                                                    class="{{ trim((data_get($block, 'headline_line') ? 'has-line ' : '') . $hFont) }}"
                                                    style="color: {{ $hColor }}"
                                                >{{ data_get($block, 'headline') }}</h3>
                                            @endif
                                            @if(filled(data_get($block, 'content')))
                                                <div class="project-text-column-content {{ data_get($block, 'content_line') ? 'has-line' : '' }}">
                                                    {!! data_get($block, 'content') !!}
                                                </div>
                                            @endif
                                            @if(filled(data_get($block, 'link_text')) && filled(data_get($block, 'link_url')))
                                                <a href="{{ data_get($block, 'link_url') }}" class="project-text-column-link">
                                                    {{ data_get($block, 'link_text') }}
                                                    <svg width="32" height="10" viewBox="0 0 32 10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                        <path d="M0 5H30M30 5L26 1M30 5L26 9" stroke="currentColor" stroke-width="1.2"/>
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif(data_get($blockOrRow, 'type') === 'floating_gallery')
                        @php $block = $blockOrRow; @endphp
                        @php
                            $fptop    = max(0, min(300, (int)data_get($block, 'padding_top', 0)));
                            $fpbottom = max(0, min(300, (int)data_get($block, 'padding_bottom', 0)));
                        @endphp
                        <div class="project-floating-gallery" style="{{ $fptop > 0 ? 'padding-top:' . $fptop . 'px;' : '' }}{{ $fpbottom > 0 ? 'padding-bottom:' . $fpbottom . 'px;' : '' }}">
                            @foreach((data_get($block, 'items') ?: []) as $item)
                                @if(!filled(data_get($item, 'image')))
                                    @continue
                                @endif
                                @php
                                    $headline = trim((string)data_get($item, 'headline', ''));
                                    $subhead = trim((string)data_get($item, 'subhead', ''));
                                    $resolvedHeadline = $headline ?: $project->title;
                                    $resolvedSubhead = filled($subhead) ? e($subhead) : '&nbsp;';
                                    $captionMarkup = '<div class="fancybox-caption-text"><div class="fancybox-caption-headline">' . e($resolvedHeadline) . '</div><div class="fancybox-caption-subhead">' . $resolvedSubhead . '</div></div>';
                                @endphp
                                <figure
                                    class="project-floating-gallery-item"
                                    style="--col-start: {{ max(1, min(12, (int)data_get($item, 'col_start', 1))) }}; --col-span: {{ max(1, min(12, (int)data_get($item, 'col_span', 12))) }};"
                                >
                                    <a
                                        href="{{ data_get($item, 'image') }}"
                                        data-fancybox="{{ $projectMediaGroup }}"
                                        data-caption='{!! $captionMarkup !!}'
                                        class="project-floating-gallery-image-link"
                                    >
                                        <img
                                            src="{{ data_get($item, 'image') }}"
                                            alt="{{ data_get($item, 'headline', $project->title) }}"
                                            loading="lazy"
                                        >
                                    </a>

                                    @if(filled(data_get($item, 'headline')) || filled(data_get($item, 'subhead')))
                                        <figcaption class="project-floating-gallery-caption">
                                            @if(filled(data_get($item, 'headline')))
                                                <strong>{{ data_get($item, 'headline') }}</strong>
                                            @endif
                                            @if(filled(data_get($item, 'subhead')))
                                                <span>{{ data_get($item, 'subhead') }}</span>
                                            @endif
                                        </figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    @elseif(data_get($blockOrRow, 'type') === 'video')
                        @php
                            $vPtop    = max(0, min(300, (int)data_get($blockOrRow, 'padding_top', 0)));
                            $vPbottom = max(0, min(300, (int)data_get($blockOrRow, 'padding_bottom', 0)));
                            $vColStart = max(1, min(12, (int)data_get($blockOrRow, 'col_start', 1)));
                            $vColSpan  = max(1, min(12, (int)data_get($blockOrRow, 'col_span', 12)));
                            $videoSource = data_get($blockOrRow, 'video_source', 'upload');
                            $videoFile   = data_get($blockOrRow, 'video');
                            $videoUrl    = data_get($blockOrRow, 'video_url');
                            $headlineColors = [
                                'emerald-950' => 'var(--color-primary-brand-950-darkest)',
                                'emerald-900' => 'var(--color-primary-brand-900-darker-silent)',
                                'emerald-800' => 'var(--color-primary-brand-800-dark)',
                                'primary'     => 'var(--color-font-primary)',
                                'gold-bright' => 'var(--color-gold-lighter)',
                            ];
                            $vhColor = $headlineColors[data_get($blockOrRow, 'headline_color', 'primary')] ?? 'var(--color-font-primary)';
                            $vhFont  = data_get($blockOrRow, 'headline_font', 'pangea') === 'nicevar' ? 'font-nicevar' : '';
                        @endphp
                        <div class="project-video-block"
                             style="--col-start: {{ $vColStart }}; --col-span: {{ $vColSpan }};{{ $vPtop > 0 ? ' padding-top:' . $vPtop . 'px;' : '' }}{{ $vPbottom > 0 ? ' padding-bottom:' . $vPbottom . 'px;' : '' }}">
                            @if($videoSource === 'url' && filled($videoUrl))
                                @php
                                    // Convert YouTube/Vimeo URLs to embed format
                                    $embedVideoUrl = $videoUrl;
                                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $ytMatch)) {
                                        $embedVideoUrl = 'https://www.youtube.com/embed/' . $ytMatch[1];
                                    } elseif (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $vimeoMatch)) {
                                        $embedVideoUrl = 'https://player.vimeo.com/video/' . $vimeoMatch[1];
                                    }
                                @endphp
                                <div class="project-video-responsive">
                                    <iframe src="{{ $embedVideoUrl }}" frameborder="0" allowfullscreen allow="autoplay; encrypted-media" loading="lazy"></iframe>
                                </div>
                            @elseif($videoSource === 'upload' && filled($videoFile))
                                <video src="{{ $videoFile }}" controls class="project-video-player" preload="metadata"></video>
                            @endif

                            @if(filled(data_get($blockOrRow, 'headline')))
                                <h3
                                    class="{{ trim((data_get($blockOrRow, 'headline_line') ? 'has-line ' : '') . $vhFont) }}"
                                    style="color: {{ $vhColor }}"
                                >{{ data_get($blockOrRow, 'headline') }}</h3>
                            @endif
                            @if(filled(data_get($blockOrRow, 'content')))
                                <div class="project-video-content {{ data_get($blockOrRow, 'content_line') ? 'has-line' : '' }}">
                                    {!! data_get($blockOrRow, 'content') !!}
                                </div>
                            @endif
                        </div>
                    @elseif(data_get($blockOrRow, 'type') === 'embed')
                        @php
                            $ePtop    = max(0, min(300, (int)data_get($blockOrRow, 'padding_top', 0)));
                            $ePbottom = max(0, min(300, (int)data_get($blockOrRow, 'padding_bottom', 0)));
                            $eColStart = max(1, min(12, (int)data_get($blockOrRow, 'col_start', 1)));
                            $eColSpan  = max(1, min(12, (int)data_get($blockOrRow, 'col_span', 12)));
                            $embedUrl    = data_get($blockOrRow, 'embed_url');
                            $embedHeight = max(100, min(2000, (int)data_get($blockOrRow, 'embed_height', 500)));
                        @endphp
                        @if(filled($embedUrl))
                            <div class="project-embed-block"
                                 style="--col-start: {{ $eColStart }}; --col-span: {{ $eColSpan }};{{ $ePtop > 0 ? ' padding-top:' . $ePtop . 'px;' : '' }}{{ $ePbottom > 0 ? ' padding-bottom:' . $ePbottom . 'px;' : '' }}">
                                <iframe src="{{ $embedUrl }}" width="100%" height="{{ $embedHeight }}" frameborder="0" allowfullscreen allow="autoplay; encrypted-media; xr-spatial-tracking" loading="lazy" style="border: 0;"></iframe>
                            </div>
                        @endif
                    @elseif(data_get($blockOrRow, 'type') === 'numbers')
                        @php
                            $nPtop    = max(0, min(300, (int)data_get($blockOrRow, 'padding_top', 0)));
                            $nPbottom = max(0, min(300, (int)data_get($blockOrRow, 'padding_bottom', 0)));
                            $nHeadlineColSpan = max(1, min(12, (int)data_get($blockOrRow, 'headline_col_span', 12)));
                            $nHeadlineLine = (bool)data_get($blockOrRow, 'headline_line', false);
                            $nHeadlineFont = data_get($blockOrRow, 'headline_font', 'pangea') === 'nicevar' ? 'font-nicevar' : '';
                            $nGridColSpan = max(1, min(12, (int)data_get($blockOrRow, 'grid_col_span', 12)));
                            $nGridColStart = max(1, min(12, (int)data_get($blockOrRow, 'grid_col_start', 1)));
                            $nItems = data_get($blockOrRow, 'items', []);
                            $lineColors = [
                                'emerald-950' => 'var(--color-primary-brand-950-darkest)',
                                'emerald-900' => 'var(--color-primary-brand-900-darker-silent)',
                                'emerald-800' => 'var(--color-primary-brand-800-dark)',
                                'primary'     => 'var(--color-font-primary)',
                                'gold-bright' => 'var(--color-gold-lighter)',
                            ];
                        @endphp
                        <div class="project-numbers"
                             style="--grid-col-span: {{ $nGridColSpan }}; --grid-col-start: {{ $nGridColStart }};{{ $nPtop > 0 ? ' padding-top:' . $nPtop . 'px;' : '' }}{{ $nPbottom > 0 ? ' padding-bottom:' . $nPbottom . 'px;' : '' }}">
                            @if(filled(data_get($blockOrRow, 'headline')))
                                <h2 class="project-numbers-headline{{ $nHeadlineLine ? ' has-line' : '' }}{{ $nHeadlineFont ? ' ' . $nHeadlineFont : '' }}"
                                    style="--col-span: {{ $nHeadlineColSpan }};">
                                    {{ data_get($blockOrRow, 'headline') }}
                                </h2>
                            @endif
                            <div class="project-numbers-grid">
                                @foreach($nItems as $item)
                                    @php
                                        $itemLineColor = $lineColors[data_get($item, 'line_color', 'emerald-900')] ?? 'var(--color-primary-brand-900-darker-silent)';
                                        $hasNumber = filled(data_get($item, 'number'));
                                        $fullWidthLine = (bool)data_get($item, 'full_width_line', false);
                                        $itemColSpan = max(1, min(12, (int)data_get($item, 'item_col_span', 3)));
                                    @endphp
                                    <div class="project-numbers-item{{ !$hasNumber ? ' no-number' : '' }}{{ $fullWidthLine ? ' has-full-line' : '' }}"
                                         style="--item-col-span: {{ $itemColSpan }};">
                                        <span class="project-numbers-item-title">{{ data_get($item, 'title') }}</span>
                                        <hr class="project-numbers-item-line" style="background: {{ $itemLineColor }};">
                                        @if($hasNumber)
                                            <span class="project-numbers-item-number">{{ data_get($item, 'number') }}</span>
                                        @endif
                                        @if(filled(data_get($item, 'subline')))
                                            <span class="project-numbers-item-subline">{{ data_get($item, 'subline') }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="project-description-content">
                            {!! data_get($blockOrRow, 'content') !!}
                        </div>
                    @endif
                @endforeach

                @if(!empty($hiddenLightboxImageUrls) || !empty($lightboxVideoUrls))
                    @php
                        $fallbackCaptionMarkup = '<div class="fancybox-caption-text"><div class="fancybox-caption-headline">' . e($project->title) . '</div><div class="fancybox-caption-subhead">&nbsp;</div></div>';
                    @endphp
                    <div class="d-none">
                        @foreach($hiddenLightboxImageUrls as $imageUrl)
                            <a
                                href="{{ $imageUrl }}"
                                data-fancybox="{{ $projectMediaGroup }}"
                                data-caption='{!! $fallbackCaptionMarkup !!}'
                            ></a>
                        @endforeach

                        @foreach($lightboxVideoUrls as $videoUrl)
                            <a
                                href="{{ $videoUrl }}"
                                data-fancybox="{{ $projectMediaGroup }}"
                                data-type="iframe"
                                data-caption='{!! $fallbackCaptionMarkup !!}'
                            ></a>
                        @endforeach
                    </div>
                @endif
            </article>

            @if ($project->hasMedia($project->mediaFiles))
                <div class="project-files file-download-list">
                    @foreach ($project->getMedia($project->mediaFiles) as $file)
                        <a
                            href="{{ $file->getUrl() }}"
                            class="file-download"
                            download="{{ $file->custom_properties['name'] }}"
                        >
                            <span class="file-download-name">{{ $file->custom_properties['name'] }}</span>
                            <span class="file-download-size">{{ number_format($file->size / (1024 * 1024), 2) }}
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
