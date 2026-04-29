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

    @vite('resources/js/admin/admin.js')

    @stack('footer-scripts')
    @stack('modals')

    <x-admin.modal.wrapper id="confirm-delete-modal"/>
</body>
</html>
