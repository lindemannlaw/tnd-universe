@php
    $user = auth()->user();
@endphp

<header id="header" class="header d-flex align-items-center border-bottom border-dark border-opacity-25 shadow-sm py-2 pe-3 ps-5 gap-3 bg-white">

    <!-- admin / manager info -->
    <div class="d-flex align-items-center gap-3 ms-auto">
        <!-- name -->
        <div class="lh-1">{{ $user->first_name }} {{ $user->last_name }}</div>

        <!-- picture -->
        <picture class="p-3 pt-4 ps-4 border border-secondary border-opacity-50 rounded-circle overflow-hidden position-relative bg-light">
            @if($user->hasMedia($user->mediaCollection))
                <img src="{{ $user->getFirstMediaUrl($user->mediaCollection, 'avatar') }}" alt="Avatar" class="img-cover position-absolute top-0 start-0">
            @else
                <div class="d-flex align-items-center text-center position-absolute top-50 start-50 translate-middle fw-bold">
                    <span>{{ strtoupper(substr($user->first_name, 0, 1)) }}</span>
                    <span>{{ strtoupper(substr($user->last_name, 0, 1)) }}</span>
                </div>
            @endif
        </picture>

        <!-- lang -->
        <x-admin.lang class="me-n3" :withIcon="true" />

        <!-- profile menu -->
        <div class="dropdown">
            <button class="btn border-0 d-flex align-items-center justify-content-center px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <svg class="bi" width="26" height="26" fill="currentColor">
                    <use xlink:href="/img/icons/bootstrap-icons.svg#three-dots-vertical"/>
                </svg>
            </button>

            <ul class="dropdown-menu shadow-md mt-1">
                <?php
                    $menu = [
                        [
                            'title' => __('admin.profile'),
                            'url' => 'admin.profile',
                            'icon' => 'gear',
                        ],
                        [
                            'title' => __('admin.password'),
                            'url' => 'admin.password',
                            'icon' => 'key',
                        ],
                        [
                            'title' => __('admin.managers'),
                            'url' => 'admin.manager',
                            'icon' => 'people',
                        ],
                    ];
                ?>

                @if($user->hasRole('super-admin') || $user->hasRole('admin'))
                    <?php foreach ($menu as $link): ?>
                        <li>
                            <a href="{{ $link['url'] === '#' ? '#' : route($link['url']) }}" class="d-flex align-items-center gap-3 dropdown-item">
                                <svg class="bi" width="18" height="18" fill="currentColor">
                                    <use xlink:href="/img/icons/bootstrap-icons.svg#<?= $link['icon']; ?>"/>
                                </svg>
                                <span><?= $link['title']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                @endif

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="d-flex align-items-center gap-3 dropdown-item">
                            <svg class="bi" width="18" height="18" fill="currentColor">
                                <use xlink:href="/img/icons/bootstrap-icons.svg#box-arrow-left"/>
                            </svg>
                            <span>{{ __('admin.log_out') }}</span>
                        </button>
                    </form>
                </li>

            </ul>
        </div>
    </div>
</header>
