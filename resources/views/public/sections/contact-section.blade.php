@if(isset($contactUsSection))
    <section class="container contact-section">
        <div class="contact-section-content bg-img-cover" style="background-image: url({{ $contactUsSection->firstAttachedMediaUrl('bg-image', 'hd-webp') }});">
            <div class="contact-section-body">
                <div class="contact-section-head">
                    <h2 class="contact-section-title">{{ $contactUsSection->title }}</h2>
                    <p class="contact-section-description">{{ data_get($contactUsSection->content_data, 'description') }}</p>
                </div>

                <div class="contact-section-links">
                    @foreach(data_get($contactUsSection->getTranslation('content_data', config('app.fallback_locale')), 'phones', []) as $phone)
                        <p>
                            <a href="tel:{{ get_only_numbers($phone) }}" class="base-link">{{ $phone }}</a>
                        </p>
                    @endforeach
                    
                    @foreach(data_get($contactUsSection->getTranslation('content_data', config('app.fallback_locale')), 'emails', []) as $email)
                        <p>
                            <a href="mailto:{{ $email }}" class="base-link">{{ $email }}</a>
                        </p>
                    @endforeach
                </div>
            </div>

            <div class="contact-section-form">
                <div class="contact-section-form-head">
                    <h4 class="contact-section-form-title">{{ __('public.contact_us_title') }}</h4>
                </div>

                <form data-contact-form action="{{ route('public.send-contact-form') }}" method="POST" class="form contact-section-form-body">
                    @csrf
                    @method('POST')

                    <div class="form-fields">
                        <div class="form-fields-row">
                            <label class="form-control">
                                <input data-form-field type="text" name="first_name" class="form-field" required>
                                <span class="form-control-placeholder">{{ __('base.your_name') }} <sup>*</sup></span>
                            </label>

                            <label class="form-control">
                                <input data-form-field type="text" name="last_name" class="form-field" required>
                                <span class="form-control-placeholder">{{ __('base.last_name') }} <sup>*</sup></span>
                            </label>
                        </div>

                        <label class="form-control">
                            <input data-form-field type="tel" name="phone" class="form-field" required>
                            <span class="form-control-placeholder">{{ __('base.phone') }} <sup>*</sup></span>
                        </label>

                        <label class="form-control">
                            <input data-form-field type="email" name="email" class="form-field" required>
                            <span class="form-control-placeholder">{{ __('base.email') }} <sup>*</sup></span>
                        </label>
                    </div>

                    <div data-form-errors data-error-messge="{{ __('public.error_validate_form') }}" class="form-errors"></div>

                    <p class="form-info">{{ __('public.by_clicking_send') }} <a href="{{ static_page_url('privacy-notice') }}">{{ __('public.privacy_notice') }}</a>.</p>

                    <button type="submit" class="btn btn-submit">{{ __('base.send') }}</button>
                </form>
            </div>
        </div>
    </section>
@endif
