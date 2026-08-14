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
          <th>Persetujuan Berjenjang</th>
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
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="bookingModalLabel">
          <i class="bi bi-journal-plus text-warning me-2"></i> Form Pemesanan Kendaraan
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formBooking" method="POST" action="{{ route('bookings.store') }}" class="needs-validation" novalidate>
        @csrf

        <div class="modal-body p-4">
          <div class="alert alert-info py-2 px-3 mb-4 rounded-3 small border-0 bg-info bg-opacity-10 text-info">
            <i class="bi bi-info-circle-fill me-1"></i> Halaman ini hanya menampilkan <strong>Kendaraan & Driver yang berstatus TERSEDIA</strong> dan tidak sedang dalam proses pemesanan/approval aktif.
          </div>

          <div class="row g-3">
            <!-- Pilih Kendaraan (Available Only) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectVehicle">Pilih Kendaraan (Tersedia) <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectVehicle" name="vehicle_id" required>
                <option value="" selected disabled>-- Pilih Kendaraan --</option>
                @forelse($availableVehicles as $v)
                  <option value="{{ $v->id }}">
                    {{ $v->name }} ({{ $v->license_plate }}) - [{{ $v->type === 'passenger' ? 'Orang' : 'Barang' }} @ {{ $v->location?->name ?? 'Pool' }}]
                  </option>
                @empty
                  <option value="" disabled>-- Tidak ada kendaraan tersedia saat ini --</option>
                @endforelse
              </select>
              <div class="invalid-feedback" id="err-vehicle_id">Pilih kendaraan yang tersedia.</div>
            </div>

            <!-- Pilih Driver (Available Only) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectDriver">Pilih Driver / Pengemudi (Tersedia) <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectDriver" name="driver_id" required>
                <option value="" selected disabled>-- Pilih Driver --</option>
                @forelse($availableDrivers as $d)
                  <option value="{{ $d->id }}">
                    {{ $d->name }} ({{ $d->phone ?? 'No Phone' }})
                  </option>
                @empty
                  <option value="" disabled>-- Tidak ada driver tersedia saat ini --</option>
                @endforelse
              </select>
              <div class="invalid-feedback" id="err-driver_id">Pilih driver yang tersedia.</div>
            </div>

            <!-- Lokasi Penjemputan / Asal -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectStartLocation">Lokasi Penjemputan / Asal <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectStartLocation" name="start_location_id" required>
                <option value="" selected disabled>-- Pilih Lokasi Asal --</option>
                @foreach($locations as $loc)
                  <option value="{{ $loc->id }}">{{ $loc->name }} ({{ ucfirst($loc->type) }})</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-start_location_id">Pilih lokasi penjemputan/asal.</div>
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

            <!-- Tanggal & Waktu Mulai -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputStartDate">Tanggal & Waktu Mulai <span class="text-danger">*</span></label>
              <input type="datetime-local" class="form-control" id="inputStartDate" name="start_date" required>
              <div class="invalid-feedback" id="err-start_date">Tanggal mulai wajib diisi.</div>
            </div>

            <!-- Tanggal & Waktu Selesai -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputEndDate">Tanggal & Waktu Selesai <span class="text-danger">*</span></label>
              <input type="datetime-local" class="form-control" id="inputEndDate" name="end_date" required>
              <div class="invalid-feedback" id="err-end_date">Tanggal selesai wajib diisi.</div>
            </div>

            <!-- Persetujuan Level 1 (Atasan L1 dengan Role Level 1 / SPV) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectApprover1">Pihak Penyetuju Level 1 (Atasan L1 / SPV) <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectApprover1" name="approver_1_id" required>
                <option value="" selected disabled>-- Pilih Penyetuju Level 1 (Role L1) --</option>
                @foreach($approversL1 as $appr)
                  <option value="{{ $appr->id }}">
                    {{ $appr->name }} ({{ $appr->role?->label ?? 'Supervisor' }})
                  </option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-approver_1_id">Pilih Pihak Penyetuju Level 1.</div>
            </div>

            <!-- Persetujuan Level 2 (Atasan L2 dengan Role Level 2 / Manager) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectApprover2">Pihak Penyetuju Level 2 (Atasan L2 / Manager) <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectApprover2" name="approver_2_id" required>
                <option value="" selected disabled>-- Pilih Penyetuju Level 2 (Role L2) --</option>
                @foreach($approversL2 as $appr)
                  <option value="{{ $appr->id }}">
                    {{ $appr->name }} ({{ $appr->role?->label ?? 'Manager' }})
                  </option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-approver_2_id">Pilih Pihak Penyetuju Level 2.</div>
            </div>

            <!-- Keperluan Pemakaian -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputPurpose">Keperluan Pemakaian Kendaraan <span class="text-danger">*</span></label>
              <textarea class="form-control" id="inputPurpose" name="purpose" rows="3" placeholder="Contoh: Transportasi Tim Geologis & Operasional Inspeksi Tambang Morowali Site A" required></textarea>
              <div class="invalid-feedback" id="err-purpose">Keperluan pemakaian kendaraan wajib diisi.</div>
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
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom bg-light">
        <h5 class="modal-title fw-bold" id="detailModalLabel">
          <i class="bi bi-file-earmark-text text-primary me-2"></i> Detail Pemesanan Kendaraan: <span id="detailBookingCode" class="text-primary font-monospace"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" id="detailContent">
        <!-- Loaded via AJAX -->
      </div>

      <div class="modal-footer border-top bg-light px-4 py-2">
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

    // 1. Inisialisasi DataTables Server-Side Processing
    const tableBookings = $('#tableBookings').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('bookings.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
        { data: 'code_date', name: 'booking_code' },
        { data: 'vehicle_driver', name: 'vehicle.name' },
        { data: 'route', name: 'startLocation.name' },
        { data: 'approvals_status', name: 'approvals_status', orderable: false, searchable: false },
        { data: 'status_badge', name: 'status' },
        { data: 'action', name: 'action', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' }
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

    // Tombol Tambah Pemesanan
    $('#btnCreateBooking').on('click', function() {
      $('#formBooking')[0].reset();
      $('#formBooking').removeClass('was-validated');
      $('.invalid-feedback').text('');
      $('.select2').val('').trigger('change');
      bookingModal.show();
    });

    // Submit Form (AJAX)
    $('#formBooking').on('submit', function(e) {
      e.preventDefault();

      const form = $(this);
      const url = form.attr('action');
      const formData = form.serialize();

      $('#btnSaveBooking').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mengirim...');

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

    // Tombol Detail Pemesanan
    $(document).on('click', '.btn-detail', function() {
      const bookingId = $(this).data('id');
      const url = "{{ url('bookings') }}/" + bookingId;

      Swal.fire({
        title: 'Memuat detail...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          Swal.close();
          $('#detailBookingCode').text(data.booking_code);

          let approvalsHtml = '';
          $.each(data.approvals, function(i, appr) {
            let statusBadge = '<span class="badge text-bg-warning text-dark">Menunggu</span>';
            if (appr.status === 'approved') statusBadge = '<span class="badge text-bg-success">Disetujui</span>';
            if (appr.status === 'rejected') statusBadge = '<span class="badge text-bg-danger">Ditolak</span>';

            approvalsHtml += `
              <div class="border rounded-3 p-3 mb-2 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong>Level ${appr.approval_level}: ${appr.approver ? appr.approver.name : '-'}</strong>
                  ${statusBadge}
                </div>
                <small class="text-muted d-block">Role: ${appr.approver && appr.approver.role ? appr.approver.role.label : '-'}</small>
                ${appr.note ? `<div class="mt-1 small bg-white p-2 border rounded"><strong>Catatan:</strong> ${appr.note}</div>` : ''}
              </div>
            `;
          });

          const html = `
            <div class="row g-3">
              <div class="col-md-6">
                <label class="text-muted small d-block">PEMESAN / CREATOR</label>
                <strong>${data.user ? data.user.name : '-'}</strong>
              </div>
              <div class="col-md-6">
                <label class="text-muted small d-block">KENDARAAN</label>
                <strong>${data.vehicle ? data.vehicle.name + ' (' + data.vehicle.license_plate + ')' : '-'}</strong>
              </div>
              <div class="col-md-6">
                <label class="text-muted small d-block">DRIVER</label>
                <strong>${data.driver ? data.driver.name : '-'}</strong>
              </div>
              <div class="col-md-6">
                <label class="text-muted small d-block">PERIODE PEMAKAIAN</label>
                <strong>${data.start_date} s.d. ${data.end_date}</strong>
              </div>
              <div class="col-md-6">
                <label class="text-muted small d-block">LOKASI ASAL</label>
                <strong>${data.start_location ? data.start_location.name : '-'}</strong>
              </div>
              <div class="col-md-6">
                <label class="text-muted small d-block">LOKASI TUJUAN</label>
                <strong>${data.destination_location ? data.destination_location.name : '-'}</strong>
              </div>
              <div class="col-12">
                <label class="text-muted small d-block">KEPERLUAN</label>
                <p class="mb-0 bg-light p-3 border rounded">${data.purpose}</p>
              </div>
              <div class="col-12 mt-3">
                <h6 class="fw-bold mb-2">Riwayat Persetujuan Berjenjang (Minimal 2 Level):</h6>
                ${approvalsHtml}
              </div>
            </div>
          `;

          $('#detailContent').html(html);
          detailModal.show();
        },
        error: function() {
          Swal.fire('Error', 'Gagal memuat detail pemesanan.', 'error');
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
