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

<!-- TOP METRIC CARDS -->
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

<!-- CHARTS ROW 1: Tren Pemakaian & Status Kesiapan Armada -->
<div class="row g-3 mb-4">
  <!-- Grafik Tren Pemakaian Kendaraan -->
  <div class="col-12 col-xl-8">
    <div class="panel shadow-sm h-100 p-4">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 pb-2 border-bottom gap-2">
        <div>
          <h2 class="h5 fw-bold mb-1 text-dark">
            <i class="bi bi-graph-up text-primary me-2"></i>Tren Pemakaian Kendaraan (6 Bulan Terakhir)
          </h2>
          <p class="text-muted small mb-0">Statistik frekuensi pemesanan kendaraan dan trip operasional yang telah selesai.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace">Bulanan</span>
      </div>
      <div style="height: 280px; position: relative;">
        <canvas id="chartUsageTrend"></canvas>
      </div>
    </div>
  </div>

  <!-- Grafik Status Kesiapan Armada -->
  <div class="col-12 col-xl-4">
    <div class="panel shadow-sm h-100 p-4">
      <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
          <h2 class="h5 fw-bold mb-1 text-dark">
            <i class="bi bi-pie-chart-fill text-success me-2"></i>Status Kesiapan Armada
          </h2>
          <p class="text-muted small mb-0">Kondisi ketersediaan seluruh unit saat ini.</p>
        </div>
      </div>
      <div style="height: 280px; position: relative;" class="d-flex align-items-center justify-content-center">
        <canvas id="chartVehicleStatus"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- CHARTS ROW 2: Top Kendaraan Teraktif & Distribusi per Pool / Tambang -->
<div class="row g-3 mb-4">
  <!-- Grafik Top 5 Kendaraan Teraktif -->
  <div class="col-12 col-xl-6">
    <div class="panel shadow-sm h-100 p-4">
      <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
          <h2 class="h5 fw-bold mb-1 text-dark">
            <i class="bi bi-bar-chart-fill text-warning me-2"></i>Top 5 Kendaraan Paling Sering Digunakan
          </h2>
          <p class="text-muted small mb-0">Armada dengan intensitas pemakaian & trip tertinggi.</p>
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

<!-- BOTTOM SECTION: Persetujuan / Pemesanan Terkini & Audit Log Feed -->
<div class="row g-3">
  <!-- Kolom Kiri: Persetujuan Menunggu Tindakan / Pemesanan Terkini -->
  <div class="col-12 col-lg-6">
    <div class="panel shadow-sm h-100 p-4">
      @if($myPendingApprovals->count() > 0)
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
          <div>
            <h2 class="h5 fw-bold mb-1 text-dark">
              <i class="bi bi-clock-history text-warning me-2"></i>Persetujuan Menunggu Tindakan
            </h2>
            <p class="text-muted small mb-0">Pengajuan pemesanan yang memerlukan persetujuan Anda.</p>
          </div>
          <span class="badge bg-warning text-dark">{{ $myPendingApprovals->count() }} Menunggu</span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Kode Booking</th>
                <th>Kendaraan</th>
                <th>Pemohon</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($myPendingApprovals as $approval)
                <tr>
                  <td><strong class="font-monospace text-primary">{{ $approval->booking->booking_code }}</strong></td>
                  <td>
                    <strong>{{ $approval->booking->vehicle->name ?? '-' }}</strong>
                    <small class="text-muted d-block font-monospace">({{ $approval->booking->vehicle->license_plate ?? '-' }})</small>
                  </td>
                  <td>{{ $approval->booking->user->name ?? '-' }}</td>
                  <td class="text-center">
                    <a href="{{ route('approvals.index') }}" class="btn btn-sm btn-outline-primary">
                      Tinjau
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
          <div>
            <h2 class="h5 fw-bold mb-1 text-dark">
              <i class="bi bi-journal-text text-primary me-2"></i>Pemesanan Kendaraan Terkini
            </h2>
            <p class="text-muted small mb-0">Aktivitas pemesanan armada terbaru dalam sistem.</p>
          </div>
          <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Kode</th>
                <th>Kendaraan</th>
                <th>Pemohon</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentBookings as $b)
                <tr>
                  <td><strong class="font-monospace text-primary">{{ $b->booking_code }}</strong></td>
                  <td>
                    <strong>{{ $b->vehicle->name ?? '-' }}</strong>
                    <small class="text-muted d-block">Driver: {{ $b->driver->name ?? '-' }}</small>
                  </td>
                  <td>{{ $b->user->name ?? '-' }}</td>
                  <td class="text-center">
                    @if($b->status === 'completed')
                      <span class="badge p-1 text-bg-success">Selesai</span>
                    @elseif($b->status === 'approved' || $b->status === 'in_progress')
                      <span class="badge p-1 text-bg-info">Disetujui</span>
                    @elseif($b->status === 'pending')
                      <span class="badge p-1 text-bg-warning text-dark">Pending</span>
                    @elseif($b->status === 'rejected')
                      <span class="badge p-1 text-bg-danger">Ditolak</span>
                    @else
                      <span class="badge p-1 text-bg-secondary">{{ ucfirst($b->status) }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-3">Belum ada pemesanan kendaraan.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  <!-- Kolom Kanan: Feed Log Aktivitas (Audit Trail) -->
  <div class="col-12 col-lg-6">
    <div class="panel shadow-sm h-100 p-4">
      <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
          <h2 class="h5 fw-bold mb-1 text-dark">
            <i class="bi bi-clock-history text-info me-2"></i>Log Aktivitas Terkini (Audit Trail)
          </h2>
          <p class="text-muted small mb-0">Catatan kronologis perubahan data operasional oleh pengguna.</p>
        </div>
        <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-secondary">Semua Log</a>
      </div>

      <div class="activity-feed" style="max-height: 280px; overflow-y: auto;">
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
              label: 'Total Pemesanan',
              data: {!! json_encode($monthlyTotals) !!},
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13, 110, 253, 0.12)',
              fill: true,
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
              fill: true,
              tension: 0.35,
              borderWidth: 2.5,
              pointBackgroundColor: '#198754',
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

    // 2. Grafik Status Kesiapan Armada (Doughnut Chart)
    const ctxStatus = document.getElementById('chartVehicleStatus');
    if (ctxStatus) {
      new Chart(ctxStatus.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: {!! json_encode(array_keys($vehicleStatusCounts)) !!},
          datasets: [{
            data: {!! json_encode(array_values($vehicleStatusCounts)) !!},
            backgroundColor: ['#198754', '#0d6efd', '#dc3545'],
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

    // 3. Grafik Top 5 Kendaraan Teraktif (Horizontal Bar Chart)
    const ctxTopVehicles = document.getElementById('chartTopVehicles');
    if (ctxTopVehicles) {
      new Chart(ctxTopVehicles.getContext('2d'), {
        type: 'bar',
        data: {
          labels: {!! json_encode($topVehicleLabels) !!},
          datasets: [{
            label: 'Total Trip / Pemakaian',
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
