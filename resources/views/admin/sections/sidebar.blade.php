@php
    $user = auth()->user();

    $menu = [
            [
                'title' => __('admin.main'),
                'url' => 'admin.main',
                'icon' => 'buildings',
                'submenu' => [],
                'isCan' => true,
            ],
            [
                'title' => __('admin.home'),
                'url' => 'admin.home.page',
                'icon' => 'house',
                'submenu' => [],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.about'),
                'url' => 'admin.about',
                'icon' => 'people',
                'submenu' => [
                    [
                        'title' => __('admin.page'),
                        'url' => 'admin.about.page',
                        'groupUrl' => 'admin.about.page',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                    [
                        'title' => __('admin.leaders'),
                        'url' => 'admin.about.leaders',
                        'groupUrl' => 'admin.about.leader*',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                ],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.services'),
                'url' => 'admin.services',
                'icon' => 'columns-gap',
                'submenu' => [
                    [
                        'title' => __('admin.page'),
                        'url' => 'admin.services.page',
                        'groupUrl' => 'admin.services.page',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                    [
                        'title' => __('admin.categories'),
                        'url' => 'admin.services.categories',
                        'groupUrl' => 'admin.services.categor*',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                    [
                        'title' => __('admin.services'),
                        'url' => 'admin.services.services',
                        'groupUrl' => 'admin.services.service*',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                ],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.portfolio'),
                'url' => 'admin.portfolio',
                'icon' => 'bounding-box',
                'submenu' => [
                    [
                        'title' => __('admin.page'),
                        'url' => 'admin.portfolio.page',
                        'groupUrl' => 'admin.portfolio.page',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                    [
                        'title' => __('admin.projects'),
                        'url' => 'admin.portfolio.projects',
                        'groupUrl' => 'admin.portfolio.project*',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                ],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.news'),
                'url' => 'admin.news',
                'icon' => 'list-columns',
                'submenu' => [
                    [
                        'title' => __('admin.page'),
                        'url' => 'admin.news.page',
                        'groupUrl' => 'admin.news.page',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                    [
                        'title' => __('admin.categories'),
                        'url' => 'admin.news.categories',
                        'groupUrl' => 'admin.news.categor*',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                    [
                        'title' => __('admin.articles'),
                        'url' => 'admin.news.articles',
                        'groupUrl' => 'admin.news.article*',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                ],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.contacts'),
                'url' => 'admin.contacts.page',
                'icon' => 'person-lines-fill',
                'submenu' => [],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.sections'),
                'url' => 'admin.site-sections',
                'icon' => 'view-list',
                'submenu' => [
                    [
                        'title' => __('admin.who_we_are'),
                        'url' => 'admin.site-sections.who-we-are',
                        'groupUrl' => 'admin.site-sections.who-we-are',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                    [
                        'title' => __('admin.contact_us'),
                        'url' => 'admin.site-sections.contact-us',
                        'groupUrl' => 'admin.site-sections.contact-us',
                        'icon' => null,
                        'submenu' => [],
                        'isCan' => $user->can('all'),
                    ],
                ],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.seo_geo_overview'),
                'url' => 'admin.seo-geo.index',
                'icon' => 'search',
                'submenu' => [],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.translation_check'),
                'url' => 'admin.translations.index',
                'icon' => 'translate',
                'submenu' => [],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.media'),
                'url' => 'admin.media.index',
                'icon' => 'images',
                'submenu' => [],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.imprint'),
                'url' => 'admin.imprint.page',
                'icon' => 'card-text',
                'submenu' => [],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.privacy_notice'),
                'url' => 'admin.privacy-notice.page',
                'icon' => 'card-text',
                'submenu' => [],
                'isCan' => $user->can('all'),
            ],
            [
                'title' => __('admin.terms_of_use'),
                'url' => 'admin.terms-of-use.page',
                'icon' => 'card-text',
                'submenu' => [],
                'isCan' => $user->can('all'),
            ],
        ];
@endphp

<aside id="adminSidebar" class="sidebar d-flex flex-column bg-secondary text-white active">
    <div class="sidebar-header d-flex align-items-center gap-4 lh-sm border-bottom border-white border-opacity-25 shadow-sm py-2 px-3 position-relative">
        <a href="{{ route('public.home') }}" target="_blank" class="position-relative rounded-circle">
            <img src="/img/admin-logo.jpg" alt="Logo" width="36" class="img-fluid p-1 rounded-pill">
        </a>

        <span>{{ config('app.name') }}</span>

        <button id="adminSidebarToggler" type="button" class="d-flex btn btn-secondary position-absolute start-100 px-1 py-2 rounded-0 rounded-end">
            <svg class="bi" width="20" height="20" fill="currentColor">
                <use xlink:href="/img/icons/bootstrap-icons.svg#three-dots-vertical"/>
            </svg>
        </button>
    </div>

    <div data-overlayscrollbars-initialize class="sidebar-body flex-scroll">
        <nav id="accordionMenu" class="accordion sidebar-menu">
            @foreach($menu as $link)
                @if(!$link['isCan'])
                    @continue
                @endif

                @if(empty($link['submenu']))
                    <div class="accordion-item">
                        <a href="{{ $link['url'] !== '#' ? route($link['url']) : '#' }}" class="accordion-button gap-3 {{ request()->routeIs([$link['url'], $link['url'].'.*']) ? 'active' : '' }}">
                            <svg class="bi" width="20" height="20" fill="currentColor">
                                <use xlink:href="/img/icons/bootstrap-icons.svg#<?= $link['icon'] ?? 'circle'; ?>"/>
                            </svg>
                            <span><?= $link['title']; ?></span>
                        </a>
                    </div>
                @else
                    <div class="accordion-item">
                        <button class="accordion-button gap-3 {{ request()->routeIs($link['url'].'.*') ? 'active' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#accordionMenu{{ preg_replace('/\s+/', '', $link['title']) }}">
                            <svg class="bi" width="20" height="20" fill="currentColor">
                                <use xlink:href="/img/icons/bootstrap-icons.svg#{{ $link['icon'] ?? 'circle' }}"/>
                            </svg>
                            <span>{{ $link['title'] }}</span>
                        </button>

                        <div id="accordionMenu{{ preg_replace('/\s+/', '', $link['title']) }}" class="accordion-collapse collapse {{ request()->routeIs($link['url'].'.*') ? 'show' : '' }}" data-bs-parent="#accordionMenu">
                            @foreach($link['submenu'] as $sublink)
                                @if(!$sublink['isCan'])
                                    @continue
                                @endif

                                <div class="accordion-item">
                                    <a href="{{ $sublink['url'] !== '#' ? route($sublink['url']) : '#' }}" class="accordion-button gap-3 py-2 {{ request()->routeIs($sublink['groupUrl'] ? $sublink['groupUrl'] : $sublink['url']) ? 'active' : '' }}">
                                        <svg class="bi" width="20" height="20" fill="currentColor">
                                            <use xlink:href="/img/icons/bootstrap-icons.svg#<?= $sublink['icon'] ?? 'dot'; ?>"/>
                                        </svg>
                                        <span><?= $sublink['title']; ?></span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>
    </div>

    <div class="sidebar-footer">

    </div>
</aside>
