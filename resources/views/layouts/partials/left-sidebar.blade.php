<div class="main-nav">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="{{ route('admin.dashboard') }}" class="logo-light">
            <img src="/images/Logo_BB-BIOTEKNOLOGI-BRMP-Black.png" class="logo-lg d-block" alt="UPBS Biogen logo (light)"/>
        </a>

        <a href="{{ route('admin.dashboard') }}" class="logo-dark">
            <img src="/images/Logo_BB-BIOTEKNOLOGI-BRMP-White.png" class="logo-lg d-block" alt="UPBS Biogen logo (dark)"/>
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
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:home-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Dashboard </span>
                </a>
            </li>

            <li class="menu-title">Catalog</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.commodities.*') ? 'active' : '' }}" href="{{ route('admin.commodities.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:category"></iconify-icon>
                    </span>
                    <span class="nav-text"> Commodities </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.varieties.*') ? 'active' : '' }}" href="{{ route('admin.varieties.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="gridicons:product"></iconify-icon>
                    </span>
                    <span class="nav-text"> Varieties </span>
                </a>
            </li>

            <li class="menu-title">Inventory</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.seed-classes.*') ? 'active' : '' }}" href="{{ route('admin.seed-classes.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:badge-fill"></iconify-icon>
                    </span>
                    <span class="nav-text"> Seed Classes </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.seed-lots.*') ? 'active' : '' }}" href="{{ route('admin.seed-lots.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:box-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Seed Lots </span>
                </a>
            </li>

            <li class="menu-title">Sales</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="ph:receipt-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Orders </span>
                </a>
            </li>

            <li class="menu-title">Administrator</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.admin-users.*') ? 'active' : '' }}" href="{{ route('admin.admin-users.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="wpf:administrator"></iconify-icon>
                    </span>
                    <span class="nav-text"> Admin Users </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:history-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Audit Logs </span>
                </a>
            </li>

        </ul>
    </div>
</div>
<!-- ========== Left Sidebar End ========== -->
