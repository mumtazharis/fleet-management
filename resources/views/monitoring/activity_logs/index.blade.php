@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div>
    <h1 class="h3 mb-1">Log Aktivitas Sistem</h1>
    <p class="text-muted mb-0">Audit trail dan catatan riwayat seluruh aktivitas operasional armada tambang oleh pengguna.</p>
  </div>
</div>

<!-- STAT SUMMARY CARDS -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-primary-subtle text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-clock-history fs-4"></i>
        </div>
        <div>
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">TOTAL AKTIVITAS TERCATAT</small>
          <h5 class="mb-0 fw-bold text-dark">{{ number_format($totalLogs, 0, ',', '.') }} Catatan</h5>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-success-subtle text-success p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-calendar-check fs-4"></i>
        </div>
        <div>
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">AKTIVITAS HARI INI</small>
          <h5 class="mb-0 fw-bold text-dark">{{ number_format($todayLogs, 0, ',', '.') }} Aktivitas</h5>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-warning-subtle text-warning-emphasis p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-people fs-4"></i>
        </div>
        <div>
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">PENGGUNA AKTIF</small>
          <h5 class="mb-0 fw-bold text-dark">{{ number_format($uniqueUsers, 0, ',', '.') }} Pengguna</h5>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100" id="tableActivityLogs">
      <thead class="table-light">
        <tr>
          <th class="text-center">No.</th>
          <th>Waktu & Tanggal</th>
          <th>Pengguna</th>
          <th>Aksi / Event</th>
          <th>Modul / Entitas</th>
          <th>Keterangan Aktivitas</th>
          <th>Alamat IP</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Data loaded dynamically via DataTables Server-Side -->
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL DETAIL AKTIVITAS -->
<div class="modal fade" id="detailActivityModal" tabindex="-1" aria-labelledby="detailActivityModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="detailActivityModalLabel">
          <i class="bi bi-info-circle me-2"></i>Rincian Audit Log Aktivitas
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="detailActivityContent">
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
    const detailModalEl = document.getElementById('detailActivityModal');
    const detailModal = new bootstrap.Modal(detailModalEl);

    // Format action badges
    function renderActionBadge(action) {
      if (!action) return '<span class="badge p-1 text-bg-secondary">-</span>';
      const act = action.toUpperCase();

      if (act.includes('CREATE') || act.includes('STORE')) {
        return `<span class="badge p-1 text-bg-success">${escapeHtml(action)}</span>`;
      } else if (act.includes('UPDATE') || act.includes('EDIT')) {
        return `<span class="badge p-1 text-bg-primary">${escapeHtml(action)}</span>`;
      } else if (act.includes('DELETE') || act.includes('DESTROY') || act.includes('REJECT')) {
        return `<span class="badge p-1 text-bg-danger">${escapeHtml(action)}</span>`;
      } else if (act.includes('APPROVE') || act.includes('COMPLETE')) {
        return `<span class="badge p-1 text-bg-info">${escapeHtml(action)}</span>`;
      } else if (act.includes('CANCEL')) {
        return `<span class="badge p-1 text-bg-warning text-dark">${escapeHtml(action)}</span>`;
      } else if (act.includes('LOGIN') || act.includes('LOGOUT')) {
        return `<span class="badge p-1 text-bg-dark">${escapeHtml(action)}</span>`;
      }

      return `<span class="badge p-1 text-bg-secondary">${escapeHtml(action)}</span>`;
    }

    // Format entity badges
    function renderEntityBadge(entity) {
      if (!entity) return '<span class="text-muted fst-italic">-</span>';
      return `<span class="badge p-1 text-bg-light border text-dark fw-semibold">${escapeHtml(entity)}</span>`;
    }

    // 1. DataTables Server-Side Processing
    const tableActivityLogs = $('#tableActivityLogs').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('activity-logs.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
        {
          data: 'created_at',
          name: 'created_at',
          render: function(data) {
            return formatDateTime(data);
          }
        },
        {
          data: 'user',
          name: 'user.name',
          render: function(data, type, row) {
            if (!row.user) {
              return '<span class="text-muted fst-italic"><i class="bi bi-robot me-1"></i>Sistem</span>';
            }
            const userName = escapeHtml(row.user.name);
            const userRole = row.user.role ? escapeHtml(row.user.role.label || row.user.role.name) : 'User';
            return `
              <div class="lh-sm">
                <strong class="d-block text-dark">${userName}</strong>
                <small class="text-muted">${userRole}</small>
              </div>
            `;
          }
        },
        {
          data: 'action',
          name: 'action',
          render: function(data) {
            return renderActionBadge(data);
          }
        },
        {
          data: 'entity_type',
          name: 'entity_type',
          render: function(data) {
            return renderEntityBadge(data);
          }
        },
        {
          data: 'description',
          name: 'description',
          render: function(data) {
            if (!data) return '<span class="text-muted fst-italic">-</span>';
            return `<span class="text-dark">${escapeHtml(data)}</span>`;
          }
        },
        {
          data: 'ip_address',
          name: 'ip_address',
          render: function(data) {
            if (!data) return '<span class="text-muted fst-italic">-</span>';
            return `<code class="text-secondary small font-monospace">${escapeHtml(data)}</code>`;
          }
        },
        {
          data: 'id',
          name: 'id',
          orderable: false,
          searchable: false,
          width: '1%',
          className: 'text-center text-nowrap',
          render: function(data) {
            return `
              <button type="button" class="btn btn-outline-info btn-sm btn-detail" data-id="${data}" title="Rincian Audit Log">
                <i class="bi bi-eye me-1"></i> Detail
              </button>
            `;
          }
        }
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ log aktivitas",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data log aktivitas yang cocok",
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

    // 2. Tombol Detail Log Aktivitas
    $(document).on('click', '.btn-detail', function() {
      const id = $(this).data('id');
      const url = "{{ url('activity-logs') }}/" + id;

      $('#detailActivityContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
      detailModal.show();

      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          const userName = data.user ? escapeHtml(data.user.name) : 'Sistem / Otomatis';
          const userEmail = data.user ? escapeHtml(data.user.email) : '-';
          const userRole = data.user && data.user.role ? escapeHtml(data.user.role.label || data.user.role.name) : '-';
          const ipAddr = data.ip_address ? escapeHtml(data.ip_address) : '-';
          const entityType = data.entity_type ? escapeHtml(data.entity_type) : '-';
          const entityId = data.entity_id ? '#' + data.entity_id : '-';

          const html = `
            <div class="row g-3">
              <div class="col-12">
                <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-1">Informasi Pengguna & Sesi</small>
              </div>
              <div class="col-md-6 mt-1">
                <div class="p-3 rounded-3 h-100 border bg-light">
                  <div class="mb-2"><strong>Nama Pengguna:</strong> <span class="text-dark fw-bold">${userName}</span></div>
                  <div class="mb-2"><strong>Email:</strong> <span class="text-secondary">${userEmail}</span></div>
                  <div class="mb-2"><strong>Role:</strong> <span class="badge p-1 text-bg-dark">${userRole}</span></div>
                  <div class="mb-2"><strong>Alamat IP:</strong> <code class="font-monospace text-primary">${ipAddr}</code></div>
                </div>
              </div>
              <div class="col-md-6 mt-1">
                <div class="p-3 rounded-3 h-100 border bg-light">
                  <div class="mb-2"><strong>Waktu Kejadian:</strong> <span class="text-dark fw-bold">${formatDateTime(data.created_at)}</span></div>
                  <div class="mb-2"><strong>Tipe Aksi (Event):</strong> ${renderActionBadge(data.action)}</div>
                  <div class="mb-2"><strong>Modul / Entitas:</strong> <span class="fw-semibold text-dark">${entityType}</span></div>
                  <div class="mb-2"><strong>ID Entitas:</strong> <span class="badge p-1 text-bg-secondary">${entityId}</span></div>
                </div>
              </div>
              <div class="col-12 mt-3">
                <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-2">Deskripsi & Kronologi Aktivitas</small>
                <div class="rounded-3 border p-3 bg-white text-dark shadow-sm">
                  ${escapeHtml(data.description) || 'Tidak ada rincian keterangan.'}
                </div>
              </div>
            </div>
          `;

          $('#detailActivityContent').html(html);
        },
        error: function() {
          $('#detailActivityContent').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Gagal memuat rincian log aktivitas.</div>');
        }
      });
    });
  });
</script>
@endsection
