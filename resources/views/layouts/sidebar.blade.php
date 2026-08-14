<aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
  <div class="sidebar-header">
    <a class="brand-mark" href="{{ route('dashboard') }}" aria-label="Nickel Fleet dashboard">
      <span class="brand-icon bg-warning text-dark"><i class="bi bi-truck-front-fill" aria-hidden="true"></i></span>
      <span class="brand-copy">
        <span class="brand-title fw-bold">NICKEL FLEET</span>
        <span class="brand-subtitle">Tambang Nikel Ops</span>
      </span>
    </a>
  </div>

  <nav class="sidebar-nav">
    <!-- Dashboard -->
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
      <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
      <span class="nav-text">Dashboard</span>
    </a>

    <!-- Pemesanan Kendaraan -->
    <a class="nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-journal-check" aria-hidden="true"></i></span>
      <span class="nav-text">Pemesanan Kendaraan</span>
    </a>

    <!-- Persetujuan / Approvals -->
    <a class="nav-link {{ request()->routeIs('approvals.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-check2-square" aria-hidden="true"></i></span>
      <span class="nav-text">Persetujuan (Approvals)</span>
    </a>

    <!-- Master Data Section -->
    <div class="sidebar-divider my-2 border-top border-secondary-subtle"></div>
    <small class="px-3 text-uppercase text-muted font-monospace fw-bold" style="font-size: 0.7rem;">MASTER DATA</small>

    <a class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-truck" aria-hidden="true"></i></span>
      <span class="nav-text">Data Kendaraan</span>
    </a>

    <a class="nav-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
      <span class="nav-text">Data Driver</span>
    </a>

    <a class="nav-link {{ request()->routeIs('rental-companies.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-building" aria-hidden="true"></i></span>
      <span class="nav-text">Perusahaan Sewa</span>
    </a>

    <a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
      <span class="nav-text">Lokasi & Region</span>
    </a>

    <!-- Monitoring Section -->
    <div class="sidebar-divider my-2 border-top border-secondary-subtle"></div>
    <small class="px-3 text-uppercase text-muted font-monospace fw-bold" style="font-size: 0.7rem;">MONITORING</small>

    <a class="nav-link {{ request()->routeIs('fuel-logs.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-fuel-pump" aria-hidden="true"></i></span>
      <span class="nav-text">Konsumsi BBM</span>
    </a>

    <a class="nav-link {{ request()->routeIs('service-logs.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-tools" aria-hidden="true"></i></span>
      <span class="nav-text">Riwayat Service</span>
    </a>

    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i></span>
      <span class="nav-text">Laporan (Export Excel)</span>
    </a>

    <a class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="#">
      <span class="nav-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
      <span class="nav-text">Log Aktivitas</span>
    </a>
  </nav>

  <!-- Sidebar User Badge dengan Bootstrap Icon -->
  <div class="sidebar-user d-flex align-items-center gap-2">
    <div class="avatar-icon bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
      <i class="bi bi-person-fill fs-5"></i>
    </div>
    <div class="lh-sm overflow-hidden">
      <strong class="d-block text-truncate" style="max-width: 130px;">{{ Auth::user()->name ?? 'User Fleet' }}</strong>
      <small class="badge bg-warning text-dark">{{ Auth::user()->role?->label ?? 'Staff' }}</small>
    </div>
  </div>

  <div class="sidebar-footer">
    <span class="status-dot"></span>
    <span class="sidebar-footer-text">Operational System Active</span>
  </div>
</aside>
