@extends('layouts.app')

@section('title', 'Persetujuan Pemesanan - Fleet Management')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 mb-1">Persetujuan Pemesanan Kendaraan</h1>
    <p class="text-muted mb-0">Kelola dan proses persetujuan (Approve/Reject) pemesanan armada tambang berjenjang.</p>
  </div>
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle w-100" id="tableApprovals">
      <thead class="table-light">
        <tr>
          <th class="text-center" style="width: 40px; padding-left: 8px !important; padding-right: 8px !important;">No.</th>
          <th>Kode & Pemohon</th>
          <th>Jadwal & Rute</th>
          <th>Kendaraan & Driver</th>
          <th>Tingkat Penyetuju</th>
          <th>Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- DataTables Server-Side Render -->
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL REJECT REASON -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold" id="rejectModalLabel"><i class="bi bi-x-circle me-2"></i>Tolak Pemesanan Kendaraan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formReject" action="" method="POST" class="needs-validation" novalidate>
        @csrf
        <div class="modal-body p-4">
          <p class="text-secondary mb-3">Harap berikan alasan penolakan pemesanan <strong id="rejectBookingCode" class="text-dark"></strong> agar pemohon mengetahui alasan tidak disetujui.</p>

          <div class="mb-3">
            <label for="inputRejectNote" class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
            <textarea class="form-control" id="inputRejectNote" name="note" rows="4" placeholder="Contoh: Unit kendaraan sedang menjalani maintenance mendadak / Jadwal bentrok..." required></textarea>
            <div class="invalid-feedback" id="err-note">Alasan penolakan wajib diisi.</div>
          </div>
        </div>
        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger fw-bold" id="btnSubmitReject">
            <i class="bi bi-x-circle me-1"></i> Konfirmasi Penolakan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL DETAIL PEMESANAN -->
<div class="modal fade" id="detailBookingModal" tabindex="-1" aria-labelledby="detailBookingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="detailBookingModalLabel">
          <i class="bi bi-journal-text me-2"></i>Rincian Pemesanan Kendaraan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="detailContent">
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
    const rejectModalEl = document.getElementById('rejectModal');
    const rejectModal = new bootstrap.Modal(rejectModalEl);
    const detailModalEl = document.getElementById('detailBookingModal');
    const detailModal = new bootstrap.Modal(detailModalEl);

    const currentUserId = {{ Auth::id() }};
    const currentUserRole = @json(strtolower(Auth::user()->role?->name ?? ''));
    const currentUserLevel = {{ Auth::user()->role?->level ?? 0 }};

    // 1. DataTables Server-Side Processing
    const tableApprovals = $('#tableApprovals').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('approvals.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
        {
          data: 'booking.booking_code',
          name: 'booking.booking_code',
          render: function(data, type, row) {
            const b = row.booking;
            if (!b) return '-';
            const userName = b.user ? escapeHtml(b.user.name) : 'System';
            return `
              <div class="lh-sm">
                <strong class="font-monospace d-block">${escapeHtml(b.booking_code)}</strong>
                <small class="text-muted">Pemohon: ${userName}</small>
              </div>
            `;
          }
        },
        {
          data: 'booking.start_date',
          name: 'booking.start_date',
          render: function(data, type, row) {
            const b = row.booking;
            if (!b) return '-';
            const startDate = b.start_date ? formatDateTime(b.start_date) : '-';
            const endDate = b.end_date ? formatDateTime(b.end_date) : '-';
            const startLoc = b.start_location ? escapeHtml(b.start_location.name) : escapeHtml(b.start_address || '-');
            const destLoc = b.destination_location ? escapeHtml(b.destination_location.name) : escapeHtml(b.destination_address || '-');
            return `
              <div class="lh-sm">
                <small class="d-block text-dark fw-semibold"><i class="bi bi-calendar-range me-1"></i>${startDate} - ${endDate}</small>
                <small class="d-block text-secondary mt-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${startLoc} &rarr; ${destLoc}</small>
              </div>
            `;
          }
        },
        {
          data: 'booking.vehicle.name',
          name: 'booking.vehicle.name',
          render: function(data, type, row) {
            const b = row.booking;
            if (!b) return '-';
            const vName = b.vehicle ? `${escapeHtml(b.vehicle.name)} (${escapeHtml(b.vehicle.license_plate)})` : '-';
            const dName = b.driver ? escapeHtml(b.driver.name) : '-';
            return `
              <div class="lh-sm">
                <small class="d-block text-dark fw-semibold"><i class="bi bi-truck me-1"></i>${vName}</small>
                <small class="text-secondary"><i class="bi bi-person me-1"></i>Driver: ${dName}</small>
              </div>
            `;
          }
        },
        {
          data: 'approval_level',
          name: 'approval_level',
          render: function(data, type, row) {
            const approverName = row.approver ? escapeHtml(row.approver.name) : 'User';
            if (data == 1) {
              return `Level 1 (SPV: ${approverName})`;
            }
            return `Level 2 (Manager: ${approverName})`;
          }
        },
        {
          data: 'status',
          name: 'status',
          render: function(data, type, row) {
            const b = row.booking;
            if (b && (b.status === 'cancelled' || b.deleted_at)) {
              return '<span class="badge p-1 text-bg-secondary">Dibatalkan</span>';
            }
            if (data === 'approved') {
              return '<span class="badge p-1 text-bg-success">Disetujui</span>';
            } else if (data === 'rejected') {
              const noteStr = row.note ? 'Catatan: ' + escapeHtml(row.note) : '';
              return `<span class="badge p-1 text-bg-danger" title="${noteStr}">Ditolak</span>`;
            }
            return '<span class="badge p-1 text-bg-warning text-dark">Menunggu</span>';
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
            const b = row.booking;
            const bookingId = b ? b.id : 0;

            const btnDetail = `
              <button type="button" class="btn btn-outline-info btn-sm btn-detail" data-booking-id="${bookingId}" title="Lihat Detail Pemesanan">
                <i class="bi bi-eye me-1"></i> Detail
              </button>
            `;

            if (row.status !== 'pending' || (b && (b.status === 'cancelled' || b.deleted_at))) {
              return btnDetail;
            }

            const isAssignedApprover = (row.approver_id == currentUserId);
            const hasMatchingLevel = (currentUserLevel == row.approval_level && currentUserRole !== 'admin');
            let canProcess = (isAssignedApprover || hasMatchingLevel);

            if (row.approval_level == 2) {
              const l1Approved = b && b.approvals && b.approvals.some(a => a.approval_level == 1 && a.status === 'approved');
              if (!l1Approved) {
                canProcess = false;
              }
            }

            if (!canProcess) {
              return btnDetail;
            }

            const bCode = b ? escapeHtml(b.booking_code) : '';
            return `
              <div class="btn-group btn-group-sm">
                ${btnDetail}
                <button type="button" class="btn btn-success btn-sm btn-approve ms-1" data-id="${row.id}" data-code="${bCode}" title="Setujui Pemesanan">
                  <i class="bi bi-check-lg me-1"></i> Setujui
                </button>
                <button type="button" class="btn btn-danger btn-sm btn-reject ms-1" data-id="${row.id}" data-code="${bCode}" title="Tolak Pemesanan">
                  <i class="bi bi-x-lg me-1"></i> Tolak
                </button>
              </div>
            `;
          }
        }
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ persetujuan",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data persetujuan yang cocok",
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

    // 2. Process Approval (Approve Button)
    $(document).on('click', '.btn-approve', function() {
      const approvalId = $(this).data('id');
      const bookingCode = $(this).data('code');
      const approveUrl = "{{ url('approvals') }}/" + approvalId + "/approve";

      Swal.fire({
        title: 'Konfirmasi Persetujuan',
        text: 'Apakah Anda yakin ingin menyetujui pemesanan kendaraan "' + bookingCode + '"?',
        icon: 'question',
        showCancelButton: true,
        confirmColor: '#198754',
        cancelColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Ya, Setujui!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: approveUrl,
            type: 'POST',
            data: {
              _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                tableApprovals.ajax.reload(null, false);
                Swal.fire({
                  icon: 'success',
                  title: 'Disetujui!',
                  text: response.message,
                  timer: 2500,
                  showConfirmButton: false
                });
              }
            },
            error: function(xhr) {
              const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyetujui pemesanan.';
              Swal.fire('Error', msg, 'error');
            }
          });
        }
      });
    });

    // 3. Trigger Reject Modal (Reject Button)
    let activeRejectApprovalId = null;
    $(document).on('click', '.btn-reject', function() {
      activeRejectApprovalId = $(this).data('id');
      const bookingCode = $(this).data('code');

      $('#rejectBookingCode').text(bookingCode);
      $('#inputRejectNote').val('');
      $('#formReject').removeClass('was-validated');
      $('#err-note').hide();
      $('#formReject').attr('action', "{{ url('approvals') }}/" + activeRejectApprovalId + "/reject");

      rejectModal.show();
    });

    // 4. Submit Rejection Form with Reason Note
    $('#formReject').on('submit', function(e) {
      e.preventDefault();
      const form = $(this);
      const url = form.attr('action');

      $('#btnSubmitReject').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...');
      $('.invalid-feedback').hide();

      $.ajax({
        url: url,
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
          $('#btnSubmitReject').prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i> Konfirmasi Penolakan');
          if (response.success) {
            rejectModal.hide();
            tableApprovals.ajax.reload(null, false);

            Swal.fire({
              icon: 'success',
              title: 'Ditolak!',
              text: response.message,
              timer: 2500,
              showConfirmButton: false
            });
          }
        },
        error: function(xhr) {
          $('#btnSubmitReject').prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i> Konfirmasi Penolakan');
          if (xhr.status === 422) {
            const errors = xhr.responseJSON.errors;
            form.addClass('was-validated');
            if (errors.note) {
              $('#err-note').text(errors.note[0]).show();
            }
          } else {
            const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menolak pemesanan.';
            Swal.fire('Error', msg, 'error');
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
          const approverName = app.approver ? escapeHtml(app.approver.name) : 'Approver';
          const roleName = app.approver && app.approver.role ? escapeHtml(app.approver.role.label || app.approver.role.name) : '-';
          
          let badge = '<span class="badge text-bg-warning text-dark">Menunggu</span>';
          if (app.status === 'approved') {
            badge = '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Disetujui</span>';
          } else if (app.status === 'rejected') {
            badge = '<span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Ditolak</span>';
          }

          const timeBadge = app.responded_at ?
            `<span class="badge text-bg-light border text-muted ms-2"><i class="bi bi-clock me-1"></i>${formatDateTime(app.responded_at)}</span>` :
            '<span class="badge text-bg-light border text-muted ms-2"><i class="bi bi-hourglass me-1"></i>Belum Diproses</span>';

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
      const startDateText = data.start_date ? formatDateTime(data.start_date) : '-';
      const endDateText = data.end_date ? formatDateTime(data.end_date) : '-';

      return `
        <div class="row g-3">
          <div class="col-12">
            <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-1">Informasi Pemesanan & Armada</small>
          </div>
          <div class="col-md-6 mt-1">
            <div class="p-3 rounded-3 h-100 border">
              <div class="mb-2"><strong>Kode:</strong> <span class="text-primary font-monospace fs-6">${escapeHtml(data.booking_code)}</span></div>
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

    // 5. Detail Booking Modal
    $(document).on('click', '.btn-detail', function() {
      const bookingId = $(this).data('booking-id');
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
          $('#detailContent').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Gagal memuat rincian pemesanan.</div>');
        }
      });
    });
  });
</script>
@endsection
