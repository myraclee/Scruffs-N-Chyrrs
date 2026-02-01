<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scruffs&Chyrrs - About Us</title>
    @vite(['resources/css/home.css', 'resources/css/page_parts/navbar.css', 'resources/css/page_parts/footer.css'])
</head>

<body>
    <div class="upper_body">

        <!-- NAV BAR -->
        @include('page_parts.navbar')

        <div class="main_content">
            @yield('content')
        </div>
    </div>

    @include('page_parts.footer')
</body>
</html>
