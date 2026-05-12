<header id="header" class="header">
    <div class="container header-container">
        <x-public.logo :isHorisontal="true" class="header-logo"/>

        <nav id="main-menu" class="main-menu">
            <div class="main-menu-head">
                <x-public.logo :isHorisontal="true" class="main-menu-logo"/>
            </div>

            <ul>
                <li><a href="{{ static_page_url('about') }}" class="{{ static_page_is_active('about') ? 'is-active' : '' }}">{{ __('base.about') }}</a></li>
                <li><a href="{{ route('public.services') }}" class="{{ request()->routeIs('public.service*') ? 'is-active' : '' }}">{{ __('base.expertise') }}</a></li>
                <li><a href="{{ portfolio_url() }}" class="{{ portfolio_is_active() ? 'is-active' : '' }}">{{ __('base.portfolio') }}</a></li>
                <li><a href="{{ route('public.news') }}" class="{{ request()->routeIs('public.news*') ? 'is-active' : '' }}">{{ __('base.news') }}</a></li>
                <li><a href="{{ static_page_url('contacts') }}" class="{{ static_page_is_active('contacts') ? 'is-active' : '' }}">{{ __('base.contacts') }}</a></li>
            </ul>
        </nav>

        <div class="header-cta-group">
            <button data-fancybox data-src="#contact-modal" type="button" class="btn header-contact-btn">{{ __('base.get_in_touch') }}</button>
            @include('public.fragments.whatsapp-button', ['variant' => 'header'])
        </div>

        <div class="lang">
            <p>
                <img src="{{ asset('img/flags/' . current_locale() . '.svg') }}" alt="" class="lang-flag" width="20" height="15">
                <span>{{ current_locale() }}</span>
            </p>
            <ul>
                @foreach(published_languages_keys() as $lang)
                    @if(current_locale() !== $lang)
                        <li>
                            <a rel="alternate" hreflang="{{ $lang }}" href="{{ localized_url($lang) }}">
                                <img src="{{ asset('img/flags/' . $lang . '.svg') }}" alt="" class="lang-flag" width="20" height="15">
                                <span>{{ $lang }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>

        <button id="main-menu-toggle-button" type="button" class="btn main-menu-toggle-button"><span>{{ __('base.menu') }}</span></button>

        {{--<button id="extended-menu-toggle-button" type="button" class="btn extended-menu-toggle-button"><span>{{ __('base.menu') }}</span></button>--}}

        {{--<div class="extended-menu">
            <div class="extended-menu-body">
                <div class="container">
                    <div class="extended-menu-row extended-menu-head">
                        <div class="extended-menu-col">
                            <h4 class="extended-menu-title">Real Estate<br> Services</h4>
                        </div>
                        <div class="extended-menu-col">
                            <h4 class="extended-menu-title">Financial & Investment<br> Advisory</h4>
                        </div>
                        <div class="extended-menu-col">
                            <h4 class="extended-menu-title">Legal & Relocation<br> Services</h4>
                        </div>
                    </div>

                    <div class="extended-menu-row extended-menu-nav">
                        <div class="extended-menu-col">
                            <h4 class="extended-menu-title">Real Estate<br> Services</h4>

                            <ul>
                                <li><a href="#" class="primary-link">Searching and brokerage of real estate objects in Switzerland</a></li>
                                <li><a href="#" class="primary-link">Planning of real estate</a></li>
                                <li><a href="#" class="primary-link">Development of real estate</a></li>
                                <li><a href="#" class="primary-link">Marketing of real estate</a></li>
                                <li><a href="#" class="primary-link">Brokerage</a></li>
                                <li><a href="#" class="primary-link">Property management</a></li>
                                <li><a href="#" class="primary-link">Renovation</a></li>
                            </ul>
                        </div>
                        <div class="extended-menu-col">
                            <h4 class="extended-menu-title">Financial & Investment<br> Advisory</h4>

                            <ul>
                                <li><a href="#" class="primary-link">Corporate finance advisory for real estate projects</a></li>
                                <li><a href="#" class="primary-link">Investment consulting</a></li>
                                <li><a href="#" class="primary-link">Tax</a></li>
                                <li><a href="#" class="primary-link">Wealth protection</a></li>
                            </ul>
                        </div>
                        <div class="extended-menu-col">
                            <h4 class="extended-menu-title">Legal & Relocation<br> Services</h4>

                            <ul>
                                <li><a href="#" class="primary-link">Legal structuring</a></li>
                                <li><a href="#" class="primary-link">Relocation to Switzerland</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="extended-menu-footer">
                <div class="container extended-menu-footer-container">
                    <nav class="main-menu">
                        <ul>
                            <li><a href="#">{{ __('base.about') }}</a></li>
                            <li><a href="#">{{ __('base.portfolio') }}</a></li>
                            <li><a href="{{ route('public.news') }}" class="{{ request()->routeIs('public.news*') ? 'is-active' : '' }}">{{ __('base.news') }}</a></li>
                            <li><a href="#">{{ __('base.contacts') }}</a></li>
                        </ul>
                    </nav>

                    <button class="btn btn-outline-primary btn-light extended-menu-contact-btn">{{ __('base.contact_us') }}</button>

                    <x-public.socials class="extended-menu-socials"/>
                </div>
            </div>
        </div>--}}
    </div>
</header>
