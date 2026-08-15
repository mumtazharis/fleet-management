@extends('layouts.app')

@section('title', 'Pemesanan Kendaraan')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div>
    <h1 class="h3 mb-1">Pemesanan Kendaraan Tambang</h1>
    <p class="text-muted mb-0">Input pemesanan kendaraan, tentukan driver, dan tetapkan pihak penyetuju.</p>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-2">
    <button type="button" class="btn btn-success fw-semibold px-3 py-2 shadow-sm" id="btnExportBookings" title="Export Semua Detail Pemesanan ke Excel">
      <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
    </button>

    @if(Auth::user()->role?->name === 'admin')
    <button type="button" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm" id="btnCreateBooking">
      <i class="bi bi-plus-lg me-1"></i> Input Pemesanan Baru
    </button>
    @endif
  </div>
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
                <option value="other">[+] Lokasi Lainnya (Input Manual)</option>
                @foreach($locations as $loc)
                  <option value="{{ $loc->id }}">{{ $loc->name }} ({{ ucfirst($loc->type) }})</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-start_location_id">Pilih lokasi penjemputan.</div>

              <!-- Input Kustom Lokasi Asal Lainnya (Kondisional) -->
              <div class="mt-2 d-none" id="containerStartOther">
                <label class="form-label small fw-semibold text-primary mb-1" for="inputStartOtherLocation">
                  <i class="bi bi-geo-alt me-1"></i>Nama / Alamat Penjemputan Spesifik <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control form-control-sm border-primary" id="inputStartOtherLocation" name="start_address" placeholder="Contoh: Bandara Sultan Hasanuddin Makassar / Hotel Claro">
                <div class="invalid-feedback" id="err-start_address">Nama / alamat lokasi penjemputan lainnya wajib diisi.</div>
              </div>
            </div>

            <!-- Lokasi Tujuan -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectDestinationLocation">Lokasi Tujuan <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectDestinationLocation" name="destination_location_id" required>
                <option value="" selected disabled>-- Pilih Lokasi Tujuan --</option>
                <option value="other">[+] Lokasi Lainnya (Input Manual)</option>
                @foreach($locations as $loc)
                  <option value="{{ $loc->id }}">{{ $loc->name }} ({{ ucfirst($loc->type) }})</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-destination_location_id">Pilih lokasi tujuan.</div>

              <!-- Input Kustom Lokasi Tujuan Lainnya (Kondisional) -->
              <div class="mt-2 d-none" id="containerDestOther">
                <label class="form-label small fw-semibold text-primary mb-1" for="inputDestOtherLocation">
                  <i class="bi bi-geo-alt-fill me-1"></i>Nama / Alamat Tujuan Spesifik <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control form-control-sm border-primary" id="inputDestOtherLocation" name="destination_address" placeholder="Contoh: Pelabuhan Pomalaa / RS Rujukan / Kantor ESDM">
                <div class="invalid-feedback" id="err-destination_address">Nama / alamat lokasi tujuan lainnya wajib diisi.</div>
              </div>
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
            if (row.status !== 'completed' && row.status !== 'rejected') {
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

    // Helper: Refresh Dynamic Form Options via AJAX with optional date range conflict filter
    function refreshBookingOptions(callback, startDate, endDate) {
      const params = {};
      const sDate = startDate || $('#inputStartDate').val();
      const eDate = endDate || $('#inputEndDate').val();
      if (sDate && eDate) {
        params.start_date = sDate;
        params.end_date = eDate;
      }

      $.ajax({
        url: "{{ route('bookings.options') }}",
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(res) {
          if (res.available_vehicles) {
            const currentVVal = $('#selectVehicle').val();
            let vOpts = '<option value="" selected disabled>-- Pilih Kendaraan Tersedia --</option>';
            res.available_vehicles.forEach(v => {
              const pool = v.location ? v.location.name : '-';
              vOpts += `<option value="${v.id}">${escapeHtml(v.name)} (Plat: ${escapeHtml(v.license_plate)} | Pool: ${escapeHtml(pool)})</option>`;
            });
            $('#selectVehicle').html(vOpts);
            if (currentVVal && res.available_vehicles.some(v => v.id == currentVVal)) {
              $('#selectVehicle').val(currentVVal);
            }
          }
          if (res.available_drivers) {
            const currentDVal = $('#selectDriver').val();
            let dOpts = '<option value="" selected disabled>-- Pilih Driver Tersedia --</option>';
            res.available_drivers.forEach(d => {
              const sim = d.license_number ? d.license_number : 'Tanpa No SIM';
              dOpts += `<option value="${d.id}">${escapeHtml(d.name)} (${escapeHtml(sim)})</option>`;
            });
            $('#selectDriver').html(dOpts);
            if (currentDVal && res.available_drivers.some(d => d.id == currentDVal)) {
              $('#selectDriver').val(currentDVal);
            }
          }
          if (res.locations) {
            let locOpts = '<option value="" selected disabled>-- Pilih Lokasi --</option>';
            locOpts += '<option value="other">[+] Lokasi Lainnya (Input Manual)</option>';
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

    // Auto-filter armada & driver jika tanggal & waktu trip diubah
    $('#inputStartDate, #inputEndDate').on('change', function() {
      const s = $('#inputStartDate').val();
      const e = $('#inputEndDate').val();
      if (s && e) {
        refreshBookingOptions();
      }
    });

    // Toggle Input Lokasi Kustom jika memilih opsi "other"
    $('#selectStartLocation').on('change', function() {
      if ($(this).val() === 'other') {
        $('#containerStartOther').removeClass('d-none');
        $('#inputStartOtherLocation').prop('required', true).focus();
      } else {
        $('#containerStartOther').addClass('d-none');
        $('#inputStartOtherLocation').prop('required', false).val('');
      }
    });

    $('#selectDestinationLocation').on('change', function() {
      if ($(this).val() === 'other') {
        $('#containerDestOther').removeClass('d-none');
        $('#inputDestOtherLocation').prop('required', true).focus();
      } else {
        $('#containerDestOther').addClass('d-none');
        $('#inputDestOtherLocation').prop('required', false).val('');
      }
    });

    // Tombol Export Excel (AJAX dengan Loading)
    $('#btnExportBookings').on('click', function(e) {
      e.preventDefault();
      const btn = $(this);
      const originalHtml = btn.html();

      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Mengekspor...');

      fetch("{{ route('bookings.export') }}", {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Gagal mengekspor file Excel.');
        }
        let filename = 'data-pemesanan-kendaraan.xlsx';
        const disposition = response.headers.get('Content-Disposition');
        if (disposition && disposition.indexOf('filename=') !== -1) {
          const matches = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
          if (matches != null && matches[1]) {
            filename = matches[1].replace(/['"]/g, '');
          }
        }
        return response.blob().then(blob => ({ blob, filename }));
      })
      .then(({ blob, filename }) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
      })
      .catch(error => {
        Swal.fire('Error', error.message || 'Terjadi kesalahan saat mengekspor Excel.', 'error');
      })
      .finally(() => {
        btn.prop('disabled', false).html(originalHtml);
      });
    });

    // Tombol Tambah Pemesanan
    $('#btnCreateBooking').on('click', function() {
      $('#formBooking')[0].reset();
      $('#formBooking').removeClass('was-validated');
      $('.invalid-feedback').text('').hide();
      $('#containerStartOther').addClass('d-none');
      $('#inputStartOtherLocation').prop('required', false).val('');
      $('#containerDestOther').addClass('d-none');
      $('#inputDestOtherLocation').prop('required', false).val('');
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
      const isBookingCancelled = (data.status === 'cancelled' || data.deleted_at != null);

      let bookingStatusBadge = '<span class="badge text-bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu Approval</span>';
      if (isBookingCancelled) {
        bookingStatusBadge = '<span class="badge text-bg-secondary"><i class="bi bi-slash-circle me-1"></i>Dibatalkan</span>';
      } else if (data.status === 'approved') {
        bookingStatusBadge = '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Disetujui</span>';
      } else if (data.status === 'rejected') {
        bookingStatusBadge = '<span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Ditolak</span>';
      } else if (data.status === 'completed') {
        bookingStatusBadge = '<span class="badge text-bg-info"><i class="bi bi-check2-all me-1"></i>Selesai</span>';
      } else if (data.status === 'in_progress') {
        bookingStatusBadge = '<span class="badge text-bg-primary"><i class="bi bi-play-circle me-1"></i>Sedang Berjalan</span>';
      }

      let approvalsHtml = '<div class="list-group list-group-flush border rounded-3">';
      if (data.approvals && data.approvals.length > 0) {
        data.approvals.forEach(function(app) {
          const approverName = app.approver ? escapeHtml(app.approver.name) : 'Approver';
          const roleName = app.approver && app.approver.role ? escapeHtml(app.approver.role.label || app.approver.role.name) : '-';
          
          let badge = '<span class="badge text-bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>';
          if (app.status === 'cancelled' || isBookingCancelled) {
            badge = '<span class="badge text-bg-secondary"><i class="bi bi-slash-circle me-1"></i>Dibatalkan</span>';
          } else if (app.status === 'approved') {
            badge = '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Disetujui</span>';
          } else if (app.status === 'rejected') {
            badge = '<span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Ditolak</span>';
          }

          let timeBadge = '';
          if (app.responded_at) {
            timeBadge = `<span class="badge text-bg-light border text-muted ms-2"><i class="bi bi-clock me-1"></i>${formatDateTimeStr(app.responded_at)}</span>`;
          } else if (app.status === 'cancelled' || isBookingCancelled) {
            timeBadge = '<span class="badge text-bg-light border text-muted ms-2"><i class="bi bi-dash-circle me-1"></i>Dibatalkan</span>';
          } else {
            timeBadge = '<span class="badge text-bg-light border text-muted ms-2"><i class="bi bi-hourglass me-1"></i>Belum Diproses</span>';
          }

          const noteHtml = app.note ?
            `<div class="mt-2 small bg-white p-2 border rounded text-dark"><strong>Catatan:</strong> ${escapeHtml(app.note)}</div>` : '';

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

      const vehicleText = data.vehicle ? `${escapeHtml(data.vehicle.name)} (${escapeHtml(data.vehicle.license_plate)})` : '-';
      const driverText = data.driver ? `${escapeHtml(data.driver.name)}${data.driver.phone ? ' (' + escapeHtml(data.driver.phone) + ')' : ''}` : '-';
      const startLocText = data.start_location ? escapeHtml(data.start_location.name) : escapeHtml(data.start_address || '-');
      const destLocText = data.destination_location ? escapeHtml(data.destination_location.name) : escapeHtml(data.destination_address || '-');
      const startDateText = data.start_date ? formatDateTimeStr(data.start_date) : '-';
      const endDateText = data.end_date ? formatDateTimeStr(data.end_date) : '-';

      return `
        <div class="row g-3">
          <div class="col-12">
            <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-1">Informasi Pemesanan & Armada</small>
          </div>
          <div class="col-md-6 mt-1">
            <div class="p-3 rounded-3 h-100 border">
              <div class="mb-2"><strong>Kode:</strong> <span class="text-primary font-monospace fs-6">${escapeHtml(data.booking_code)}</span></div>
              <div class="mb-2"><strong>Status:</strong> ${bookingStatusBadge}</div>
              <div class="mb-2"><strong>Pemohon:</strong> ${data.user ? escapeHtml(data.user.name) : '-'}</div>
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
            <div class="rounded-3 border p-3 text-dark">${escapeHtml(data.purpose) || '-'}</div>
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
