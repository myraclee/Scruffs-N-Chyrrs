<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scruffs&Chyrrs</title>

    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page_parts/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page_parts/footer.css') }}">

</head>

<body>
    <div class="upper_body">
        <!-- NAVBAR -->
        @include('page_parts.navbar')

        <div class="main_content">
            @yield('content')
        </div>

    </div>

    <!-- FOOTER -->
    @include('page_parts.footer')
</body>
</html>
