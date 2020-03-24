<!doctype html>
<html lang="{{ config('app.locale') }}" class="no-focus">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

        <title>VioDrip - Vio-V Bus Verbindungssuche</title>

        <meta name="description" content="VioDrip - Vio-V Bus Verbindungssuche">
        <meta name="author" content="Samir">
        <meta name="robots" content="noindex, nofollow">

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="shortcut icon" href="https://cdn.discordapp.com/icons/638801105173741586/f68e478169ce628a5fb7fefe4f378724.webp?size=256">
        <link rel="icon" sizes="192x192" type="image/png" href="https://cdn.discordapp.com/icons/638801105173741586/f68e478169ce628a5fb7fefe4f378724.webp?size=256">
        <link rel="apple-touch-icon" sizes="180x180" href="https://cdn.discordapp.com/icons/638801105173741586/f68e478169ce628a5fb7fefe4f378724.webp?size=256">

        @yield('css_before')
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700">
        <link rel="stylesheet" id="css-main" href="{{ mix('/css/codebase.css') }}">
        <link rel="stylesheet" href="{{asset('js/plugins/select2/css/select2.min.css') }}">

        @yield('css_after')

        <script>window.Laravel = {!! json_encode(['csrfToken' => csrf_token(),]) !!};</script>
    </head>
    <body>
        <div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-narrow">

            <main id="main-container">
                @yield('content')
            </main>

            <footer id="page-footer" class="opacity-0">
                <div class="content py-20 font-size-sm clearfix">
                    <div class="float-right">
                        Coded with <i class="fa fa-heart text-pulse"></i> by <a class="font-w600" href="https://forum.vio-v.com/index.php?user/4506-samir/" target="_blank">Samir</a>
                    </div>
                    <div class="float-left">
                        <a class="font-w600" href="/">VioDrip - Vio-V Bus Verbindungssuche</a> &copy; <span class="js-year-copy"></span>
                    </div>
                </div>
            </footer>
        </div>

        <script src="{{ mix('js/codebase.app.js') }}"></script>
        <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>

        <script>jQuery(function(){ Codebase.helpers(['select2']); });</script>

        @yield('js_after')
    </body>
</html>
