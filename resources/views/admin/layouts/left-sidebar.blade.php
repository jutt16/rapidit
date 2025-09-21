<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="brand-link d-flex align-items-center" style="padding: 0.5rem 1rem; gap: 0.75rem;">
        <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;background:white;color:#FFD700;font-weight:bold;border-radius:50%;font-size:26px;box-shadow:0 2px 6px rgba(0,0,0,0.2);">R</span>
        <span class="brand-text font-weight-light text-capitalize" style="font-size:1.1rem; color:#ffffff;">Rapid It</span>
    </a>

    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('admin-assets/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="{{ route('admin.dashboard') }}" class="d-block">{{ Auth::user()->name }}</a>
            </div>
        </div>

        <!-- Search -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Users</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.partners.index') }}" class="nav-link {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-handshake"></i>
                        <p>Manage Partners</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.banners.index') }}" class="nav-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-image"></i>
                        <p>Banners</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-bell"></i>
                        <p>Manage Notifications</p>
                    </a>
                </li>

                <!-- ✅ New Cook Pricing Menu -->
                <li class="nav-item">
                    <a href="{{ route('admin.cook-pricings.index') }}" class="nav-link {{ request()->routeIs('admin.cook-pricings.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-utensils"></i>
                        <p>Cook Pricing</p>
                    </a>
                </li>

                <!-- ✅ New Maid Pricing Menu -->
                <li class="nav-item">
                    <a href="{{ route('admin.maid-pricings.index') }}" class="nav-link {{ request()->routeIs('admin.maid-pricings.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-broom"></i>
                        <p>Maid Packages</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.services.index') }}"
                        class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-concierge-bell"></i>
                        <p>Services</p>
                    </a>
                </li>

                <!-- Revies Management -->
                <li class="nav-item">
                    <a href="{{ route('admin.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-star"></i>
                        <p>Reviews</p>
                    </a>
                </li>

                <!-- static pages -->
                <li class="nav-item">
                    <a href="{{ route('admin.static-pages.index') }}" class="nav-link {{ request()->routeIs('admin.static-pages.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Static Pages</p>
                    </a>
                </li>

                <!-- support messages -->
                <li class="nav-item">
                    <a href="{{ route('admin.support.index') }}" class="nav-link {{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>Support Messages</p>
                    </a>
                </li>

                <!-- ✅ Settings menu -->
                <li class="nav-item">
                    <a href="{{ route('admin.settings.radius.edit') }}"
                        class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Settings</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
</aside>