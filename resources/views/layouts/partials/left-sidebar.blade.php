<div class="main-nav">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="{{ Route::has('admin.any') ? route('admin.any', 'home') : '#' }}" class="logo-dark">
            <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm"/>
            <img src="/images/logo-dark.png" class="logo-lg" alt="logo dark"/>
        </a>

        <a href="{{ Route::has('admin.any') ? route('admin.any', 'home') : '#' }}" class="logo-light">
            <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm"/>
            <img src="/images/logo-light.png" class="logo-lg" alt="logo light"/>
        </a>
    </div>

    <!-- Menu Toggle Button (sm-hover) -->
    <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
        <iconify-icon icon="iconamoon:arrow-left-4-square-duotone" class="button-sm-hover-icon"></iconify-icon>
    </button>

    <div class="scrollbar" data-simplebar>
        <ul class="navbar-nav" id="navbar-nav">
            <li class="menu-title">Menu</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:home-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Dashboard </span>
                </a>
            </li>

            <li class="menu-title">Management</li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarProducts" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarProducts">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:shopping-bag-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Products </span>
                </a>
                <div class="collapse" id="sidebarProducts">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Manage Categories</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Manage Products</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:invoice-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Orders </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span class="nav-icon">
                        <iconify-icon icon="wpf:administrator"></iconify-icon>
                    </span>
                    <span class="nav-text"> Administrator </span>
                </a>
            </li>

            <li class="menu-title">Report</li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:history-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Audit Log </span>
                </a>
            </li>

        </ul>
    </div>
</div>
<!-- ========== Left Sidebar End ========== -->
