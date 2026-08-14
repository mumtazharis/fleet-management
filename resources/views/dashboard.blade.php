@extends('layouts.app')

@section('title', 'Dashboard Monitoring Kendaraan')

@section('content')
<!-- Page Heading -->
<div class="page-heading mb-4">
  <div class="page-heading-copy">
    <span class="page-icon bg-warning text-dark"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
    <div>
      <p class="eyebrow mb-1 text-warning fw-bold"><i class="bi bi-shield-check"></i> Monitoring Operational Fleet</p>
      <h1 class="h3 mb-1">Dashboard Monitoring Kendaraan Tambang</h1>
      <p class="text-muted mb-0">Pantau operasional kendaraan, pemesanan, konsumsi BBM, dan riwayat service di seluruh lokasi tambang.</p>
    </div>
  </div>
</div>

<!-- TOP METRIC CARDS -->
<div class="row g-3 mb-4">
  <!-- Card 1: Total Kendaraan -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="panel h-100 border-start border-4 border-primary">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <span class="text-muted small fw-semibold">TOTAL KENDARAAN</span>
          <h2 class="h3 my-1 fw-bold">{{ $totalVehicles }} <small class="fs-6 fw-normal text-muted">unit</small></h2>
          <div class="d-flex gap-2 mt-2">
            <span class="badge text-bg-primary"><i class="bi bi-person-fill me-1"></i> {{ $passengerVehicles }} Orang</span>
            <span class="badge text-bg-info"><i class="bi bi-box-seam-fill me-1"></i> {{ $cargoVehicles }} Barang</span>
          </div>
        </div>
        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3">
          <i class="bi bi-truck fs-2"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 2: Status Kepemilikan -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="panel h-100 border-start border-4 border-success">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <span class="text-muted small fw-semibold">KEPEMILIKAN KENDARAAN</span>
          <h2 class="h3 my-1 fw-bold">{{ $companyVehicles + $rentedVehicles }} <small class="fs-6 fw-normal text-muted">unit</small></h2>
          <div class="d-flex gap-2 mt-2">
            <span class="badge text-bg-success"><i class="bi bi-building-check me-1"></i> {{ $companyVehicles }} Milik sendiri</span>
            <span class="badge text-bg-warning text-dark"><i class="bi bi-journal-bookmark-fill me-1"></i> {{ $rentedVehicles }} Sewa</span>
          </div>
        </div>
        <div class="p-3 bg-success bg-opacity-10 text-success rounded-3">
          <i class="bi bi-journal-text fs-2"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 3: Pemesanan Kendaraan -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="panel h-100 border-start border-4 border-warning">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <span class="text-muted small fw-semibold">PEMESANAN KENDARAAN</span>
          <h2 class="h3 my-1 fw-bold">{{ $totalBookings }} <small class="fs-6 fw-normal text-muted">transaksi</small></h2>
          <div class="d-flex gap-1 flex-wrap mt-2">
            <span class="badge text-bg-warning"><i class="bi bi-hourglass-split me-1"></i> {{ $pendingBookings }} Pending</span>
            <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> {{ $approvedBookings }} Disetujui</span>
          </div>
        </div>
        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3">
          <i class="bi bi-calendar2-check fs-2"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 4: Total Biaya BBM & Service -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="panel h-100 border-start border-4 border-danger">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <span class="text-muted small fw-semibold">MONITORING BIAYA</span>
          <h2 class="h5 my-1 fw-bold text-danger">Rp {{ number_format($totalFuelCost + $totalServiceCost, 0, ',', '.') }}</h2>
          <div class="d-flex gap-1 flex-wrap mt-2">
            <span class="badge text-bg-secondary" title="Total Biaya BBM"><i class="bi bi-fuel-pump me-1"></i> Rp {{ number_format($totalFuelCost, 0, ',', '.') }}</span>
            <span class="badge text-bg-danger" title="Total Biaya Service"><i class="bi bi-tools me-1"></i> Rp {{ number_format($totalServiceCost, 0, ',', '.') }}</span>
          </div>
        </div>
        <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3">
          <i class="bi bi-cash-coin fs-2"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CHARTS SECTION -->
<div class="row g-3 mb-4">
  <!-- Chart 1: Distribusi Kendaraan per Lokasi -->
  <div class="col-12 col-lg-8">
    <div class="panel h-100 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 section-title mb-0">
          <i class="bi bi-geo-alt-fill text-warning me-2"></i>
          <span>Distribusi Kendaraan per Lokasi / Tambang</span>
        </h2>
        <span class="badge text-bg-light border">HQ, Cabang & 6 Lokasi Tambang</span>
      </div>
      <div style="height: 280px; position: relative;">
        <canvas id="locationChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 2: Status Pemesanan -->
  <div class="col-12 col-lg-4">
    <div class="panel h-100 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 section-title mb-0">
          <i class="bi bi-pie-chart-fill text-primary me-2"></i>
          <span>Status Pemesanan</span>
        </h2>
      </div>
      <div style="height: 280px; position: relative;" class="d-flex align-items-center justify-content-center">
        <canvas id="statusChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- PENDING APPROVALS & RECENT ACTIVITIES -->
<div class="row g-3">
  <!-- Pending Approvals for Approver -->
  <div class="col-12 col-lg-6">
    <div class="panel h-100 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h2 class="h5 section-title mb-0">
          <i class="bi bi-clock-history text-warning me-2"></i>
          <span>Persetujuan Menunggu Tindakan</span>
        </h2>
        <span class="badge text-bg-warning">{{ count($myPendingApprovals) }} Menunggu</span>
      </div>

      @if($myPendingApprovals->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Kode Booking</th>
                <th>Kendaraan</th>
                <th>Pemesan</th>
                <th>Level Approval</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($myPendingApprovals as $approval)
                <tr>
                  <td><strong class="font-monospace text-primary">{{ $approval->booking->booking_code }}</strong></td>
                  <td>{{ $approval->booking->vehicle->name ?? '-' }}</td>
                  <td>{{ $approval->booking->user->name ?? '-' }}</td>
                  <td><span class="badge text-bg-info">Level {{ $approval->approval_level }}</span></td>
                  <td>
                    <button class="btn btn-sm btn-success" title="Setujui"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-sm btn-danger" title="Tolak"><i class="bi bi-x-lg"></i></button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-center py-4 text-muted">
          <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
          <p class="mb-0">Tidak ada pengajuan pemesanan yang menunggu persetujuan Anda saat ini.</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Activity Log Feed -->
  <div class="col-12 col-lg-6">
    <div class="panel h-100 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h2 class="h5 section-title mb-0">
          <i class="bi bi-activity text-info me-2"></i>
          <span>Log Aktivitas Aplikasi (Audit Trail)</span>
        </h2>
        <span class="badge text-bg-light border">Aktivitas Terbaru</span>
      </div>

      <div class="activity-feed" style="max-height: 280px; overflow-y: auto;">
        @forelse($recentActivities as $log)
          <div class="d-flex gap-3 mb-3 pb-2 border-bottom">
            <div class="bg-light border rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
              <i class="bi bi-person-fill text-primary"></i>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center">
                <strong class="small">{{ $log->user->name ?? 'System' }}</strong>
                <small class="text-muted font-monospace" style="font-size: 0.75rem;">{{ $log->created_at->diffForHumans() }}</small>
              </div>
              <p class="mb-0 text-secondary small">{{ $log->description }}</p>
            </div>
          </div>
        @empty
          <div class="text-center py-4 text-muted">Belum ada aktivitas tercatat.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  $(document).ready(function() {
    // 1. Chart Distribusi Lokasi (Bar Chart)
    const ctxLocation = document.getElementById('locationChart').getContext('2d');
    new Chart(ctxLocation, {
      type: 'bar',
      data: {
        labels: {!! json_encode($locationLabels) !!},
        datasets: [{
          label: 'Jumlah Kendaraan',
          data: {!! json_encode($locationCounts) !!},
          backgroundColor: '#0d6efd',
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
          y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
      }
    });

    // 2. Chart Status Pemesanan (Doughnut Chart)
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
      type: 'doughnut',
      data: {
        labels: {!! json_encode(array_keys($bookingStatusCounts)) !!},
        datasets: [{
          data: {!! json_encode(array_values($bookingStatusCounts)) !!},
          backgroundColor: ['#ffc107', '#198754', '#0dcaf5', '#dc3545'],
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  });
</script>
@endsection
