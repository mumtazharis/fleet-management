@extends('layouts.app')

@section('title', 'Data Master Driver')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div>
    <h1 class="h3 mb-1">Data Master Driver</h1>
    <p class="text-muted mb-0">Kelola data pengemudi/driver, lisensi SIM, dan status penugasan operasional.</p>
  </div>
  

  @if(Auth::user()->role?->name === 'admin')
  <button type="button" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm" id="btnCreateDriver">
    <i class="bi bi-plus-lg me-1"></i> Tambah Driver
  </button>
  @endif
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100" id="tableDrivers">
      <thead class="table-light">
        <tr>
          <th class="text-center">No.</th>
          <th>Nama Driver</th>
          <th>Nomor Telepon</th>
          <th>SIM</th>
          <th>Status Operasional</th>
          @if(Auth::user()->role?->name === 'admin')
            <th class="text-center">Aksi</th>
          @endif
        </tr>
      </thead>
      <tbody>
        <!-- Data loaded dynamically via DataTables Server-Side -->
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL FORM TAMBAH / EDIT DRIVER -->
<div class="modal fade" id="driverModal" tabindex="-1" aria-labelledby="driverModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="driverModalLabel">
          <i class="bi bi-person-badge me-2"></i> <span id="modalTitle">Tambah Driver Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formDriver" method="POST" action="{{ route('drivers.store') }}" class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="modal-body p-4">
          <div class="row g-3">
            <!-- Nama Driver -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputName">Nama Lengkap Driver <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="inputName" name="name" placeholder="Contoh: Rudi Hermawan" required>
              <div class="invalid-feedback" id="err-name">Nama driver wajib diisi.</div>
            </div>

            <!-- Nomor Telepon -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputPhone">Nomor Telepon / HP</label>
              <input type="text" class="form-control" id="inputPhone" name="phone" placeholder="Contoh: 085211112222">
              <div class="invalid-feedback" id="err-phone">Nomor telepon tidak valid.</div>
            </div>

            <!-- Nomor SIM -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputLicenseNumber">Nomor / Jenis SIM</label>
              <input type="text" class="form-control font-monospace" id="inputLicenseNumber" name="license_number" placeholder="Contoh: SIM BII UMUM - 902181">
              <div class="invalid-feedback" id="err-license_number">Nomor SIM tidak valid.</div>
            </div>

          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary fw-bold" id="btnSaveDriver">
            <i class="bi bi-check-circle me-1"></i> Simpan Data
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  $(document).ready(function() {
    const modalEl = document.getElementById('driverModal');
    const driverModal = new bootstrap.Modal(modalEl);

    const isAdmin = @json(Auth::user()->role?->name === 'admin');

    const driverColumns = [
      { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
      {
        data: 'name',
        name: 'name',
        render: function(data, type, row) {
          return `${escapeHtml(data)}`;
        }
      },
      {
        data: 'phone',
        name: 'phone',
        render: function(data) {
          if (!data) return '<span class="text-muted fst-italic">-</span>';
          return `${escapeHtml(data)}`;
        }
      },
      {
        data: 'license_number',
        name: 'license_number',
        render: function(data) {
          if (!data) return '<span class="text-muted fst-italic">-</span>';
          return `${escapeHtml(data)}`;
        }
      },
      {
        data: 'status',
        name: 'status',
        render: function(data) {
          if (data === 'available') {
            return '<span class="badge p-1 text-bg-success">Tersedia</span>';
          } else if (data === 'reserved') {
            return '<span class="badge p-1 text-bg-warning text-dark">Dipesan (Reserved)</span>';
          } else if (data === 'on_trip') {
            return '<span class="badge p-1 text-bg-primary">Sedang Bertugas</span>';
          }
          return '<span class="badge p-1 text-bg-secondary">Libur / Nonaktif</span>';
        }
      }
    ];

    if (isAdmin) {
      driverColumns.push({
        data: 'id',
        name: 'id',
        orderable: false,
        searchable: false,
        width: '1%',
        className: 'text-center text-nowrap',
        render: function(data, type, row) {
          return `
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-primary btn-edit" data-id="${data}" title="Edit Driver">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button type="button" class="btn btn-outline-danger btn-delete" data-id="${data}" data-name="${escapeHtml(row.name)}" title="Hapus Driver">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          `;
        }
      });
    }

    // 1. Inisialisasi DataTables Server-Side Processing
    const tableDrivers = $('#tableDrivers').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('drivers.index') }}",
      columns: driverColumns,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ driver",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data driver yang cocok",
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

    // Tombol Tambah Driver
    $('#btnCreateDriver').on('click', function() {
      $('#formDriver')[0].reset();
      $('#formDriver').removeClass('was-validated');
      $('.invalid-feedback').text('');
      $('#modalTitle').text('Tambah Driver Baru');
      $('#formMethod').val('POST');
      $('#formDriver').attr('action', "{{ route('drivers.store') }}");
      driverModal.show();
    });

    // Submit Form (AJAX)
    $('#formDriver').on('submit', function(e) {
      e.preventDefault();

      const form = $(this);
      const url = form.attr('action');
      const formData = form.serialize();

      $('#btnSaveDriver').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...');

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          $('#btnSaveDriver').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Data');
          if (response.success) {
            driverModal.hide();
            tableDrivers.ajax.reload(null, false);

            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: response.message,
              timer: 2000,
              showConfirmButton: false
            });
          }
        },
        error: function(xhr) {
          $('#btnSaveDriver').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Data');
          if (xhr.status === 422) {
            const errors = xhr.responseJSON.errors;
            form.addClass('was-validated');
            $.each(errors, function(field, messages) {
              $('#err-' + field).text(messages[0]).show();
            });
          } else {
            Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan data.', 'error');
          }
        }
      });
    });

    // Tombol Edit Driver (AJAX Fetch Data)
    $(document).on('click', '.btn-edit', function() {
      const driverId = $(this).data('id');
      const url = "{{ url('drivers') }}/" + driverId;

      Swal.fire({
        title: 'Memuat data...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          Swal.close();
          $('#formDriver').removeClass('was-validated');
          $('#modalTitle').text('Edit Data Driver: ' + data.name);
          $('#formMethod').val('PUT');
          $('#formDriver').attr('action', url);

          $('#inputName').val(data.name);
          $('#inputPhone').val(data.phone);
          $('#inputLicenseNumber').val(data.license_number);

          driverModal.show();
        },
        error: function() {
          Swal.fire('Error', 'Gagal mengambil data driver dari server.', 'error');
        }
      });
    });

    // Tombol Hapus Driver (AJAX Delete)
    $(document).on('click', '.btn-delete', function() {
      const driverId = $(this).data('id');
      const driverName = $(this).data('name');
      const deleteUrl = "{{ url('drivers') }}/" + driverId;

      Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus data driver "' + driverName + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmColor: '#dc3545',
        cancelColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash me-1"></i> Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: deleteUrl,
            type: 'POST',
            data: {
              _method: 'DELETE',
              _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                tableDrivers.ajax.reload(null, false);
                Swal.fire({
                  icon: 'success',
                  title: 'Terhapus!',
                  text: response.message,
                  timer: 2000,
                  showConfirmButton: false
                });
              }
            },
            error: function() {
              Swal.fire('Error', 'Gagal menghapus data driver.', 'error');
            }
          });
        }
      });
    });
  });
</script>
@endsection
