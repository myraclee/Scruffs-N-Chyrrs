<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scruffs&Chyrrs</title>

    @vite(['resources/css/universal_owner.css','resources/css/owner/parts/sidenav.css','resources/js/owner/sidebar_account.js'])
    @yield('page_css')
</head>

<body>

    @include('owner.parts.sidenav')

    <main class="content">
        @yield('content')
    </main>
</body>
</html>