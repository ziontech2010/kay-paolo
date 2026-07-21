@php
    $zionUser = session('zion.user', []);
    $isLoggedIn = (bool) session('zion.access_token');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kay Paolo Shipping')</title>
    <meta name="description" content="@yield('description', 'Kay Paolo Shipping freight, quoting, shipment creation, and tracking powered by Zion Shipping dev API.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('kay-paolo/assets/style.css') }}">
    <link rel="stylesheet" href="{{ asset('kay-paolo/assets/kay-paolo.css') }}">
    <link rel="icon" href="{{ asset('kay-paolo/assets/logo.svg') }}">
</head>
<body data-authenticated="{{ $isLoggedIn ? '1' : '0' }}">
<div class="topbar">
    <div class="wrap">
        <div class="topbar-left">
            <a href="https://maps.google.com/?q=Port+District+Warehouse+7+Newark+NJ" target="_blank" rel="noopener">Port District, Warehouse 7, Newark, NJ 07114</a>
            <a href="mailto:info@kaypaoloshipping.com">info@kaypaoloshipping.com</a>
        </div>
        <div class="topbar-social">
            <a href="#" aria-label="Facebook">f</a>
            <a href="#" aria-label="Instagram">in</a>
            <a href="#" aria-label="X">x</a>
            <a href="#" aria-label="LinkedIn">ln</a>
        </div>
    </div>
</div>

<header class="site" id="siteHeader">
    <div class="wrap nav-wrap">
        <a href="{{ route('home') }}" class="brand">
            <img src="{{ asset('kay-paolo/assets/logo.svg') }}" alt="Kay Paolo Shipping" width="44" height="44">
            <span class="brand-word"><span class="t1">Kay Paolo</span><span class="t2">Shipping Co.</span></span>
        </a>

        <nav class="main" id="mainNav">
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('about') }}">About Us</a></li>
                <li class="has-dd">
                    <a href="{{ route('services') }}">Services <span class="caret">v</span></a>
                    <div class="dropdown">
                        <a href="{{ route('services') }}#ocean">Ocean Freight</a>
                        <a href="{{ route('services') }}#air">Air Freight</a>
                        <a href="{{ route('services') }}#land">Land Freight</a>
                    </div>
                </li>
                <li class="has-dd">
                    <a href="{{ route('quote') }}">Shipping <span class="caret">v</span></a>
                    <div class="dropdown">
                        <a href="{{ route('quote') }}">Create a Shipment</a>
                        <a href="{{ route('quote') }}">Get Quote</a>
                    </div>
                </li>
                <li class="has-dd">
                    <a href="{{ route('tracking') }}">Tracking <span class="caret">v</span></a>
                    <div class="dropdown">
                        <a href="{{ route('tracking') }}">Track a Package</a>
                        <a href="{{ route('contact') }}">Our Locations</a>
                    </div>
                </li>
                <li><a href="{{ route('blog') }}">Blog</a></li>
                <li><a href="{{ route('contact') }}">Get In Touch</a></li>
                @if ($isLoggedIn)
                    <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                @else
                    <li><a href="{{ route('login') }}">Login</a></li>
                @endif
            </ul>
        </nav>

        <div class="nav-cta">
            <div class="nav-phone">
                Have Any Questions?
                <b>+1 (201) 555-0148</b>
            </div>
            <a href="{{ route('quote') }}" class="btn btn-gold">Get A Quote</a>
            <button class="burger" id="burgerBtn" aria-label="Menu">Menu</button>
        </div>
    </div>
</header>

@yield('banner')
@yield('content')

<footer class="site">
    <div class="wrap footer-grid">
        <div class="footer-brand">
            <a href="{{ route('home') }}" class="brand">
                <img src="{{ asset('kay-paolo/assets/logo.svg') }}" alt="Kay Paolo Shipping" width="40" height="40">
                <span class="brand-word"><span class="t1">Kay Paolo</span><span class="t2">Shipping Co.</span></span>
            </a>
            <p>Ocean, air and land freight with customs clearance and live tracking, one desk from pickup to delivery.</p>
            <div class="footer-social"><a href="#">f</a><a href="#">in</a><a href="#">x</a><a href="#">ln</a></div>
        </div>
        <div>
            <h5>Quick Links</h5>
            <ul>
                <li><a href="{{ route('about') }}">About Us</a></li>
                <li><a href="{{ route('services') }}">Services</a></li>
                <li><a href="{{ route('contact') }}">Get In Touch</a></li>
                <li><a href="{{ route('tracking') }}">Support 24/7</a></li>
            </ul>
        </div>
        <div>
            <h5>Our Services</h5>
            <ul>
                <li><a href="{{ route('services') }}#ocean">Ocean Freight</a></li>
                <li><a href="{{ route('services') }}#rail">Rail Freight</a></li>
                <li><a href="{{ route('services') }}#land">Land Freight</a></li>
                <li><a href="{{ route('services') }}#air">Air Freight</a></li>
            </ul>
        </div>
        <div>
            <h5>Session</h5>
            <ul>
                @if ($isLoggedIn)
                    <li>{{ $zionUser['name'] ?? 'Zion user' }}</li>
                    <li>{{ $zionUser['role']['name'] ?? 'Role synced from Zion' }}</li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="footer-button" type="submit">Logout</button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('login') }}">Login with Zion account</a></li>
                    <li>Powered by dev Zion API</li>
                @endif
            </ul>
        </div>
    </div>
    <div class="wrap footer-bottom">
        <span>Copyright 2026 Kay Paolo Shipping Co. | Freight and Logistics</span>
        <span><a href="#">Privacy</a> / <a href="#">Terms and Conditions</a> / <a href="{{ route('contact') }}">Contact</a></span>
    </div>
</footer>

<a class="whatsapp-fab" href="https://wa.me/12015550148" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg viewBox="0 0 32 32"><path d="M16.001 3C9.383 3 4 8.383 4 15c0 2.34.687 4.51 1.865 6.34L4 29l7.86-1.822A11.94 11.94 0 0 0 16 27c6.617 0 12-5.383 12-12S22.618 3 16.001 3zm6.98 17.02c-.298.84-1.47 1.54-2.41 1.74-.65.14-1.5.25-4.35-.93-3.65-1.51-6-5.2-6.18-5.44-.18-.24-1.47-1.95-1.47-3.72 0-1.77.93-2.64 1.26-3 .33-.36.72-.45.96-.45.24 0 .48 0 .69.01.22.01.52-.08.81.62.3.72 1.02 2.49 1.11 2.67.09.18.15.39.03.63-.12.24-.18.39-.36.6-.18.21-.38.47-.54.63-.18.18-.37.38-.16.74.21.36.94 1.55 2.02 2.51 1.39 1.24 2.56 1.62 2.92 1.8.36.18.57.15.78-.09.21-.24.9-1.05 1.14-1.41.24-.36.48-.3.81-.18.33.12 2.1.99 2.46 1.17.36.18.6.27.69.42.09.15.09.87-.19 1.71z"/></svg>
</a>

<script>
    window.KayPaolo = {
        authenticated: @json($isLoggedIn),
        sessionToken: @json($isLoggedIn ? session('zion.access_token') : null),
        sessionUser: @json($zionUser),
        routes: {
            loginPage: @json(route('login')),
            login: @json(route('api.kay-paolo.login')),
            quote: @json(route('api.kay-paolo.quote')),
            shipping: @json(route('api.kay-paolo.shipping')),
            tracking: @json(route('api.kay-paolo.validate-tracking')),
            fetchUserForQuote: @json(route('api.kay-paolo.fetch-user-for-quote')),
            consigneeList: @json(route('api.kay-paolo.consignee-list')),
            flatRates: @json(route('api.kay-paolo.flat-rates'))
        },
        assets: {
            generatingQuote: @json(asset('kay-paolo/assets/generating-quote.gif')),
            processingShipping: @json(asset('kay-paolo/assets/processing-shipping.gif'))
        }
    };
</script>
<script src="{{ asset('kay-paolo/assets/app.js') }}" defer></script>
</body>
</html>
