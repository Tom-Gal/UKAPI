@php
    $seoTitle = request()->routeIs('home')
        ? 'UK data API for postcodes, companies and more | UKApi.co.uk'
        : 'UKApi.co.uk';
    $seoDescription = request()->routeIs('home')
        ? 'Postcodes, companies, crime, flood information and more through one carefully normalised UK data API.'
        : 'A dependable UK data API for postcodes, companies, crime and flood information.';
    $canonicalUrl = url()->current();
@endphp
<!DOCTYPE html>
<html lang="en-GB">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="index,follow">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">

        {{-- Keep the first paint aligned with the field-atlas interface. --}}
        <style>
            html {
                background-color: #f5f7f4;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $seoTitle }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
        @if (request()->routeIs('home'))
            <noscript>
                <main aria-labelledby="seo-home-title">
                    <h1 id="seo-home-title">UK data, one clean API.</h1>
                    <p>
                        Postcodes, companies, crime, flood information and more,
                        normalised into a single, dependable API for UK products.
                    </p>
                    <h2>Useful UK data, clearly catalogued</h2>
                    <p>
                        UKApi.co.uk provides predictable JSON endpoints for UK
                        postcodes, company information, crime data, flood
                        information, planning and related public data.
                    </p>
                    <ul>
                        <li>Canonical postcodes and selected geographic references</li>
                        <li>Companies House data for UK businesses</li>
                        <li>Crime and flood information with source coverage shown</li>
                    </ul>
                    <p>
                        <a href="/docs">Read the documentation</a>
                        or <a href="/api-catalogue">explore the API catalogue</a>.
                    </p>
                </main>
            </noscript>
        @endif
    </body>
</html>
