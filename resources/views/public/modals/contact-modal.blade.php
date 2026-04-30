<div id="contact-modal" class="modal">
    <h4 class="modal-title">{{ __('public.contact_us_title') }}</h4>

    <form data-contact-form action="{{ route('public.send-contact-form') }}" method="POST" class="form">
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

        <p class="form-info">By clicking “Send,” I agree to the <a href="{{ static_page_url('privacy-notice') }}">{{ __('public.privacy_notice') }}</a>.</p>

        <button type="submit" class="btn btn-submit">{{ __('base.send') }}</button>
    </form>
</div>
