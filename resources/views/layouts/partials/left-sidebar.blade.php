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
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:home-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Dashboard </span>
                </a>
            </li>

            <li class="menu-title">Management</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.commodities.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:category"></iconify-icon>
                    </span>
                    <span class="nav-text"> Commodities </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.varieties.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="gridicons:product"></iconify-icon>
                    </span>
                    <span class="nav-text"> Varieties </span>
                </a>
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
