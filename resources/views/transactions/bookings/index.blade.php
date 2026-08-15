@extends('layouts.app')

@section('title', 'Pemesanan Kendaraan')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div class="page-heading-copy mb-0">
    <span class="page-icon bg-warning text-dark"><i class="bi bi-journal-check" aria-hidden="true"></i></span>
    <div>
      <p class="eyebrow mb-1 text-warning fw-bold"><i class="bi bi-shield-check"></i> Operasional Fleet</p>
      <h1 class="h3 mb-1">Pemesanan Kendaraan Tambang</h1>
      <p class="text-muted mb-0">Input pemesanan kendaraan, tentukan driver, dan tetapkan pihak penyetuju berjenjang (minimal 2 level).</p>
    </div>
  </div>

  @if(Auth::user()->role?->name === 'admin')
  <button type="button" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm" id="btnCreateBooking">
    <i class="bi bi-plus-lg me-1"></i> Input Pemesanan Baru
  </button>
  @endif
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle w-100" id="tableBookings">
      <thead class="table-light">
        <tr>
          <th class="text-center">No.</th>
          <th>Kode & Tanggal</th>
          <th>Kendaraan & Driver</th>
          <th>Rute (Asal &rarr; Tujuan)</th>
          <th>Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Data loaded dynamically via DataTables Server-Side -->
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL FORM INPUT PEMESANAN KENDARAAN -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header text-dark">
        <h5 class="modal-title fw-bold" id="bookingModalLabel">
          <i class="bi bi-journal-plus me-2"></i><span id="modalTitle">Input Pemesanan Kendaraan</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formBooking" action="{{ route('bookings.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        <div class="modal-body p-4">
          <div class="row g-3">

            <!-- Pilih Kendaraan (Hanya yang Status = Available & Tidak Sedang Dipesan) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectVehicle">Pilih Kendaraan Operasional <span class="text-danger">*</span></label>
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

            <!-- Pilih Driver (Hanya yang Status = Available & Tidak Bertugas) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectDriver">Pilih Driver / Pengemudi <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectDriver" name="driver_id" required>
                <option value="" selected disabled>-- Pilih Driver Tersedia --</option>
                @foreach($availableDrivers as $d)
                  <option value="{{ $d->id }}">
                    {{ $d->name }} ({{ $d->license_number ?? 'Tanpa No SIM' }})
                  </option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-driver_id">Pilih driver yang tersedia.</div>
            </div>

            <!-- Lokasi Penjemputan / Asal -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectStartLocation">Lokasi Asal / Penjemputan <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectStartLocation" name="start_location_id" required>
                <option value="" selected disabled>-- Pilih Lokasi Asal --</option>
                @foreach($locations as $loc)
                  <option value="{{ $loc->id }}">{{ $loc->name }} ({{ ucfirst($loc->type) }})</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-start_location_id">Pilih lokasi penjemputan.</div>
            </div>

            <!-- Lokasi Tujuan -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectDestinationLocation">Lokasi Tujuan <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectDestinationLocation" name="destination_location_id" required>
                <option value="" selected disabled>-- Pilih Lokasi Tujuan --</option>
                @foreach($locations as $loc)
                  <option value="{{ $loc->id }}">{{ $loc->name }} ({{ ucfirst($loc->type) }})</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-destination_location_id">Pilih lokasi tujuan.</div>
            </div>

            <!-- Waktu Mulai -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputStartDate">Tanggal & Waktu Mulai <span class="text-danger">*</span></label>
              <input type="datetime-local" class="form-control" id="inputStartDate" name="start_date" required>
              <div class="invalid-feedback" id="err-start_date">Tanggal mulai wajib diisi.</div>
            </div>

            <!-- Waktu Selesai -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputEndDate">Tanggal & Waktu Selesai <span class="text-danger">*</span></label>
              <input type="datetime-local" class="form-control" id="inputEndDate" name="end_date" required>
              <div class="invalid-feedback" id="err-end_date">Tanggal selesai wajib diisi.</div>
            </div>

            <!-- Penyetuju Level 1 (Atasan Langsung / SPV) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectApprover1">Penyetuju Level 1 (Supervisor/SPV) <span class="text-danger">*</span></label>
              <select class="form-select" id="selectApprover1" name="approver_1_id" required>
                <option value="" selected disabled>-- Pilih Penyetuju Level 1 --</option>
                @foreach($approversL1 as $appr)
                  <option value="{{ $appr->id }}">{{ $appr->name }} ({{ $appr->role?->label ?? 'Supervisor' }})</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-approver_1_id">Pilih penyetuju level 1.</div>
            </div>

            <!-- Penyetuju Level 2 (Atasan L2 / Manager) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectApprover2">Penyetuju Level 2 (Manager Ops) <span class="text-danger">*</span></label>
              <select class="form-select" id="selectApprover2" name="approver_2_id" required>
                <option value="" selected disabled>-- Pilih Penyetuju Level 2 --</option>
                @foreach($approversL2 as $appr)
                  <option value="{{ $appr->id }}">{{ $appr->name }} ({{ $appr->role?->label ?? 'Manager' }})</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-approver_2_id">Pilih penyetuju level 2.</div>
            </div>

            <!-- Keperluan -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputPurpose">Keperluan Pemakaian Kendaraan <span class="text-danger">*</span></label>
              <textarea class="form-control" id="inputPurpose" name="purpose" rows="3" placeholder="Contoh: Inspeksi Lapangan Tambang Blok A Morowali..." required></textarea>
              <div class="invalid-feedback" id="err-purpose">Keperluan pemakaian wajib diisi.</div>
            </div>

          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary fw-bold" id="btnSaveBooking">
            <i class="bi bi-send-check me-1"></i> Kirim Pemesanan Kendaraan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL DETAIL PEMESANAN KENDARAAN -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="detailModalLabel">
          <i class="bi bi-info-circle me-2"></i>Detail Pemesanan Kendaraan: <span id="detailBookingCode" class="font-monospace"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="detailContent">
        <!-- Content loaded dynamically via AJAX -->
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  #tableBookings th:first-child,
  #tableBookings td:first-child {
    width: 40px !important;
    max-width: 40px !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
    text-align: center;
  }
  #tableBookings th:first-child::before,
  #tableBookings th:first-child::after {
    display: none !important;
  }
</style>
@endpush

@section('scripts')
<script>
  $(document).ready(function() {
    const bookingModalEl = document.getElementById('bookingModal');
    const bookingModal = new bootstrap.Modal(bookingModalEl);
    const detailModalEl = document.getElementById('detailModal');
    const detailModal = new bootstrap.Modal(detailModalEl);

    const isAdmin = @json(Auth::user()->role?->name === 'admin');

    // 1. Inisialisasi DataTables Server-Side Processing
    const tableBookings = $('#tableBookings').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('bookings.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
        {
          data: 'booking_code',
          name: 'booking_code',
          render: function(data, type, row) {
            const startDate = row.start_date ? formatDateTime(row.start_date) : '-';
            const endDate = row.end_date ? formatDateTime(row.end_date) : '-';
            return `
              <div class="lh-sm">
                <strong class="font-monospace d-block">${escapeHtml(data)}</strong>
                <small class="text-muted"><i class="bi bi-calendar-range me-1"></i>${startDate} - ${endDate}</small>
              </div>
            `;
          }
        },
        {
          data: 'vehicle',
          name: 'vehicle.name',
          render: function(data, type, row) {
            const vehicleName = row.vehicle ? `${escapeHtml(row.vehicle.name)} (${escapeHtml(row.vehicle.license_plate)})` : '-';
            const driverName = row.driver ? escapeHtml(row.driver.name) : '-';
            return `
              <div class="lh-sm">
                <strong class="d-block text-dark fw-semibold"><i class="bi bi-truck me-1"></i>${vehicleName}</strong>
                <small class="text-secondary"><i class="bi bi-person me-1"></i>Driver: ${driverName}</small>
              </div>
            `;
          }
        },
        {
          data: 'start_location',
          name: 'startLocation.name',
          render: function(data, type, row) {
            const startLoc = row.start_location ? escapeHtml(row.start_location.name) : escapeHtml(row.start_address || '-');
            const destLoc = row.destination_location ? escapeHtml(row.destination_location.name) : escapeHtml(row.destination_address || '-');
            return `
              <small class="d-block text-dark fw-semibold"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${startLoc}</small>
              <small class="d-block text-secondary"><i class="bi bi-arrow-down me-1"></i>${destLoc}</small>
            `;
          }
        },
        {
          data: 'status',
          name: 'status',
          render: function(data, type, row) {
            if (data === 'cancelled' || row.deleted_at) {
              return '<span class="badge p-1 text-bg-secondary">Dibatalkan</span>';
            } else if (data === 'approved') {
              return '<span class="badge p-1 text-bg-success">Disetujui</span>';
            } else if (data === 'rejected') {
              return '<span class="badge p-1 text-bg-danger">Ditolak</span>';
            } else if (data === 'completed') {
              return '<span class="badge p-1 text-bg-info">Selesai</span>';
            } else if (data === 'in_progress') {
              return '<span class="badge p-1 text-bg-primary">Berjalan</span>';
            }
            return '<span class="badge p-1 text-bg-warning text-dark">Menunggu Approval</span>';
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
              <button type="button" class="btn btn-sm btn-outline-info btn-detail" data-id="${data}" title="Detail Pemesanan">
                <i class="bi bi-eye me-1"></i> Detail
              </button>
            `;

            if (!isAdmin || row.status === 'cancelled' || row.deleted_at) {
              return btnDetail;
            }

            let btnComplete = '';
            if (row.status === 'approved' || row.status === 'in_progress') {
              btnComplete = `
                <button type="button" class="btn btn-sm btn-success btn-complete ms-1" data-id="${data}" data-code="${escapeHtml(row.booking_code)}" title="Selesaikan Pemesanan">
                  <i class="bi bi-check2-circle me-1"></i> Selesai
                </button>
              `;
            }

            let btnCancel = '';
            if (row.status !== 'completed') {
              btnCancel = `
                <button type="button" class="btn btn-sm btn-outline-warning btn-cancel ms-1" data-id="${data}" data-code="${escapeHtml(row.booking_code)}" title="Batalkan Pemesanan">
                  Batalkan
                </button>
              `;
            }

            return `<div class="btn-group btn-group-sm">${btnDetail}${btnComplete}${btnCancel}</div>`;
          }
        }
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ pemesanan",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data pemesanan yang cocok",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Berikutnya",
          previous: "Sebelumnya"
        }
      },
      pageLength: 10,
      responsive: true,
      order: [[0, 'desc']]
    });

    // Helper: Refresh Dynamic Form Options via AJAX
    function refreshBookingOptions(callback) {
      $.ajax({
        url: "{{ route('bookings.options') }}",
        type: 'GET',
        dataType: 'json',
        success: function(res) {
          if (res.available_vehicles) {
            let vOpts = '<option value="" selected disabled>-- Pilih Kendaraan Tersedia --</option>';
            res.available_vehicles.forEach(v => {
              const pool = v.location ? v.location.name : '-';
              vOpts += `<option value="${v.id}">${escapeHtml(v.name)} (Plat: ${escapeHtml(v.license_plate)} | Pool: ${escapeHtml(pool)})</option>`;
            });
            $('#selectVehicle').html(vOpts);
          }
          if (res.available_drivers) {
            let dOpts = '<option value="" selected disabled>-- Pilih Driver Tersedia --</option>';
            res.available_drivers.forEach(d => {
              const sim = d.license_number ? d.license_number : 'Tanpa No SIM';
              dOpts += `<option value="${d.id}">${escapeHtml(d.name)} (${escapeHtml(sim)})</option>`;
            });
            $('#selectDriver').html(dOpts);
          }
          if (res.locations) {
            let locOpts = '<option value="" selected disabled>-- Pilih Lokasi --</option>';
            res.locations.forEach(loc => {
              locOpts += `<option value="${loc.id}">${escapeHtml(loc.name)} (${escapeHtml(loc.type)})</option>`;
            });
            const sVal = $('#selectStartLocation').val();
            const dVal = $('#selectDestinationLocation').val();
            $('#selectStartLocation').html(locOpts);
            $('#selectDestinationLocation').html(locOpts);
            if (sVal) $('#selectStartLocation').val(sVal);
            if (dVal) $('#selectDestinationLocation').val(dVal);
          }
          if (callback) callback(res);
        }
      });
    }

    // Tombol Tambah Pemesanan
    $('#btnCreateBooking').on('click', function() {
      $('#formBooking')[0].reset();
      $('#formBooking').removeClass('was-validated');
      $('.invalid-feedback').text('');
      refreshBookingOptions(function() {
        $('.select2').val('').trigger('change');
      });
      bookingModal.show();
    });

    // Submit Form (AJAX)
    $('#formBooking').on('submit', function(e) {
      e.preventDefault();

      const form = $(this);
      const url = form.attr('action');
      const formData = form.serialize();

      $('#btnSaveBooking').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mengirim...');
      $('.invalid-feedback').hide();

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          $('#btnSaveBooking').prop('disabled', false).html('<i class="bi bi-send-check me-1"></i> Kirim Pemesanan Kendaraan');
          if (response.success) {
            bookingModal.hide();
            tableBookings.ajax.reload(null, false);
            refreshBookingOptions();

            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: response.message,
              timer: 2500,
              showConfirmButton: false
            });
          } else {
            Swal.fire('Perhatian', response.message, 'warning');
          }
        },
        error: function(xhr) {
          $('#btnSaveBooking').prop('disabled', false).html('<i class="bi bi-send-check me-1"></i> Kirim Pemesanan Kendaraan');
          if (xhr.status === 422) {
            const errors = xhr.responseJSON.errors;
            form.addClass('was-validated');
            $.each(errors, function(field, messages) {
              $('#err-' + field).text(messages[0]).show();
            });

            if (xhr.responseJSON.message) {
              Swal.fire('Peringatan Validasi', xhr.responseJSON.message, 'warning');
            }
          } else {
            Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan pemesanan.', 'error');
          }
        }
      });
    });

    // Helper to format JS timestamp
    function formatDateTimeStr(isoStr) {
      if (!isoStr) return '';
      const d = new Date(isoStr);
      if (isNaN(d.getTime())) return isoStr;
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      const hours = String(d.getHours()).padStart(2, '0');
      const mins = String(d.getMinutes()).padStart(2, '0');
      return `${day}/${month}/${year} ${hours}:${mins} WIB`;
    }

    function renderBookingDetailModal(data) {
      let approvalsHtml = '<div class="list-group list-group-flush border rounded-3">';
      if (data.approvals && data.approvals.length > 0) {
        data.approvals.forEach(function(app) {
          const approverName = app.approver ? app.approver.name : 'Approver';
          const roleName = app.approver && app.approver.role ? (app.approver.role.label || app.approver.role.name) : '-';
          
          let badge = '<span class="badge text-bg-warning text-dark">Menunggu</span>';
          if (app.status === 'approved') {
            badge = '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Disetujui</span>';
          } else if (app.status === 'rejected') {
            badge = '<span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Ditolak</span>';
          }

          const timeBadge = app.responded_at ?
            `<span class="badge text-bg-light border text-muted ms-2"><i class="bi bi-clock me-1"></i>${formatDateTimeStr(app.responded_at)}</span>` :
            '<span class="badge text-bg-light border text-muted ms-2"><i class="bi bi-hourglass me-1"></i>Belum Diproses</span>';

          const noteHtml = app.note ?
            `<div class="mt-2 small bg-white p-2 border rounded text-dark"><strong>Catatan:</strong> ${app.note}</div>` : '';

          approvalsHtml += `
            <div class="list-group-item py-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                  <strong class="d-block text-dark">Level ${app.approval_level}: ${approverName}</strong>
                  <small class="text-muted">Role: ${roleName}</small>
                </div>
                <div>
                  ${badge}
                  ${timeBadge}
                </div>
              </div>
              ${noteHtml}
            </div>
          `;
        });
      } else {
        approvalsHtml += '<div class="list-group-item py-2 text-muted small">Belum ada data persetujuan</div>';
      }
      approvalsHtml += '</div>';

      const vehicleText = data.vehicle ? data.vehicle.name + ' (' + data.vehicle.license_plate + ')' : '-';
      const driverText = data.driver ? data.driver.name + (data.driver.phone ? ' (' + data.driver.phone + ')' : '') : '-';
      const startLocText = data.start_location ? data.start_location.name : (data.start_address || '-');
      const destLocText = data.destination_location ? data.destination_location.name : (data.destination_address || '-');
      const startDateText = data.start_date ? formatDateTimeStr(data.start_date) : '-';
      const endDateText = data.end_date ? formatDateTimeStr(data.end_date) : '-';

      return `
        <div class="row g-3">
          <div class="col-12">
            <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-1">Informasi Pemesanan & Armada</small>
          </div>
          <div class="col-md-6 mt-1">
            <div class="p-3 rounded-3 h-100 border">
              <div class="mb-2"><strong>Kode:</strong> <span class="text-primary font-monospace fs-6">${data.booking_code}</span></div>
              <div class="mb-2"><strong>Pemohon:</strong> ${data.user ? data.user.name : '-'}</div>
              <div class="mb-2"><strong>Jadwal:</strong> ${startDateText} s/d ${endDateText}</div>
            </div>
          </div>
          <div class="col-md-6 mt-1">
            <div class="p-3 rounded-3 h-100 border">
              <div class="mb-2"><strong>Kendaraan:</strong> ${vehicleText}</div>
              <div class="mb-2"><strong>Driver:</strong> ${driverText}</div>
              <div class="mb-2"><strong>Rute Penjemputan:</strong> ${startLocText}</div>
              <div class="mb-2"><strong>Tujuan:</strong> ${destLocText}</div>
            </div>
          </div>
          <div class="col-12 mt-3">
            <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-2">Keperluan</small>
            <div class="rounded-3 border p-3 text-dark">${data.purpose || '-'}</div>
          </div>
          <div class="col-12 mt-3">
            <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-2">Status Persetujuan Berjenjang</small>
            ${approvalsHtml}
          </div>
        </div>
      `;
    }

    // Tombol Detail Pemesanan
    $(document).on('click', '.btn-detail', function() {
      const bookingId = $(this).data('id');
      const url = "{{ url('bookings') }}/" + bookingId;

      $('#detailBookingCode').text('');
      $('#detailContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
      detailModal.show();

      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          $('#detailBookingCode').text(data.booking_code);
          $('#detailContent').html(renderBookingDetailModal(data));
        },
        error: function() {
          $('#detailContent').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Gagal memuat detail pemesanan.</div>');
        }
      });
    });

    // Tombol Selesaikan Pemesanan
    $(document).on('click', '.btn-complete', function() {
      const bookingId = $(this).data('id');
      const bookingCode = $(this).data('code');
      const completeUrl = "{{ url('bookings') }}/" + bookingId + "/complete";

      Swal.fire({
        title: 'Konfirmasi Selesai Pesanan',
        text: 'Apakah Anda yakin ingin menyelesaikan pemesanan kendaraan "' + bookingCode + '"? Status armada & driver akan dikembalikan ke TERSEDIA.',
        icon: 'question',
        showCancelButton: true,
        confirmColor: '#198754',
        cancelColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-check2-circle me-1"></i> Ya, Selesaikan!',
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
                tableBookings.ajax.reload(null, false);
                refreshBookingOptions();
                Swal.fire({
                  icon: 'success',
                  title: 'Berhasil Selesai!',
                  text: response.message,
                  timer: 2500,
                  showConfirmButton: false
                });
              }
            },
            error: function(xhr) {
              const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyelesaikan pemesanan.';
              Swal.fire('Error', msg, 'error');
            }
          });
        }
      });
    });

    // Tombol Batalkan Pemesanan
    $(document).on('click', '.btn-cancel', function() {
      const bookingId = $(this).data('id');
      const bookingCode = $(this).data('code');
      const cancelUrl = "{{ url('bookings') }}/" + bookingId;

      Swal.fire({
        title: 'Konfirmasi Pembatalan',
        text: 'Apakah Anda yakin ingin membatalkan pemesanan kendaraan "' + bookingCode + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmColor: '#ffc107',
        cancelColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-x-circle me-1"></i> Ya, Batalkan!',
        cancelButtonText: 'Kembali'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: cancelUrl,
            type: 'POST',
            data: {
              _method: 'DELETE',
              _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                tableBookings.ajax.reload(null, false);
                refreshBookingOptions();
                Swal.fire({
                  icon: 'success',
                  title: 'Dibatalkan!',
                  text: response.message,
                  timer: 2000,
                  showConfirmButton: false
                });
              }
            },
            error: function() {
              Swal.fire('Error', 'Gagal membatalkan pemesanan kendaraan.', 'error');
            }
          });
        }
      });
    });
  });
</script>
@endsection
