<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @inject ('setting', 'App\Models\Setting')
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ (isset($metaData) && !empty($metaData['meta_page_title'])) ? $metaData['meta_page_title'] : $setting::getValue('app', 'name') }}</title>
        @if (isset($metaData))
            @include('themes.starter.partials.site.metadata')
        @endif

        @php $public = url('/'); @endphp
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <!-- Bootstrap Font Icon CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="{{ asset('/vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
	<!-- Custom CSS file -->
	<link rel="stylesheet" href="{{ asset('/css/style.css') }}">
        <!-- Google fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Work Sans:100,200,300,400,500|Whisper|Alex Brush">
        <link rel="stylesheet" href="{{ asset('/vendor/adminlte/plugins/select2/css/select2.min.css') }}">
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>
        <!-- Daterangepicker -->
        @if (request()->is('discussions/create') || request()->is('discussions/*/edit'))
            <link rel="stylesheet" href="{{ asset('/vendor/adminlte/plugins/daterangepicker/daterangepicker.css') }}">
        @endif
    </head>
    <body>

	<div class="container">
	    <!-- Header -->
	    <header id="layout-header">
                @include('themes.starter.partials.site.header')
	    </header>

	    <!-- Content -->
	    <section id="layout-content" class="pt-4">
                @include('themes.starter.pages.'.$page)
	    </section>

	    <!-- Footer -->
	    <footer id="layout-footer" class="page-footer pt-4">
                @include('themes.starter.partials.site.footer')
	    </footer>
	</div>

    <!-- JS files: jQuery first, then Bootstrap JS -->
    <script type="text/javascript" src="{{ asset('/vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type="text/javascript" src="{{ asset('/js/parent.menu.links.clickable.js') }}"></script>
    <!-- Adds possible extra js scripts pushed by pages and partials. -->
    @stack ('scripts')

    @include('themes.starter.partials.cookie-info.index')
    </body>
</html>
