<footer class="footer">
    <div class="container">
        <div class="footer-body">
            <div class="footer-col footer-contacts">
                <x-public.logo class="footer-logo" />

                <div class="footer-contacts-links">
                    @foreach (data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'phones', []) as $phone)
                        <p>
                            <a
                                href="tel:{{ get_only_numbers($phone) }}"
                                class="base-link"
                            >{{ $phone }}</a>
                        </p>
                    @endforeach

                    @foreach (data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'emails', []) as $email)
                        <p>
                            <a
                                href="mailto:{{ $email }}"
                                class="base-link"
                            >{{ $email }}</a>
                        </p>
                    @endforeach

                    @include('public.fragments.whatsapp-button', ['variant' => 'footer', 'label' => 'WhatsApp'])
                </div>

                <address>
                    {{ data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'address') }}
                </address>
            </div>

            <nav class="footer-col footer-menu">
                <ul>
                    <li><a
                            href="{{ route('public.about') }}"
                            class="{{ request()->routeIs('public.about') ? 'is-active' : '' }}"
                        >{{ __('base.about') }}</a></li>
                    <li><a
                            href="{{ route('public.services') }}"
                            class="{{ request()->routeIs('public.service*') ? 'is-active' : '' }}"
                        >{{ __('base.expertise') }}</a></li>
                    <li><a
                            href="{{ route('public.portfolio') }}"
                            class="{{ request()->routeIs('public.portfolio*') ? 'is-active' : '' }}"
                        >{{ __('base.portfolio') }}</a></li>
                    <li><a
                            href="{{ route('public.news') }}"
                            class="{{ request()->routeIs('public.news*') ? 'is-active' : '' }}"
                        >{{ __('base.news') }}</a></li>
                    <li><a
                            href="{{ route('public.contacts') }}"
                            class="{{ request()->routeIs('public.contacts') ? 'is-active' : '' }}"
                        >{{ __('base.contacts') }}</a></li>
                </ul>

                <ul>
                    <li><a
                            href="{{ route('public.imprint') }}"
                            class="{{ request()->routeIs('public.imprint') ? 'is-active' : '' }}"
                        >{{ __('base.imprint') }}</a></li>
                    <li><a
                            href="{{ route('public.privacy-notice') }}"
                            class="{{ request()->routeIs('public.privacy-notice') ? 'is-active' : '' }}"
                        >{{ __('base.privacy_notice') }}</a></li>
                    <li><a
                            href="{{ route('public.terms-of-use') }}"
                            class="{{ request()->routeIs('public.terms-of-use') ? 'is-active' : '' }}"
                        >{{ __('base.terms_of_use') }}</a></li>
                </ul>
            </nav>

            <div class="footer-col footer-socials">
                <div class="footer-socials-content">
                    <div class="footer-socials-title">{{ __('base.follow_us') }}</div>
                    @include('public.fragments.socials')
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="copyright">{{ __('public.copyright', ['year' => date('Y')]) }}</div>

            {{-- <a href="#" class="creator">
                <img src="/img/citi-logo.svg" alt="CITI Advertising" class="img-fluid">
            </a> --}}
        </div>
    </div>
</footer>
