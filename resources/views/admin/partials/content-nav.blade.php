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
$singleCls = fn (bool $active): string => 'list-group-item list-group-item-action border-0 py-1 px-3 small'
    . ($active ? ' active' : '');

$sectionIdx = 0;
$hdrClass = 'list-group-item border-0 py-1 px-3 text-uppercase fw-semibold text-muted';
$hdrStyle = function () use (&$sectionIdx): string {
    $style = 'font-size:.7rem; letter-spacing:.05em; background: var(--bs-light);'
           . ($sectionIdx > 0 ? ' border-top: 1px solid rgba(0,0,0,.125);' : '');
    $sectionIdx++;
    return $style;
};
@endphp

<div class="{{ ($inner ?? false) ? 'list-group list-group-flush' : 'list-group list-group-flush border rounded' }}" style="font-size: .85rem;">

    {{-- All / clear filter --}}
    @php $allLink = route($route, $extraParams ?? []); @endphp
    <a href="{{ $allLink }}"
       class="{{ 'list-group-item list-group-item-action border-0 py-1 px-3 small fw-semibold' . ($typeFilter === 'all' ? ' active' : '') }}">
        Alle
    </a>

    {{-- Home --}}
    @if(isset($navPages['home']))
        <a href="{{ $link('page', $navPages['home']) }}"
           class="{{ $singleCls($isActive('page', $navPages['home'])) }}">
            Home Page
        </a>
    @endif

    {{-- About --}}
    @if($show('leader'))
        <div class="{{ $hdrClass }}" style="{{ $hdrStyle() }}">
            About
        </div>
        @if(isset($navPages['about']))
            <a href="{{ $link('page', $navPages['about']) }}"
               class="{{ $cls($isActive('page', $navPages['about'])) }}">
                Page
            </a>
        @endif
        <a href="{{ $link('leader') }}"
           class="{{ $cls($isActive('leader')) }}">
            Leaders
        </a>
    @elseif(isset($navPages['about']))
        <a href="{{ $link('page', $navPages['about']) }}"
           class="{{ $singleCls($isActive('page', $navPages['about'])) }}">
            About Page
        </a>
    @endif

    {{-- Services --}}
    <div class="{{ $hdrClass }}" style="{{ $hdrStyle() }}">
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
    <div class="{{ $hdrClass }}" style="{{ $hdrStyle() }}">
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
    <div class="{{ $hdrClass }}" style="{{ $hdrStyle() }}">
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
        <a href="{{ $link('page', $navPages['contacts']) }}"
           class="{{ $singleCls($isActive('page', $navPages['contacts'])) }}">
            Contacts Page
        </a>
    @endif

    {{-- Sections (Translations only) --}}
    @if($show('site_section'))
        <div class="{{ $hdrClass }}" style="{{ $hdrStyle() }}">
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
        <a href="{{ $link('page', $navPages['imprint']) }}"
           class="{{ $singleCls($isActive('page', $navPages['imprint'])) }}">
            Imprint Page
        </a>
    @endif

    {{-- Privacy notice --}}
    @if(isset($navPages['privacy-notice']))
        <a href="{{ $link('page', $navPages['privacy-notice']) }}"
           class="{{ $singleCls($isActive('page', $navPages['privacy-notice'])) }}">
            Privacy Notice Page
        </a>
    @endif

    {{-- Terms of use --}}
    @if(isset($navPages['terms-of-use']))
        <a href="{{ $link('page', $navPages['terms-of-use']) }}"
           class="{{ $singleCls($isActive('page', $navPages['terms-of-use'])) }}">
            Terms of Use Page
        </a>
    @endif

    {{-- UI Strings (Translations dashboard only) --}}
    @if($dashboard === 'translations')
        <div class="{{ $hdrClass }}" style="{{ $hdrStyle() }}">
            UI Strings
        </div>
        <a href="{{ $link('ui_string') }}"
           class="{{ $cls($isActive('ui_string')) }}">
            base.php
        </a>
    @endif

</div>
