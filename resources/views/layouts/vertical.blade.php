<!doctype html>
<html lang="en" class="no-js" data-bs-theme="light" data-topbar-color="light" data-menu-color="light" data-menu-size="sm-hover-active">
<head>
    @include('layouts.partials/title-meta', ['title' => $title])
    @yield('css')
    @include('layouts.partials/head-css')
    
    <!-- Prevent FOUC by applying theme immediately with inline CSS -->
    <style>
        /* Critical CSS to prevent FOUC - applied immediately */
        html.no-js {
            background-color: #ffffff !important;
            color: #495057 !important;
        }
        html.no-js[data-bs-theme="dark"] {
            background-color: #1a1d29 !important;
            color: #adb5bd !important;
        }
        html.no-js * {
            transition: none !important;
            animation-duration: 0s !important;
        }
        /* Hide content until theme is properly applied */
        html.no-js body {
            visibility: hidden;
        }
        html.theme-ready body {
            visibility: visible;
        }
    </style>
    
    <script>
        (function() {
            // Get saved theme from localStorage immediately
            const savedConfig = localStorage.getItem("__REBACK_CONFIG__");
            const html = document.documentElement;
            
            if (savedConfig) {
                try {
                    const config = JSON.parse(savedConfig);
                    if (config.theme) {
                        html.setAttribute("data-bs-theme", config.theme);
                    }
                    if (config.topbar && config.topbar.color) {
                        html.setAttribute("data-topbar-color", config.topbar.color);
                    }
                    if (config.menu && config.menu.color) {
                        html.setAttribute("data-menu-color", config.menu.color);
                    }
                    if (config.menu && config.menu.size) {
                        if (window.innerWidth <= 1140) {
                            html.setAttribute("data-menu-size", "hidden");
                        } else {
                            html.setAttribute("data-menu-size", config.menu.size);
                        }
                    }
                } catch (e) {
                    // If parsing fails, use defaults
                    console.warn('Failed to parse saved theme config:', e);
                }
            }
            
            // Remove no-js class and add theme-ready class after theme is applied
            html.classList.remove('no-js');
            html.classList.add('theme-ready');
        })();
    </script>
</head>

<body>

<div class="wrapper">
    @include('layouts.partials/topbar')
    @include('layouts.partials/left-sidebar')

    <div class="page-content">

        <div class="container-xxl">
            @include("layouts.partials/page-title", ['title' => $title,'subTitle' => $subTitle])
            @yield('content')
        </div>

        @include("layouts.partials/footer")
    </div>

</div>

@include("layouts.partials/right-sidebar")

<!-- Logout Form -->
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>

@include("layouts.partials/footer-scripts")
@vite(['resources/js/app.js'])

<script>
// Remove no-js class to enable transitions after page load
document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.classList.remove('no-js');
});
</script>

@stack('modals')
@stack('scripts')

</body>
</html>
