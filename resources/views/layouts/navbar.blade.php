<nav class="navbar admin-navbar navbar-expand bg-white border-bottom">
  <div class="container-fluid px-3 px-lg-4">
    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true" aria-label="Toggle sidebar">
      <span></span>
      <span></span>
      <span></span>
    </button>

    {{-- <div class="d-none d-md-flex ms-3 align-items-center">
      <span class="badge text-bg-light border text-dark fs-6 font-monospace">
        <i class="bi bi-geo-alt-fill text-warning me-1"></i> {{ Auth::user()->location?->name ?? 'HQ / Office Fleet' }}
      </span>
    </div> --}}

    <div class="navbar-actions ms-auto d-flex align-items-center gap-2">
      <!-- Theme Toggle -->
      {{-- <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme" title="Switch color theme">
        <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
      </button> --}}

      <!-- User Menu -->
      <div class="dropdown">
        <button class="profile-button dropdown-toggle d-flex align-items-center gap-2 border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="avatar-icon bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
            <i class="bi bi-person-fill fs-6"></i>
          </div>
          <span class="profile-name d-none d-sm-inline font-semibold text-dark">{{ Auth::user()->name ?? 'User Fleet' }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li>
            <div class="px-3 py-2 border-bottom">
              <strong class="d-block text-dark">{{ Auth::user()->name ?? 'User' }}</strong>
              <div class="mt-1">
                <span class="badge text-bg-warning text-dark"><i class="bi bi-shield-lock me-1"></i>{{ Auth::user()->role?->label ?? 'User' }}</span>
              </div>
              <small class="text-muted d-block mt-1">{{ Auth::user()->email ?? '' }}</small>
            </div>
          </li>
          <li>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="dropdown-item text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Sign out
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
