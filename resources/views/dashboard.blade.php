@extends('layouts.app')

@section('title', 'Dashboard Monitoring Kendaraan')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div>
    <h1 class="h3 mb-1">Dashboard Monitoring Kendaraan</h1>
    <p class="text-muted mb-0">Ringkasan operasional armada, grafik pemakaian kendaraan, konsumsi BBM, dan persetujuan.</p>
  </div>

  @if(Auth::user()->role?->name === 'admin')
  <div class="d-flex flex-wrap gap-2">
    <a href="{{ route('bookings.index') }}" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm">
      <i class="bi bi-plus-lg me-1"></i> Buat Pemesanan
    </a>
  </div>
  @endif
</div>

<!-- 1. PERSETUJUAN MENUNGGU TINDAKAN (POSISI ATAS AGAR TERLIHAT TANPA PERLU SCROLL) -->
@if($myPendingApprovals->count() > 0)
@php
  $isAdmin = Auth::user()->role?->name === 'admin';
@endphp
<div class="card border-warning border-2 shadow-sm rounded-3 mb-4">
  <div class="card-header bg-warning bg-opacity-10 border-warning border-opacity-25 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center py-3 gap-2">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-warning text-dark p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      </span>
      <div>
        <h5 class="mb-0 fw-bold text-dark">
          {{ $isAdmin ? 'Pengajuan Pemesanan Menunggu Persetujuan' : 'Persetujuan Menunggu Tindakan Anda' }}
        </h5>
        <small class="text-secondary">
          @if($isAdmin)
            Terdapat <strong class="text-dark">{{ $myPendingApprovals->count() }} pengajuan pemesanan aktif</strong> yang sedang dalam proses persetujuan oleh Supervisor / Manager.
          @else
            Terdapat <strong class="text-dark">{{ $myPendingApprovals->count() }} pengajuan pemesanan</strong> yang memerlukan respon / persetujuan dari Anda ({{ Auth::user()->role?->label ?? Auth::user()->role?->name }}).
          @endif
        </small>
      </div>
    </div>
    <a href="{{ route('approvals.index') }}" class="btn btn-warning btn-sm fw-semibold shadow-sm text-dark text-nowrap">
      <i class="bi bi-check2-square me-1"></i> Buka Menu Persetujuan
    </a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3 text-nowrap">Kode Booking</th>
            <th>Pemohon</th>
            <th>Kendaraan & Driver</th>
            <th>Rute Perjalanan</th>
            <th>Jadwal Trip</th>
            <th class="text-center">Level Approval</th>
            <th class="text-center pe-3">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($myPendingApprovals as $approval)
            @php
              $vName = $approval->booking?->vehicle ? $approval->booking->vehicle->name . ' (' . $approval->booking->vehicle->license_plate . ')' : '-';
              $dName = $approval->booking?->driver ? $approval->booking->driver->name : '-';
              $startLoc = $approval->booking?->startLocation ? $approval->booking->startLocation->name : ($approval->booking?->start_address ?? '-');
              $destLoc = $approval->booking?->destinationLocation ? $approval->booking->destinationLocation->name : ($approval->booking?->destination_address ?? '-');
              $startDt = $approval->booking?->start_date ? $approval->booking->start_date->format('d/m/Y H:i') : '-';
              $endDt = $approval->booking?->end_date ? $approval->booking->end_date->format('d/m/Y H:i') : '-';
            @endphp
            <tr>
              <td class="ps-3"><strong class="font-monospace text-primary">{{ $approval->booking?->booking_code ?? '-' }}</strong></td>
              <td>
                <strong class="d-block text-dark">{{ $approval->booking?->user?->name ?? '-' }}</strong>
                <small class="text-muted">{{ $approval->booking?->user?->role?->label ?? '-' }}</small>
              </td>
              <td>
                <strong class="d-block text-dark"><i class="bi bi-truck me-1"></i>{{ $vName }}</strong>
                <small class="text-secondary"><i class="bi bi-person me-1"></i>Driver: {{ $dName }}</small>
              </td>
              <td>
                <small class="d-block text-dark fw-semibold"><i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $startLoc }}</small>
                <small class="d-block text-secondary"><i class="bi bi-arrow-down me-1"></i>{{ $destLoc }}</small>
              </td>
              <td>
                <small class="d-block font-monospace">{{ $startDt }}</small>
                <small class="d-block text-muted font-monospace">s/d {{ $endDt }}</small>
              </td>
              <td class="text-center">
                @if($approval->approval_level == 1)
                  <span class="badge text-bg-warning text-dark">Level 1 (SPV)</span>
                @else
                  <span class="badge text-bg-info">Level 2 (Manager)</span>
                @endif
              </td>
              <td class="text-center pe-3">
                <a href="{{ route('approvals.index') }}" class="btn btn-sm btn-outline-primary fw-semibold" title="{{ $isAdmin ? 'Tinjau Status Persetujuan' : 'Buka Detail & Beri Persetujuan' }}">
                  <i class="bi bi-box-arrow-up-right me-1"></i> {{ $isAdmin ? 'Tinjau' : 'Proses' }}
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif

<!-- 2. TOP METRIC CARDS -->
<div class="row g-3 mb-4">
  <!-- Card 1: Total Armada Kendaraan -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-primary-subtle text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
          <i class="bi bi-truck fs-3"></i>
        </div>
        <div class="flex-grow-1 overflow-hidden">
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.7rem;">TOTAL ARMADA KENDARAAN</small>
          <h4 class="mb-1 fw-bold text-dark">{{ number_format($totalVehicles, 0, ',', '.') }} <small class="fs-6 fw-normal text-muted">Unit</small></h4>
          <div class="d-flex gap-2 flex-wrap">
            <small class="text-secondary"><i class="bi bi-people me-1"></i>{{ $passengerVehicles }} Org</small>
            <small class="text-secondary"><i class="bi bi-box-seam me-1"></i>{{ $cargoVehicles }} Brg</small>
            <small class="text-muted">({{ $companyVehicles }} Milik / {{ $rentedVehicles }} Sewa)</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 2: Pemesanan & Operasi -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-success-subtle text-success p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
          <i class="bi bi-journal-check fs-3"></i>
        </div>
        <div class="flex-grow-1 overflow-hidden">
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.7rem;">PEMESANAN KENDARAAN</small>
          <h4 class="mb-1 fw-bold text-dark">{{ number_format($totalBookings, 0, ',', '.') }} <small class="fs-6 fw-normal text-muted">Trip</small></h4>
          <div class="d-flex gap-2 flex-wrap">
            <small class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>{{ $completedBookings }} Selesai</small>
            <small class="text-warning-emphasis fw-semibold"><i class="bi bi-hourglass-split me-1"></i>{{ $pendingBookings }} Pending</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 3: Konsumsi Bahan Bakar (BBM) -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-warning-subtle text-warning-emphasis p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
          <i class="bi bi-fuel-pump fs-3"></i>
        </div>
        <div class="flex-grow-1 overflow-hidden">
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.7rem;">KONSUMSI BBM</small>
          <h4 class="mb-1 fw-bold text-dark">Rp {{ number_format($totalFuelCost, 0, ',', '.') }}</h4>
          <div class="d-flex gap-2 flex-wrap">
            <small class="text-muted"><i class="bi bi-droplet-fill text-warning me-1"></i>{{ number_format($totalFuelLiters, 2, ',', '.') }} Liter BBM</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 4: Biaya Pemeliharaan & Servis -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-danger-subtle text-danger p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
          <i class="bi bi-tools fs-3"></i>
        </div>
        <div class="flex-grow-1 overflow-hidden">
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.7rem;">BIAYA SERVIS & PERAWATAN</small>
          <h4 class="mb-1 fw-bold text-dark">Rp {{ number_format($totalServiceCost, 0, ',', '.') }}</h4>
          <div class="d-flex gap-2 flex-wrap">
            <small class="text-danger fw-semibold"><i class="bi bi-wrench-adjustable me-1"></i>{{ $maintenanceVehiclesCount }} Unit Dalam Servis</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 3. CHARTS ROW 1: Tren Pemakaian & Status Kesiapan Armada Realtime -->
<div class="row g-3 mb-4">
  <!-- Grafik Tren Pemakaian Kendaraan -->
  <div class="col-12 col-xl-8">
    <div class="panel shadow-sm h-100 p-4">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 pb-2 border-bottom gap-2">
        <div>
          <h2 class="h5 fw-bold mb-1 text-dark">
            <i class="bi bi-graph-up text-primary me-2"></i>Tren Pemakaian Kendaraan (6 Bulan Terakhir)
          </h2>
          <p class="text-muted small mb-0">Statistik perbandingan pemesanan disetujui, trip selesai, dan pemesanan ditolak.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace">Bulanan</span>
      </div>
      <div style="height: 280px; position: relative;">
        <canvas id="chartUsageTrend"></canvas>
      </div>
    </div>
  </div>

  <!-- Grafik Status Kesiapan Armada Real-time (Termasuk Dipesan / Reserved) -->
  <div class="col-12 col-xl-4">
    <div class="panel shadow-sm h-100 p-4">
      <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
          <h2 class="h5 fw-bold mb-1 text-dark">
            <i class="bi bi-pie-chart-fill text-success me-2"></i>Status Kesiapan Armada
          </h2>
          <p class="text-muted small mb-0">Kondisi ketersediaan seluruh unit armada saat ini.</p>
        </div>
      </div>
      <div style="height: 280px; position: relative;" class="d-flex align-items-center justify-content-center">
        <canvas id="chartVehicleStatus"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- 4. CHARTS ROW 2: Top Kendaraan Teraktif (Hanya Selesai) & Distribusi per Pool / Tambang -->
<div class="row g-3 mb-4">
  <!-- Grafik Top 5 Kendaraan Paling Sering Digunakan (Hanya Pemesanan Selesai) -->
  <div class="col-12 col-xl-6">
    <div class="panel shadow-sm h-100 p-4">
      <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
          <h2 class="h5 fw-bold mb-1 text-dark">
            <i class="bi bi-bar-chart-fill text-warning me-2"></i>Top 5 Kendaraan Paling Sering Digunakan
          </h2>
          <p class="text-muted small mb-0">Dihitung dari pemesanan kendaraan yang telah <strong>selesai (completed trips)</strong>.</p>
        </div>
      </div>
      <div style="height: 260px; position: relative;">
        <canvas id="chartTopVehicles"></canvas>
      </div>
    </div>
  </div>

  <!-- Grafik Distribusi Armada per Lokasi Tambang -->
  <div class="col-12 col-xl-6">
    <div class="panel shadow-sm h-100 p-4">
      <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
          <h2 class="h5 fw-bold mb-1 text-dark">
            <i class="bi bi-geo-alt-fill text-danger me-2"></i>Distribusi Armada per Pool & Lokasi Tambang
          </h2>
          <p class="text-muted small mb-0">Sebaran penempatan kendaraan di kantor dan 6 site tambang.</p>
        </div>
      </div>
      <div style="height: 260px; position: relative;">
        <canvas id="chartLocationDistribution"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- 5. BOTTOM SECTION: Pemesanan Terkini & Log Aktivitas (Admin Only) -->
<div class="row g-3">
  @if(Auth::user()->role?->name === 'admin')
    <!-- Kolom Kiri: Pemesanan Kendaraan Terkini (Admin Mode) -->
    <div class="col-12 col-xl-7">
      <div class="panel shadow-sm h-100 p-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 pb-2 border-bottom gap-2">
          <div>
            <h2 class="h5 fw-bold mb-1 text-dark">
              <i class="bi bi-journal-text text-primary me-2"></i>Pemesanan Kendaraan Terkini
            </h2>
            <p class="text-muted small mb-0">Aktivitas riwayat pemesanan armada terbaru.</p>
          </div>
          <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-primary fw-semibold">
            <i class="bi bi-arrow-right-circle me-1"></i> Semua Pemesanan
          </a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3 text-nowrap">Kode</th>
                <th>Pemohon</th>
                <th>Kendaraan</th>
                <th>Rute</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentBookings as $b)
                @php
                  $vName = $b->vehicle ? $b->vehicle->name . ' (' . $b->vehicle->license_plate . ')' : '-';
                  $startLoc = $b->startLocation ? $b->startLocation->name : ($b->start_address ?? '-');
                  $destLoc = $b->destinationLocation ? $b->destinationLocation->name : ($b->destination_address ?? '-');
                @endphp
                <tr>
                  <td class="ps-3"><strong class="font-monospace text-primary">{{ $b->booking_code }}</strong></td>
                  <td>
                    <strong class="d-block text-dark">{{ $b->user?->name ?? '-' }}</strong>
                    <small class="text-muted">{{ $b->user?->role?->label ?? '-' }}</small>
                  </td>
                  <td>
                    <strong class="d-block text-dark small"><i class="bi bi-truck me-1"></i>{{ $vName }}</strong>
                    <small class="text-secondary"><i class="bi bi-person me-1"></i>{{ $b->driver->name ?? '-' }}</small>
                  </td>
                  <td>
                    <small class="d-block text-dark fw-semibold"><i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $startLoc }}</small>
                    <small class="d-block text-secondary"><i class="bi bi-arrow-down me-1"></i>{{ $destLoc }}</small>
                  </td>
                  <td class="text-center">
                    @if($b->status === 'completed')
                      <span class="badge p-1 text-bg-success">Selesai</span>
                    @elseif($b->status === 'approved')
                      <span class="badge p-1 text-bg-info">Disetujui</span>
                    @elseif($b->status === 'in_progress')
                      <span class="badge p-1 text-bg-primary">Berjalan</span>
                    @elseif($b->status === 'pending')
                      <span class="badge p-1 text-bg-warning text-dark">Pending</span>
                    @elseif($b->status === 'rejected')
                      <span class="badge p-1 text-bg-danger">Ditolak</span>
                    @elseif($b->status === 'cancelled')
                      <span class="badge p-1 text-bg-secondary">Dibatalkan</span>
                    @else
                      <span class="badge p-1 text-bg-secondary">{{ ucfirst($b->status) }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">Belum ada data pemesanan.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Kolom Kanan: Feed Log Aktivitas (Khusus Role Admin) -->
    <div class="col-12 col-xl-5">
      <div class="panel shadow-sm h-100 p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
          <div>
            <h2 class="h5 fw-bold mb-1 text-dark">
              <i class="bi bi-clock-history text-info me-2"></i>Log Aktivitas Terkini (Audit Trail)
            </h2>
            <p class="text-muted small mb-0">Catatan kronologis aktivitas sistem terbaru.</p>
          </div>
          <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-secondary">Semua Log</a>
        </div>

        <div class="activity-feed" style="max-height: 310px; overflow-y: auto;">
          @forelse($recentActivities as $log)
            <div class="d-flex gap-3 mb-3 pb-2 border-bottom align-items-start">
              <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                <i class="bi bi-person-fill fs-6"></i>
              </div>
              <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center">
                  <strong class="text-dark small">{{ $log->user->name ?? 'Sistem' }}</strong>
                  <small class="text-muted font-monospace" style="font-size: 0.725rem;">{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</small>
                </div>
                <p class="mb-0 text-secondary small text-truncate" title="{{ $log->description }}">{{ $log->description }}</p>
              </div>
            </div>
          @empty
            <div class="text-center py-4 text-muted">Belum ada aktivitas tercatat.</div>
          @endforelse
        </div>
      </div>
    </div>

  @else
    <!-- Non-Admin Mode: Pemesanan Kendaraan Terkini (Full Width) -->
    <div class="col-12">
      <div class="panel shadow-sm h-100 p-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 pb-2 border-bottom gap-2">
          <div>
            <h2 class="h5 fw-bold mb-1 text-dark">
              <i class="bi bi-journal-text text-primary me-2"></i>Pemesanan Kendaraan Terkini
            </h2>
            <p class="text-muted small mb-0">Aktivitas riwayat pemesanan armada terbaru dalam sistem.</p>
          </div>
          <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-primary fw-semibold">
            <i class="bi bi-arrow-right-circle me-1"></i> Buka Seluruh Pemesanan
          </a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3 text-nowrap">Kode Booking</th>
                <th>Pemohon</th>
                <th>Kendaraan & Driver</th>
                <th>Rute Perjalanan</th>
                <th>Jadwal Pelaksanaan</th>
                <th class="text-center">Status Pemesanan</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentBookings as $b)
                @php
                  $vName = $b->vehicle ? $b->vehicle->name . ' (' . $b->vehicle->license_plate . ')' : '-';
                  $dName = $b->driver ? $b->driver->name : '-';
                  $startLoc = $b->startLocation ? $b->startLocation->name : ($b->start_address ?? '-');
                  $destLoc = $b->destinationLocation ? $b->destinationLocation->name : ($b->destination_address ?? '-');
                  $startDt = $b->start_date ? $b->start_date->format('d/m/Y H:i') : '-';
                  $endDt = $b->end_date ? $b->end_date->format('d/m/Y H:i') : '-';
                @endphp
                <tr>
                  <td class="ps-3"><strong class="font-monospace text-primary">{{ $b->booking_code }}</strong></td>
                  <td>
                    <strong class="d-block text-dark">{{ $b->user?->name ?? '-' }}</strong>
                    <small class="text-muted">{{ $b->user?->role?->label ?? '-' }}</small>
                  </td>
                  <td>
                    <strong class="d-block text-dark"><i class="bi bi-truck me-1"></i>{{ $vName }}</strong>
                    <small class="text-secondary"><i class="bi bi-person me-1"></i>Driver: {{ $dName }}</small>
                  </td>
                  <td>
                    <small class="d-block text-dark fw-semibold"><i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $startLoc }}</small>
                    <small class="d-block text-secondary"><i class="bi bi-arrow-down me-1"></i>{{ $destLoc }}</small>
                  </td>
                  <td>
                    <small class="d-block font-monospace">{{ $startDt }}</small>
                    <small class="d-block text-muted font-monospace">s/d {{ $endDt }}</small>
                  </td>
                  <td class="text-center">
                    @if($b->status === 'completed')
                      <span class="badge p-1 text-bg-success">Selesai</span>
                    @elseif($b->status === 'approved')
                      <span class="badge p-1 text-bg-info">Disetujui</span>
                    @elseif($b->status === 'in_progress')
                      <span class="badge p-1 text-bg-primary">Berjalan</span>
                    @elseif($b->status === 'pending')
                      <span class="badge p-1 text-bg-warning text-dark">Pending</span>
                    @elseif($b->status === 'rejected')
                      <span class="badge p-1 text-bg-danger">Ditolak</span>
                    @elseif($b->status === 'cancelled')
                      <span class="badge p-1 text-bg-secondary">Dibatalkan</span>
                    @else
                      <span class="badge p-1 text-bg-secondary">{{ ucfirst($b->status) }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">Belum ada data pemesanan kendaraan tercatat.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection

@section('scripts')
<script>
  $(document).ready(function() {
    // 1. Grafik Tren Pemakaian Kendaraan (Line/Area Chart)
    const ctxTrend = document.getElementById('chartUsageTrend');
    if (ctxTrend) {
      new Chart(ctxTrend.getContext('2d'), {
        type: 'line',
        data: {
          labels: {!! json_encode($monthlyLabels) !!},
          datasets: [
            {
              label: 'Pesanan Disetujui',
              data: {!! json_encode($monthlyApproved) !!},
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13, 110, 253, 0.08)',
              fill: false,
              tension: 0.35,
              borderWidth: 2.5,
              pointBackgroundColor: '#0d6efd',
              pointRadius: 4,
              pointHoverRadius: 6
            },
            {
              label: 'Trip Selesai',
              data: {!! json_encode($monthlyCompleted) !!},
              borderColor: '#198754',
              backgroundColor: 'rgba(25, 135, 84, 0.08)',
              fill: false,
              tension: 0.35,
              borderWidth: 2.5,
              pointBackgroundColor: '#198754',
              pointRadius: 4,
              pointHoverRadius: 6
            },
            {
              label: 'Pesanan Ditolak',
              data: {!! json_encode($monthlyRejected) !!},
              borderColor: '#dc3545',
              backgroundColor: 'rgba(220, 53, 69, 0.08)',
              fill: false,
              tension: 0.35,
              borderWidth: 2.5,
              pointBackgroundColor: '#dc3545',
              pointRadius: 4,
              pointHoverRadius: 6
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: {
                boxWidth: 12,
                font: { size: 12, weight: '600' }
              }
            },
            tooltip: {
              padding: 10,
              boxPadding: 4
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1, precision: 0 },
              grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
              grid: { display: false }
            }
          }
        }
      });
    }

    // 2. Grafik Status Kesiapan Armada (Doughnut Chart: Tersedia, Dipesan, Digunakan, Servis)
    const ctxStatus = document.getElementById('chartVehicleStatus');
    if (ctxStatus) {
      new Chart(ctxStatus.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: {!! json_encode(array_keys($vehicleStatusCounts)) !!},
          datasets: [{
            data: {!! json_encode(array_values($vehicleStatusCounts)) !!},
            backgroundColor: ['#198754', '#ffc107', '#0d6efd', '#dc3545'],
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                font: { size: 11, weight: '500' }
              }
            }
          },
          cutout: '68%'
        }
      });
    }

    // 3. Grafik Top 5 Kendaraan Teraktif - Hanya Pemesanan Selesai (Horizontal Bar Chart)
    const ctxTopVehicles = document.getElementById('chartTopVehicles');
    if (ctxTopVehicles) {
      new Chart(ctxTopVehicles.getContext('2d'), {
        type: 'bar',
        data: {
          labels: {!! json_encode($topVehicleLabels) !!},
          datasets: [{
            label: 'Total Trip Selesai',
            data: {!! json_encode($topVehicleCounts) !!},
            backgroundColor: '#ffc107',
            borderColor: '#e0a800',
            borderWidth: 1,
            borderRadius: 6
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: {
              beginAtZero: true,
              ticks: { stepSize: 1, precision: 0 },
              grid: { color: 'rgba(0,0,0,0.05)' }
            },
            y: {
              grid: { display: false },
              ticks: {
                font: { size: 11, weight: '500' }
              }
            }
          }
        }
      });
    }

    // 4. Grafik Distribusi Armada per Lokasi Tambang (Bar Chart)
    const ctxLocation = document.getElementById('chartLocationDistribution');
    if (ctxLocation) {
      new Chart(ctxLocation.getContext('2d'), {
        type: 'bar',
        data: {
          labels: {!! json_encode($locationLabels) !!},
          datasets: [{
            label: 'Jumlah Unit Armada',
            data: {!! json_encode($locationCounts) !!},
            backgroundColor: '#0dcaf0',
            borderColor: '#0aa2c0',
            borderWidth: 1,
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1, precision: 0 },
              grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
              grid: { display: false },
              ticks: {
                font: { size: 10, weight: '500' },
                maxRotation: 45,
                minRotation: 20
              }
            }
          }
        }
      });
    }
  });
</script>
@endsection
