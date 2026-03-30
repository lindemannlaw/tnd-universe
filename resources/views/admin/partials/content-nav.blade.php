{{--
    Content navigation sidebar for Translations and SEO/GEO dashboards.

    Required variables:
        $dashboard    – 'translations' | 'seo-geo'
        $typeFilter   – currently active type string (e.g. 'page', 'project', 'all')
        $idFilter     – currently active record id (or null)
        $navPages     – array of slug → id  (Page records)
        $navSections  – array of slug → id  (SiteSection records)
        $extraParams  – additional query params to preserve in every link (e.g. lang, status)
--}}
@php
$route = $dashboard === 'translations' ? 'admin.translations.index' : 'admin.seo-geo.index';

$link = function (string $type, ?int $id = null) use ($route, $extraParams) {
    $params = array_merge($extraParams ?? [], ['type' => $type]);
    if ($id !== null) $params['id'] = $id;
    return route($route, $params);
};

$isActive = function (string $type, ?int $id = null) use ($typeFilter, $idFilter): bool {
    if ($typeFilter !== $type) return false;
    if ($id !== null) return (string) $idFilter === (string) $id;
    return $idFilter === null || $idFilter === '';
};

// For SEO/GEO dashboard, leaders and site_sections have no SEO → skip them
$hasSeo = fn (string $type) => !in_array($type, ['leader', 'site_section'], true);
$show   = fn (string $type) => $dashboard === 'translations' || $hasSeo($type);

$cls = fn (bool $active): string => 'list-group-item list-group-item-action border-0 py-1 small'
    . ($active ? ' active' : '')
    . ' ps-4';
@endphp

<div class="list-group list-group-flush border rounded" style="font-size: .85rem;">

    {{-- About --}}
    <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
        About
    </div>
    @if(isset($navPages['about']))
        <a href="{{ $link('page', $navPages['about']) }}"
           class="{{ $cls($isActive('page', $navPages['about'])) }}">
            Page
        </a>
    @endif
    @if($show('leader'))
        <a href="{{ $link('leader') }}"
           class="{{ $cls($isActive('leader')) }}">
            Leaders
        </a>
    @endif

    {{-- Services --}}
    <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
        Services
    </div>
    @if(isset($navPages['services']))
        <a href="{{ $link('page', $navPages['services']) }}"
           class="{{ $cls($isActive('page', $navPages['services'])) }}">
            Page
        </a>
    @endif
    <a href="{{ $link('service_category') }}"
       class="{{ $cls($isActive('service_category')) }}">
        Categories
    </a>
    <a href="{{ $link('service') }}"
       class="{{ $cls($isActive('service')) }}">
        Services
    </a>

    {{-- Portfolio --}}
    <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
        Portfolio
    </div>
    @if(isset($navPages['portfolio']))
        <a href="{{ $link('page', $navPages['portfolio']) }}"
           class="{{ $cls($isActive('page', $navPages['portfolio'])) }}">
            Page
        </a>
    @endif
    <a href="{{ $link('project') }}"
       class="{{ $cls($isActive('project')) }}">
        Projects
    </a>

    {{-- News --}}
    <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
        News
    </div>
    @if(isset($navPages['news']))
        <a href="{{ $link('page', $navPages['news']) }}"
           class="{{ $cls($isActive('page', $navPages['news'])) }}">
            Page
        </a>
    @endif
    <a href="{{ $link('news_category') }}"
       class="{{ $cls($isActive('news_category')) }}">
        Categories
    </a>
    <a href="{{ $link('news_article') }}"
       class="{{ $cls($isActive('news_article')) }}">
        Articles
    </a>

    {{-- Contacts --}}
    @if(isset($navPages['contacts']))
        <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
            Contacts
        </div>
        <a href="{{ $link('page', $navPages['contacts']) }}"
           class="{{ $cls($isActive('page', $navPages['contacts'])) }}">
            Page
        </a>
    @endif

    {{-- Sections (Translations only) --}}
    @if($show('site_section'))
        <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
            Sections
        </div>
        @if(isset($navSections['who-we-are']))
            <a href="{{ $link('site_section', $navSections['who-we-are']) }}"
               class="{{ $cls($isActive('site_section', $navSections['who-we-are'])) }}">
                Who we are
            </a>
        @endif
        @if(isset($navSections['contact-us']))
            <a href="{{ $link('site_section', $navSections['contact-us']) }}"
               class="{{ $cls($isActive('site_section', $navSections['contact-us'])) }}">
                Contact us
            </a>
        @endif
    @endif

    {{-- Imprint --}}
    @if(isset($navPages['imprint']))
        <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
            Imprint
        </div>
        <a href="{{ $link('page', $navPages['imprint']) }}"
           class="{{ $cls($isActive('page', $navPages['imprint'])) }}">
            Page
        </a>
    @endif

    {{-- Privacy notice --}}
    @if(isset($navPages['privacy-notice']))
        <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
            Privacy notice
        </div>
        <a href="{{ $link('page', $navPages['privacy-notice']) }}"
           class="{{ $cls($isActive('page', $navPages['privacy-notice'])) }}">
            Page
        </a>
    @endif

    {{-- Terms of use --}}
    @if(isset($navPages['terms-of-use']))
        <div class="list-group-item py-1 px-3 text-uppercase fw-semibold text-muted" style="font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);">
            Terms of use
        </div>
        <a href="{{ $link('page', $navPages['terms-of-use']) }}"
           class="{{ $cls($isActive('page', $navPages['terms-of-use'])) }}">
            Page
        </a>
    @endif

</div>
