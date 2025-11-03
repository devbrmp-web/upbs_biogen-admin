<header class="topbar">
    <div class="container-xxl">
        <div class="navbar-header">
            <div class="d-flex align-items-center gap-2">
                <!-- Menu Toggle Button -->
                <div class="topbar-item">
                    <button type="button" class="button-toggle-menu">
                        <iconify-icon
                            icon="iconamoon:menu-burger-horizontal"
                            class="fs-22"
                        ></iconify-icon>
                    </button>
                </div>

                <!-- App Search-->
                <form class="app-search d-none d-md-block me-auto">
                    <div class="position-relative">
                        <input
                            type="search"
                            class="form-control"
                            placeholder="Search..."
                            autocomplete="off"
                            value=""
                        />
                        <iconify-icon
                            icon="iconamoon:search-duotone"
                            class="search-widget-icon"
                        ></iconify-icon>
                    </div>
                </form>
            </div>

            <div class="d-flex align-items-center gap-1">
                <!-- Theme Color (Light/Dark) -->
                <div class="topbar-item">
                    <button
                        type="button"
                        class="topbar-button"
                        id="light-dark-mode"
                    >
                        <iconify-icon
                            icon="iconamoon:mode-dark-duotone"
                            class="fs-24 align-middle"
                        ></iconify-icon>
                    </button>
                </div>

                <!-- Theme Setting -->
                <div class="topbar-item">
                    <button
                        type="button"
                        class="topbar-button"
                        id="theme-settings-btn"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#theme-settings-offcanvas"
                        aria-controls="theme-settings-offcanvas"
                    >
                        <iconify-icon
                            icon="iconamoon:settings-duotone"
                            class="fs-24 align-middle"
                        ></iconify-icon>
                    </button>
                </div>



                <!-- User -->
                <div class="dropdown topbar-item">
                    <a
                        type="button"
                        class="topbar-button"
                        id="page-header-user-dropdown"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <span class="d-flex align-items-center">
                            <img
                                class="rounded-circle"
                                width="32"
                                src="/images/users/avatar-1.jpg"
                                alt="avatar-3"
                            />
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">Welcome {{ auth()->user()->name }}!</h6>
                        <a class="dropdown-item" href="{{ route('admin.profile') }}">
                            <i
                                class="bx bx-user-circle text-muted fs-18 align-middle me-1"
                            ></i
                            ><span class="align-middle">Profile</span>
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.help') }}">
                            <i
                                class="bx bx-help-circle text-muted fs-18 align-middle me-1"
                            ></i
                            ><span class="align-middle">Help</span>
                        </a>
                        <div class="dropdown-divider my-1"></div>

                        <a
                            class="dropdown-item text-danger"
                            href="#"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        >
                            <i class="bx bx-log-out fs-18 align-middle me-1"></i
                            ><span class="align-middle">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
