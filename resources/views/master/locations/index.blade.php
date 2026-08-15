@extends('layouts.app')

@section('title', 'Data Master Lokasi & Region')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div class="page-heading-copy mb-0">
    <span class="page-icon bg-warning text-dark"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></span>
    <div>
      <p class="eyebrow mb-1 text-warning fw-bold"><i class="bi bi-folder2-open"></i> Master Data</p>
      <h1 class="h3 mb-1">Data Master Lokasi & Region</h1>
      <p class="text-muted mb-0">Kelola lokasi operasional.</p>
    </div>
  </div>

  @if(Auth::user()->role?->name === 'admin')
  <button type="button" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm" id="btnCreateLocation">
    <i class="bi bi-plus-lg me-1"></i> Tambah Lokasi
  </button>
  @endif
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100" id="tableLocations">
      <thead class="table-light">
        <tr>
          <th class="text-center">No.</th>
          <th>Nama Lokasi</th>
          <th>Kendaraan</th>
          <th>Wilayah / Region</th>
          <th>Tipe Lokasi</th>
          <th>Alamat Lengkap</th>
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

<!-- MODAL FORM TAMBAH / EDIT LOKASI -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="locationModalLabel">
          <i class="bi bi-geo-alt me-2"></i> <span id="modalTitle">Tambah Lokasi Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formLocation" method="POST" action="{{ route('locations.store') }}" class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="modal-body p-4">
          <div class="row g-3">
            <!-- Nama Lokasi -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputName">Nama Lokasi <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="inputName" name="name" placeholder="Contoh: Lokasi Tambang Morowali Site A" required>
              <div class="invalid-feedback" id="err-name">Nama lokasi wajib diisi.</div>
            </div>

            <!-- Region / Wilayah -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectRegion">Wilayah / Region</label>
              <select class="form-select" id="selectRegion" name="region_id">
                <option value="" selected>-- Pilih Region --</option>
                @foreach($regions as $reg)
                  <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                @endforeach
              </select>
            </div>

            <!-- Tipe Lokasi -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectType">Tipe Lokasi <span class="text-danger">*</span></label>
              <select class="form-select" id="selectType" name="type" required>
                <option value="mine_site" selected>Lokasi Tambang (Mine Site)</option>
                <option value="head_office">Kantor Pusat (Head Office)</option>
                <option value="branch_office">Kantor Cabang (Branch Office)</option>
              </select>
              <div class="invalid-feedback" id="err-type">Pilih tipe lokasi.</div>
            </div>

            <!-- Alamat Lengkap -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputAddress">Alamat Lengkap / Keterangan Area</label>
              <textarea class="form-control" id="inputAddress" name="address" rows="3" placeholder="Contoh: Morowali Site A Area Pertambangan Nikel Blok Barat"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary fw-bold" id="btnSaveLocation">
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
    const modalEl = document.getElementById('locationModal');
    const locationModal = new bootstrap.Modal(modalEl);

    const isAdmin = @json(Auth::user()->role?->name === 'admin');

    const locationColumns = [
      { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
      {
        data: 'name',
        name: 'name',
        render: function(data) {
          return `${escapeHtml(data)}`;
        }
      },
      {
        data: 'vehicles_count',
        name: 'vehicles_count',
        className: 'text-center',
        render: function(data, type, row) {
          const vCount = row.vehicles_count ?? 0;
          return `${vCount}`;
        }
      },
      {
        data: 'region',
        name: 'region.name',
        render: function(data) {
          if (!data || !data.name) return '<span class="text-muted fst-italic">-</span>';
          return `${escapeHtml(data.name)}`;
        }
      },
      {
        data: 'type',
        name: 'type',
        render: function(data) {
          if (data === 'head_office') {
            return 'Kantor Pusat';
          } else if (data === 'branch_office') {
            return 'Kantor Cabang';
          }
          return 'Lokasi Tambang';
        }
      },
      {
        data: 'address',
        name: 'address',
        render: function(data) {
          if (!data) return '<span class="text-muted fst-italic">-</span>';
          return `<small class="text-secondary">${escapeHtml(data)}</small>`;
        }
      }
    ];

    if (isAdmin) {
      locationColumns.push({
        data: 'id',
        name: 'id',
        orderable: false,
        searchable: false,
        width: '1%',
        className: 'text-center text-nowrap',
        render: function(data, type, row) {
          return `
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-primary btn-edit" data-id="${data}" title="Edit Lokasi">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button type="button" class="btn btn-outline-danger btn-delete" data-id="${data}" data-name="${escapeHtml(row.name)}" title="Hapus Lokasi">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          `;
        }
      });
    }

    // 1. Inisialisasi DataTables Server-Side Processing
    const tableLocations = $('#tableLocations').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('locations.index') }}",
      columns: locationColumns,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ lokasi",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data lokasi yang cocok",
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

    // Tombol Tambah Lokasi
    $('#btnCreateLocation').on('click', function() {
      $('#formLocation')[0].reset();
      $('#formLocation').removeClass('was-validated');
      $('.invalid-feedback').text('');
      $('#modalTitle').text('Tambah Lokasi Baru');
      $('#formMethod').val('POST');
      $('#formLocation').attr('action', "{{ route('locations.store') }}");
      locationModal.show();
    });

    // Submit Form (AJAX)
    $('#formLocation').on('submit', function(e) {
      e.preventDefault();

      const form = $(this);
      const url = form.attr('action');
      const formData = form.serialize();

      $('#btnSaveLocation').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...');

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          $('#btnSaveLocation').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Data');
          if (response.success) {
            locationModal.hide();
            tableLocations.ajax.reload(null, false);

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
          $('#btnSaveLocation').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Data');
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

    // Tombol Edit Lokasi (AJAX Fetch Data)
    $(document).on('click', '.btn-edit', function() {
      const locationId = $(this).data('id');
      const url = "{{ url('locations') }}/" + locationId;

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
          $('#formLocation').removeClass('was-validated');
          $('#modalTitle').text('Edit Lokasi: ' + data.name);
          $('#formMethod').val('PUT');
          $('#formLocation').attr('action', url);

          $('#inputName').val(data.name);
          $('#selectRegion').val(data.region_id);
          $('#selectType').val(data.type);
          $('#inputAddress').val(data.address);

          locationModal.show();
        },
        error: function() {
          Swal.fire('Error', 'Gagal mengambil data lokasi dari server.', 'error');
        }
      });
    });

    // Tombol Hapus Lokasi (AJAX Delete)
    $(document).on('click', '.btn-delete', function() {
      const locationId = $(this).data('id');
      const locationName = $(this).data('name');
      const deleteUrl = "{{ url('locations') }}/" + locationId;

      Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus lokasi "' + locationName + '"?',
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
                tableLocations.ajax.reload(null, false);
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
              Swal.fire('Error', 'Gagal menghapus data lokasi.', 'error');
            }
          });
        }
      });
    });
  });
</script>
@endsection
