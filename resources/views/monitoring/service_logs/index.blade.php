@extends('layouts.app')

@section('title', 'Riwayat Service')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div>
    <h1 class="h3 mb-1">Riwayat & Pemeliharaan Servis Armada</h1>
    <p class="text-muted mb-0">Kelola & pantau perawatan berkala, perbaikan mesin, ganti oli, dan riwayat biaya servis kendaraan tambang.</p>
  </div>

  @if(Auth::user()->role?->name === 'admin')
  <button type="button" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm" id="btnCreateServiceLog">
    <i class="bi bi-plus-lg me-1"></i> Catat Servis Baru
  </button>
  @endif
</div>

<!-- STAT SUMMARY CARDS -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-success-subtle text-success p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-cash-coin fs-4"></i>
        </div>
        <div>
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">TOTAL BIAYA SERVIS</small>
          <h5 class="mb-0 fw-bold text-dark" id="statTotalCost">Rp {{ number_format($totalCost, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-primary-subtle text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-wrench-adjustable fs-4"></i>
        </div>
        <div>
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">TOTAL RIWAYAT SERVIS</small>
          <h5 class="mb-0 fw-bold text-dark" id="statTotalServices">{{ number_format($totalServices, 0, ',', '.') }} Kali Servis</h5>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-warning-subtle text-warning-emphasis p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-gear-wide-connected fs-4"></i>
        </div>
        <div>
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">SERVIS AKTIF (DALAM PROSES)</small>
          <h5 class="mb-0 fw-bold text-dark" id="statInMaintenanceCount">{{ number_format($inMaintenanceCount, 0, ',', '.') }} Unit</h5>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle w-100" id="tableServiceLogs">
      <thead class="table-light">
        <tr>
          <th class="text-center">No.</th>
          <th>Tanggal Servis</th>
          <th>Kendaraan & Pool</th>
          <th>Jenis Servis</th>
          <th>Biaya (Rp)</th>
          <th>Status Servis</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Data loaded dynamically via DataTables Server-Side -->
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL FORM INPUT / EDIT SERVIS -->
<div class="modal fade" id="serviceLogModal" tabindex="-1" aria-labelledby="serviceLogModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="serviceLogModalLabel">
          <i class="bi bi-tools me-2"></i><span id="modalTitle">Catat Servis Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formServiceLog" action="{{ route('service-logs.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="modal-body p-4">
          <div class="alert alert-info border-0 rounded-3 mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <small>Menambahkan kendaraan ke daftar servis akan <strong>otomatis mengubah status armada menjadi MAINTENANCE (Dalam Perawatan)</strong> dan status transaksi servis menjadi <strong>Dalam Servis</strong>.</small>
          </div>

          <div class="row g-3">
            <!-- Pilih Kendaraan (Available Only) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectVehicle">Pilih Kendaraan (Tersedia) <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectVehicle" name="vehicle_id" required>
                <option value="" selected disabled>-- Pilih Kendaraan Tersedia --</option>
                @foreach($availableVehicles as $v)
                  <option value="{{ $v->id }}">
                    {{ $v->name }} (Plat: {{ $v->license_plate }} | Pool: {{ $v->location?->name ?? '-' }})
                  </option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-vehicle_id">Pilih kendaraan yang tersedia.</div>
            </div>

            <!-- Jenis Servis -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputServiceType">Jenis Servis <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="inputServiceType" name="service_type" list="serviceTypeList" placeholder="Contoh: Servis Rutin / Ganti Oli" required>
              <datalist id="serviceTypeList">
                <option value="Servis Rutin Berkala"></option>
                <option value="Ganti Oli & Filter Mesin"></option>
                <option value="Tune Up & Injector Clean"></option>
                <option value="Perbaikan Rem & Kaki-kaki"></option>
                <option value="Ganti Ban / Spooring Balancing"></option>
                <option value="Overhaul Mesin / Transmisi"></option>
              </datalist>
              <div class="invalid-feedback" id="err-service_type">Jenis servis wajib diisi.</div>
            </div>

            <!-- Tanggal Servis -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputServiceDate">Tanggal Servis <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="inputServiceDate" name="service_date" value="{{ date('Y-m-d') }}" required>
              <div class="invalid-feedback" id="err-service_date">Tanggal servis wajib diisi.</div>
            </div>

            <!-- Biaya Servis -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputCost">Biaya Servis (Rp) <span class="text-danger">*</span></label>
              <input type="number" step="1" min="0" class="form-control" id="inputCost" name="cost" placeholder="Contoh: 1500000" required>
              <div class="invalid-feedback" id="err-cost">Biaya servis wajib diisi.</div>
            </div>

            <!-- Deskripsi / Rincian Perbaikan -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputDescription">Rincian Perbaikan / Sparepart yang Diganti</label>
              <textarea class="form-control" id="inputDescription" name="description" rows="3" placeholder="Catat suku cadang yang diganti, nama bengkel, atau garansi..."></textarea>
              <div class="invalid-feedback" id="err-description">Rincian perbaikan maksimal 1000 karakter.</div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary fw-bold" id="btnSaveServiceLog">
            <i class="bi bi-save me-1"></i> Simpan Catatan Servis
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL DETAIL SERVIS -->
<div class="modal fade" id="detailServiceLogModal" tabindex="-1" aria-labelledby="detailServiceLogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="detailServiceLogModalLabel">
          <i class="bi bi-info-circle me-2"></i>Rincian Pemeliharaan Servis
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="detailServiceLogContent">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  $(document).ready(function() {
    const serviceLogModalEl = document.getElementById('serviceLogModal');
    const serviceLogModal = new bootstrap.Modal(serviceLogModalEl);
    const detailModalEl = document.getElementById('detailServiceLogModal');
    const detailModal = new bootstrap.Modal(detailModalEl);

    const isAdmin = @json(Auth::user()->role?->name === 'admin');

    // 1. DataTables Server-Side Processing
    const tableServiceLogs = $('#tableServiceLogs').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('service-logs.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
        {
          data: 'service_date',
          name: 'service_date',
          render: function(data) {
            return formatDate(data);
          }
        },
        {
          data: 'vehicle',
          name: 'vehicle.name',
          render: function(data, type, row) {
            const vName = row.vehicle ? escapeHtml(row.vehicle.name) : 'Kendaraan Terhapus';
            const plate = row.vehicle ? escapeHtml(row.vehicle.license_plate) : '-';
            const location = row.vehicle && row.vehicle.location ? escapeHtml(row.vehicle.location.name) : '-';

            return `
              <div class="lh-sm">
                <span class="d-block text-dark fw-semibold">${vName} <small class="text-muted">(${plate})</small></span> 
                <small class="text-muted">Pool: ${location}</small>
              </div>
            `;
          }
        },
        {
          data: 'service_type',
          name: 'service_type',
          render: function(data) {
            return `${escapeHtml(data)}`;
          }
        },
        {
          data: 'cost',
          name: 'cost',
          render: function(data) {
            return `${formatRupiah(data)}`;
          }
        },
        {
          data: 'status',
          name: 'status',
          render: function(data) {
            if (data === 'completed') {
              return '<span class="badge p-1 text-bg-success">Selesai</span>';
            } else if (data === 'cancelled') {
              return '<span class="badge p-1 text-bg-secondary">Dibatalkan</span>';
            }
            return '<span class="badge p-1 text-bg-warning text-dark">Dalam Servis</span>';
          }
        },
        {
          data: 'id',
          name: 'id',
          orderable: false,
          searchable: false,
          width: '1%',
          className: 'text-center text-nowrap',
          render: function(data, type, row) {
            const btnDetail = `
              <button type="button" class="btn btn-outline-info btn-sm btn-detail" data-id="${data}" title="Rincian Servis">
                <i class="bi bi-eye me-1"></i> Detail
              </button>
            `;

            if (!isAdmin || row.status !== 'in_progress') {
              return btnDetail;
            }

            const vName = escapeHtml(row.vehicle ? row.vehicle.name : 'Kendaraan');

            const btnComplete = `
              <button type="button" class="btn btn-success btn-sm btn-complete-service ms-1" data-id="${data}" data-vehicle="${vName}" title="Selesaikan Servis & Kembalikan Status Armada ke Tersedia">
                <i class="bi bi-check2-circle me-1"></i> Selesai
              </button>
            `;

            const btnEdit = `
              <button type="button" class="btn btn-outline-warning btn-sm btn-edit ms-1" data-id="${data}" title="Edit Servis">
                <i class="bi bi-pencil me-1"></i> Edit
              </button>
            `;

            const btnCancel = `
              <button type="button" class="btn btn-outline-danger btn-sm btn-cancel-service ms-1" data-id="${data}" data-vehicle="${vName}" title="Batalkan Servis">
                <i class="bi bi-x-circle me-1"></i> Batalkan
              </button>
            `;

            return `<div class="btn-group btn-group-sm">${btnDetail}${btnComplete}${btnEdit}${btnCancel}</div>`;
          }
        }
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ pencatatan servis",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data riwayat servis yang cocok",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Berikutnya",
          previous: "Sebelumnya"
        }
      },
      pageLength: 10,
      responsive: true,
      order: [[1, 'desc']]
    });

    // Helper: Refresh Dynamic Form Options & Stat Cards via AJAX
    function refreshServiceOptionsAndStats(callback) {
      $.ajax({
        url: "{{ route('service-logs.options') }}",
        type: 'GET',
        dataType: 'json',
        success: function(res) {
          if (res.stats) {
            $('#statTotalCost').text(formatRupiah(res.stats.total_cost));
            $('#statTotalServices').text(formatNumber(res.stats.total_services) + ' Kali Servis');
            $('#statInMaintenanceCount').text(formatNumber(res.stats.in_maintenance_count) + ' Unit');
          }
          if (res.available_vehicles) {
            const currentVal = $('#selectVehicle').val();
            let opts = '<option value="" selected disabled>-- Pilih Kendaraan Tersedia --</option>';
            res.available_vehicles.forEach(v => {
              const pool = v.location ? v.location.name : '-';
              opts += `<option value="${v.id}">${escapeHtml(v.name)} (Plat: ${escapeHtml(v.license_plate)} | Pool: ${escapeHtml(pool)})</option>`;
            });
            $('#selectVehicle').html(opts);
            if (currentVal && res.available_vehicles.some(v => v.id == currentVal)) {
              $('#selectVehicle').val(currentVal);
            }
          }
          if (callback) callback(res);
        }
      });
    }

    // Tombol Tambah Servis
    $('#btnCreateServiceLog').on('click', function() {
      $('#modalTitle').text('Catat Servis Kendaraan Baru');
      $('#formServiceLog')[0].reset();
      $('#formMethod').val('POST');
      $('#formServiceLog').attr('action', "{{ route('service-logs.store') }}");
      $('#formServiceLog').removeClass('was-validated');
      $('.invalid-feedback').text('');
      $('#inputServiceDate').val(new Date().toISOString().split('T')[0]);
      refreshServiceOptionsAndStats(function() {
        $('#selectVehicle').val('').trigger('change');
      });
      serviceLogModal.show();
    });

    // Submit Form (AJAX Store / Update)
    $('#formServiceLog').on('submit', function(e) {
      e.preventDefault();

      const form = $(this);
      const url = form.attr('action');
      const formData = form.serialize();

      $('#btnSaveServiceLog').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...');
      $('.invalid-feedback').hide();

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          $('#btnSaveServiceLog').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Catatan Servis');
          if (response.success) {
            serviceLogModal.hide();
            tableServiceLogs.ajax.reload(null, false);
            refreshServiceOptionsAndStats();

            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: response.message,
              timer: 2500,
              showConfirmButton: false
            });
          }
        },
        error: function(xhr) {
          $('#btnSaveServiceLog').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Catatan Servis');
          if (xhr.status === 422) {
            const errors = xhr.responseJSON.errors;
            form.addClass('was-validated');
            $.each(errors, function(field, messages) {
              $('#err-' + field).text(messages[0]).show();
            });
          } else {
            const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.';
            Swal.fire('Error', msg, 'error');
          }
        }
      });
    });

    // Tombol Edit
    $(document).on('click', '.btn-edit', function() {
      const id = $(this).data('id');
      const url = "{{ url('service-logs') }}/" + id;

      $('#modalTitle').text('Edit Riwayat Servis');
      $('#formMethod').val('PUT');
      $('#formServiceLog').attr('action', url);
      $('#formServiceLog').removeClass('was-validated');
      $('.invalid-feedback').text('');

      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          $.ajax({
            url: "{{ route('service-logs.options') }}",
            type: 'GET',
            dataType: 'json',
            success: function(res) {
              const vehicles = res.all_vehicles || res.available_vehicles || [];
              let opts = '<option value="" disabled>-- Pilih Kendaraan --</option>';
              vehicles.forEach(v => {
                const pool = v.location ? v.location.name : '-';
                opts += `<option value="${v.id}">${escapeHtml(v.name)} (Plat: ${escapeHtml(v.license_plate)} | Pool: ${escapeHtml(pool)})</option>`;
              });
              $('#selectVehicle').html(opts);
              $('#selectVehicle').val(data.vehicle_id).trigger('change');
              $('#inputServiceType').val(data.service_type);
              $('#inputServiceDate').val(data.service_date ? data.service_date.split('T')[0] : '');
              $('#inputCost').val(data.cost);
              $('#inputDescription').val(data.description || '');

              serviceLogModal.show();
            }
          });
        },
        error: function() {
          Swal.fire('Error', 'Gagal memuat data servis.', 'error');
        }
      });
    });

    // Tombol Selesaikan Servis
    $(document).on('click', '.btn-complete-service', function() {
      const serviceId = $(this).data('id');
      const vehicleName = $(this).data('vehicle');
      const completeUrl = "{{ url('service-logs') }}/" + serviceId + "/complete";

      Swal.fire({
        title: 'Selesaikan Servis Armada?',
        text: 'Apakah Anda yakin transaksi servis kendaraan "' + vehicleName + '" telah selesai? Status servis akan diubah menjadi SELESAI & armada dikembalikan ke TERSEDIA.',
        icon: 'question',
        showCancelButton: true,
        confirmColor: '#198754',
        cancelColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-check2-circle me-1"></i> Ya, Selesai Servis!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: completeUrl,
            type: 'POST',
            data: {
              _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                tableServiceLogs.ajax.reload(null, false);
                refreshServiceOptionsAndStats();
                Swal.fire({
                  icon: 'success',
                  title: 'Servis Selesai!',
                  text: response.message,
                  timer: 2500,
                  showConfirmButton: false
                });
              }
            },
            error: function(xhr) {
              const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyelesaikan servis kendaraan.';
              Swal.fire('Error', msg, 'error');
            }
          });
        }
      });
    });

    // Tombol Detail
    $(document).on('click', '.btn-detail', function() {
      const id = $(this).data('id');
      const url = "{{ url('service-logs') }}/" + id;

      $('#detailServiceLogContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
      detailModal.show();

      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          const vehicleName = data.vehicle ? `${escapeHtml(data.vehicle.name)} (${escapeHtml(data.vehicle.license_plate)})` : 'Kendaraan Terhapus';
          const vehiclePool = data.vehicle && data.vehicle.location ? escapeHtml(data.vehicle.location.name) : '-';
          const serviceStatus = data.status === 'completed'
            ? '<span class="badge text-bg-success fs-6">SELESAI (COMPLETED)</span>'
            : (data.status === 'cancelled' ? '<span class="badge text-bg-secondary fs-6">DIBATALKAN</span>' : '<span class="badge text-bg-warning text-dark fs-6">DALAM SERVIS (IN PROGRESS)</span>');

          const html = `
            <div class="row g-3">
              <div class="col-12">
                <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-1">Informasi Pemeliharaan & Armada</small>
              </div>
              <div class="col-md-6 mt-1">
                <div class="p-3 rounded-3 h-100 border">
                  <div class="mb-2"><strong>Status Servis:</strong> ${serviceStatus}</div>
                  <div class="mb-2"><strong>Jenis Servis:</strong> <span class="badge text-bg-primary fs-6">${escapeHtml(data.service_type)}</span></div>
                  <div class="mb-2"><strong>Tanggal Servis:</strong> <span class="text-dark fw-bold">${formatDate(data.service_date)}</span></div>
                  <div class="mb-2"><strong>Total Biaya:</strong> <span class="text-success fw-bold fs-6">${formatRupiah(data.cost)}</span></div>
                </div>
              </div>
              <div class="col-md-6 mt-1">
                <div class="p-3 rounded-3 h-100 border">
                  <div class="mb-2"><strong>Kendaraan:</strong> ${vehicleName}</div>
                  <div class="mb-2"><strong>Lokasi Pool:</strong> ${vehiclePool}</div>
                  <div class="mb-2"><strong>Status Armada:</strong> <span class="badge text-bg-secondary">${data.vehicle ? data.vehicle.status.toUpperCase() : '-'}</span></div>
                </div>
              </div>
              <div class="col-12 mt-3">
                <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-2">Rincian Perbaikan & Catatan Suku Cadang</small>
                <div class="rounded-3 border p-3 text-dark bg-light">${escapeHtml(data.description) || 'Tidak ada rincian catatan.'}</div>
              </div>
            </div>
          `;

          $('#detailServiceLogContent').html(html);
        },
        error: function() {
          $('#detailServiceLogContent').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Gagal memuat rincian data servis.</div>');
        }
      });
    });

    // Tombol Batalkan Servis
    $(document).on('click', '.btn-cancel-service', function() {
      const id = $(this).data('id');
      const vehicleName = $(this).data('vehicle');
      const cancelUrl = "{{ url('service-logs') }}/" + id + "/cancel";

      Swal.fire({
        title: 'Konfirmasi Pembatalan Servis',
        text: 'Apakah Anda yakin ingin membatalkan pencatatan servis kendaraan "' + vehicleName + '"? Status armada akan dikembalikan ke TERSEDIA.',
        icon: 'warning',
        showCancelButton: true,
        confirmColor: '#dc3545',
        cancelColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-x-circle me-1"></i> Ya, Batalkan!',
        cancelButtonText: 'Kembali'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: cancelUrl,
            type: 'POST',
            data: {
              _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                tableServiceLogs.ajax.reload(null, false);
                refreshServiceOptionsAndStats();
                Swal.fire({
                  icon: 'success',
                  title: 'Dibatalkan!',
                  text: response.message,
                  timer: 2500,
                  showConfirmButton: false
                });
              }
            },
            error: function(xhr) {
              const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal membatalkan pencatatan servis.';
              Swal.fire('Error', msg, 'error');
            }
          });
        }
      });
    });
  });
</script>
@endsection
