<div class="bg-img-cover project-card" style="background-image: url({{ $project->hasMedia($project->mediaHero) ? $project->getFirstMediaUrl($project->mediaHero, 'md-webp') : '/img/default-vertical.svg' }});">
            <div class="project-card-content">
                <h5 class="project-card-title">{{ $project->title }}</h5>
                <div class="project-card-description">
                    <p>{{ $project->short_description }}</p>
                </div>
                <div class="project-card-tags">
                    @foreach($project['tags'] as $tag)
                        <p>{{ $tag }}</p>
                    @endforeach
                </div>
                <a href="{{ portfolio_project_url($project) }}" class="project-card-link"></a>
            </div>
</div>