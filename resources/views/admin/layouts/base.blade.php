<!doctype html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>@yield('title', config('app.name'))</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite('resources/css/admin/admin.scss')
</head>
<body class="bg-light">

    {{-- Inline SVG sprite – eliminates separate HTTP request for icons --}}
    <div style="display:none" aria-hidden="true">
        {!! file_get_contents(public_path('img/icons/bootstrap-icons.svg')) !!}
    </div>

    <div id="preloader" class="preloader active">
        <div class="preloader-spinner"></div>
    </div>

    @include('admin.sections.sidebar')

    @include('admin.sections.header')

    <main>
        @yield('panel')

        @yield('content')
    </main>

    @include('admin.sections.alerts')

    <script>
        window.ADMIN_I18N = @json([
            'overlay_changed_fields_processing' => __('admin.overlay_changed_fields_processing'),
            'overlay_auto_translations_generating' => __('admin.overlay_auto_translations_generating'),
            'overlay_translate_content' => __('admin.overlay_translate_content'),
            'overlay_generate_seo_geo' => __('admin.overlay_generate_seo_geo'),
            'overlay_translate_seo_geo' => __('admin.overlay_translate_seo_geo'),
            'overlay_please_wait' => __('admin.overlay_please_wait'),
            'overlay_translations' => __('admin.overlay_translations'),
            'overlay_no_fields_to_translate' => __('admin.overlay_no_fields_to_translate'),
            'overlay_no_changed_fields' => __('admin.overlay_no_changed_fields'),
            'overlay_no_fields' => __('admin.overlay_no_fields'),
            'overlay_seo_geo_not_available' => __('admin.overlay_seo_geo_not_available'),
            'overlay_skipped_user_choice' => __('admin.overlay_skipped_user_choice'),
            'overlay_skipped_no_confirm' => __('admin.overlay_skipped_no_confirm'),
            'overlay_close_done' => __('admin.overlay_close_done'),
            'overlay_confirm_seo_geo_rerun' => __('admin.overlay_confirm_seo_geo_rerun'),
            'overlay_confirm_seo_geo_text' => __('admin.overlay_confirm_seo_geo_text'),
            'overlay_yes' => __('admin.overlay_yes'),
            'overlay_no' => __('admin.overlay_no'),
            'overlay_saved' => __('admin.overlay_saved'),
            'overlay_saved_detail' => __('admin.overlay_saved_detail'),
            'overlay_fields' => __('admin.overlay_fields'),
            'overlay_changed_unchanged' => __('admin.overlay_changed_unchanged', ['unchanged' => ':unchanged', 'changed' => ':changed']),
            'overlay_translations_detail_ok' => __('admin.overlay_translations_detail_ok', ['count' => ':count']),
            'overlay_translations_detail_mix' => __('admin.overlay_translations_detail_mix', ['ok' => ':ok', 'err' => ':err']),
            'overlay_seo_geo' => __('admin.overlay_seo_geo'),
            'overlay_generated' => __('admin.overlay_generated'),
            'overlay_generation_error' => __('admin.overlay_generation_error'),
            'overlay_geo_translations' => __('admin.overlay_geo_translations'),
        ]);
    </script>

    @vite('resources/js/admin/admin.js')

    @stack('footer-scripts')
    @stack('modals')

    <x-admin.modal.wrapper id="confirm-delete-modal"/>
</body>
</html>
