<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scruffs&Chyrrs</title>

    @vite(['resources/css/universal.css', 'resources/css/page_parts/navbar.css', 'resources/css/page_parts/footer.css'])
    @yield('page_css')
</head>

<body>
    <div class="upper_body">
        <!-- NAVBAR -->
        @include('page_parts.navbar')

        <!-- MAIN CONTENT -->
        <div class="main_content">
            @yield('content')
        </div>

    </div>

    <!-- FOOTER -->
    @include('page_parts.footer')

    {{-- JS --}}
    @yield('page_js')
    @vite('resources/js/customeraccount_options.js')
</body>
</html>
