<footer class="footer">
    <div class="container">
        <div class="footer-body">
            <div class="footer-col footer-contacts">
                <x-public.logo class="footer-logo" />

                <div class="footer-contacts-links">
                    @php
                        $contentData = $contacts->getTranslation('content_data', config('app.fallback_locale'));
                        $phone = data_get($contentData, 'phones.0');
                        $whatsapp = data_get($contentData, 'whatsapp');
                        $email = data_get($contentData, 'emails.0');
                    @endphp

                    @if(!empty(trim((string) $phone)))
                        <p>
                            <a
                                href="tel:{{ get_only_numbers($phone) }}"
                                class="base-link footer-contact-link footer-contact-link--phone"
                            >{{ $phone }}</a>
                        </p>
                    @endif

                    @if(!empty(trim((string) $whatsapp)))
                        <p>
                            <a
                                href="https://wa.me/{{ get_only_numbers($whatsapp) }}"
                                target="_blank"
                                rel="noopener"
                                class="base-link footer-contact-link footer-contact-link--whatsapp"
                            >{{ $whatsapp }}</a>
                        </p>
                    @endif

                    @if(!empty(trim((string) $email)))
                        <p>
                            <a
                                href="mailto:{{ $email }}"
                                class="base-link footer-contact-link footer-contact-link--mail"
                            >{{ $email }}</a>
                        </p>
                    @endif
                </div>

                <address>
                    {{ data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'address') }}
                </address>
            </div>

            <nav class="footer-col footer-menu">
                <ul>
                    <li><a
                            href="{{ static_page_url('about') }}"
                            class="{{ static_page_is_active('about') ? 'is-active' : '' }}"
                        >{{ __('base.about') }}</a></li>
                    <li><a
                            href="{{ route('public.services') }}"
                            class="{{ request()->routeIs('public.service*') ? 'is-active' : '' }}"
                        >{{ __('base.expertise') }}</a></li>
                    <li><a
                            href="{{ portfolio_url() }}"
                            class="{{ portfolio_is_active() ? 'is-active' : '' }}"
                        >{{ __('base.portfolio') }}</a></li>
                    <li><a
                            href="{{ route('public.news') }}"
                            class="{{ request()->routeIs('public.news*') ? 'is-active' : '' }}"
                        >{{ __('base.news') }}</a></li>
                    <li><a
                            href="{{ static_page_url('contacts') }}"
                            class="{{ static_page_is_active('contacts') ? 'is-active' : '' }}"
                        >{{ __('base.contacts') }}</a></li>
                </ul>

                <ul>
                    <li><a
                            href="{{ static_page_url('imprint') }}"
                            class="{{ static_page_is_active('imprint') ? 'is-active' : '' }}"
                        >{{ __('base.imprint') }}</a></li>
                    <li><a
                            href="{{ static_page_url('privacy-notice') }}"
                            class="{{ static_page_is_active('privacy-notice') ? 'is-active' : '' }}"
                        >{{ __('base.privacy_notice') }}</a></li>
                    <li><a
                            href="{{ static_page_url('terms-of-use') }}"
                            class="{{ static_page_is_active('terms-of-use') ? 'is-active' : '' }}"
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
