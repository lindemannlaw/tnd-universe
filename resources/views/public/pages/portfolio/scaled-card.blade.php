<div class="project-scaled-card">
    <picture class="project-scaled-card-picture">
        <img
            @php
$projectImage = $project->hasMedia($project->mediaHero) ? $project->getFirstMedia($project->mediaHero) : '/img/default.svg';
            $projectImageSizes = [
                'md' => is_object($projectImage) ? $projectImage->getUrl('md-webp') : $projectImage,
                'lg' => is_object($projectImage) ? $projectImage->getUrl('lg-webp') : $projectImage
            ]; @endphp
            srcset="
            {{ $projectImageSizes['md'] }},
            {{ $projectImageSizes['lg'] }} 1.5x,
            {{ $projectImageSizes['lg'] }} 2x
        "
            src="{{ $projectImageSizes['lg'] }}"
            alt="{{ $project->title }}"
            class="img-cover project-scaled-card-image"
            loading="lazy"
        >
    </picture>

    <h4 class="project-scaled-card-title">{{ $project->title }}</h4>

    @if ($project->area || $project->location)
        <div class="project-scaled-card-info">
            @if ($project->area)
                <p>{{ $project->area }} m²</p>

                @if ($project->location)
                    <span class="project-scaled-card-info-dot"></span>
                @endif
            @endif

            @if ($project->location)
                <p>{{ $project->location }}</p>
            @endif
        </div>
    @endif

    <a
        href="{{ portfolio_project_url($project) }}"
        class="project-scaled-card-link"
    ></a>
</div>
