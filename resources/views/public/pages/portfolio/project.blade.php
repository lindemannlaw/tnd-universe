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

                @php
                    $descriptionHtml = (string) ($project->description ?? '');
                    $descriptionBlocks = [];
                    $useProjectBlocks = false;

                    if ($descriptionHtml !== '') {
                        if (preg_match('/<div\s+[^>]*class="[^"]*project-block[^"]*"[^>]*data-align="(left|right)"[^>]*>/i', $descriptionHtml)) {
                            $dom = new DOMDocument();
                            libxml_use_internal_errors(true);
                            $dom->loadHTML('<?xml encoding="UTF-8"><div id="root">' . $descriptionHtml . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                            libxml_clear_errors();
                            $xpath = new DOMXPath($dom);
                            $blocks = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " project-block ") and @data-align]');

                            foreach ($blocks as $block) {
                                $align = strtolower($block->getAttribute('data-align'));
                                if (!in_array($align, ['left', 'right'], true)) {
                                    $align = 'left';
                                }
                                $innerHtml = '';
                                foreach ($block->childNodes as $child) {
                                    $innerHtml .= $dom->saveHTML($child);
                                }
                                $descriptionBlocks[] = ['align' => $align, 'content' => trim($innerHtml)];
                            }
                            $useProjectBlocks = !empty($descriptionBlocks);
                        }

                        if (!$useProjectBlocks) {
                            $segments = preg_split(
                                '/(<h2\b[^>]*>.*?<\/h2>)/is',
                                $descriptionHtml,
                                -1,
                                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
                            );
                            $currentBlock = '';

                            foreach ($segments as $segment) {
                                if (preg_match('/^<h2\b/i', trim($segment))) {
                                    if (trim($currentBlock) !== '') {
                                        $descriptionBlocks[] = ['align' => null, 'content' => $currentBlock];
                                    }
                                    $currentBlock = $segment;
                                    continue;
                                }
                                $currentBlock .= $segment;
                            }
                            if (trim($currentBlock) !== '') {
                                $descriptionBlocks[] = ['align' => null, 'content' => $currentBlock];
                            }
                        }
                    }
                @endphp

                @if (!empty($descriptionBlocks))
                    @foreach ($descriptionBlocks as $block)
                        <div class="project-description-block {{ ($block['align'] ?? null) ? 'is-align-' . $block['align'] : ($loop->odd ? 'is-align-left' : 'is-align-right') }}">
                            {!! $block['content'] ?? $block !!}
                        </div>
                    @endforeach
                @else
                    {!! $project->description !!}
                @endif
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
