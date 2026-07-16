<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('chatify.name', 'Chatify Messenger') }}</title>
    @stack('styles')
</head>
<body class="chatify:antialiased chatify:m-0 chatify:overflow-hidden" style="overscroll-behavior: none; touch-action: manipulation;">
    @yield('content')
</body>
</html>
