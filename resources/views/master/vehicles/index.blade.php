@extends('layouts.app')

@section('title', 'Data Master Kendaraan')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div class="page-heading-copy mb-0">
    <span class="page-icon bg-warning text-dark"><i class="bi bi-truck-front" aria-hidden="true"></i></span>
    <div>
      <p class="eyebrow mb-1 text-warning fw-bold"><i class="bi bi-folder2-open"></i> Master Data</p>
      <h1 class="h3 mb-1">Data Master Kendaraan</h1>
      <p class="text-muted mb-0">Kelola armada kendaraan angkutan orang & barang, status kepemilikan, dan penempatan pool.</p>
    </div>
  </div>

  @if(Auth::user()->role?->name === 'admin')
  <button type="button" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm" id="btnCreateVehicle">
    <i class="bi bi-plus-lg me-1"></i> Tambah Kendaraan
  </button>
  @endif
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-bordered align-middle w-100" id="tableVehicles">
      <thead class="table-light">
        <tr>
          <th class="text-center">No.</th>
          <th>Nama Kendaraan & Plat</th>
          <th>Jenis Kendaraan</th>
          <th>Kepemilikan</th>
          <th>Lokasi Pool</th>
          <th>Jenis BBM</th>
          <th>Status</th>
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

<!-- MODAL FORM TAMBAH / EDIT KENDARAAN -->
<div class="modal fade" id="vehicleModal" tabindex="-1" aria-labelledby="vehicleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="vehicleModalLabel">
          <i class="bi bi-truck me-2"></i> <span id="modalTitle">Tambah Kendaraan Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formVehicle" method="POST" action="{{ route('vehicles.store') }}" class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="modal-body p-4">
          <div class="row g-3">
            <!-- Nama / Model Kendaraan -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputName">Nama / Model Kendaraan <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="inputName" name="name" placeholder="Contoh: Toyota Hilux Single Cab 4x4" required>
              <div class="invalid-feedback" id="err-name">Nama kendaraan wajib diisi.</div>
            </div>

            <!-- Plat Nomor -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputLicensePlate">Plat Nomor Kendaraan <span class="text-danger">*</span></label>
              <input type="text" class="form-control font-monospace" id="inputLicensePlate" name="license_plate" placeholder="Contoh: DN 8102 AB" required>
              <div class="invalid-feedback" id="err-license_plate">Plat nomor wajib diisi.</div>
            </div>

            <!-- Jenis Kendaraan -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectType">Jenis Kendaraan <span class="text-danger">*</span></label>
              <select class="form-select" id="selectType" name="type" required>
                <option value="" selected disabled>-- Pilih Jenis --</option>
                <option value="passenger">Angkutan Orang (Passenger)</option>
                <option value="cargo">Angkutan Barang (Cargo/Material)</option>
              </select>
              <div class="invalid-feedback" id="err-type">Pilih jenis kendaraan.</div>
            </div>

            <!-- Jenis BBM -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputFuelType">Jenis Bahan Bakar (BBM) <span class="text-danger">*</span></label>
              <select class="form-select" id="inputFuelType" name="fuel_type" required>
                <option value="Dexlite" selected>Dexlite</option>
                <option value="Solar">Solar</option>
                <option value="Pertamina Dex">Pertamina Dex</option>
                <option value="Pertalite">Pertalite</option>
                <option value="Pertamax">Pertamax</option>
              </select>
              <div class="invalid-feedback" id="err-fuel_type">Pilih jenis BBM.</div>
            </div>

            <!-- Kepemilikan -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectOwnership">Status Kepemilikan <span class="text-danger">*</span></label>
              <select class="form-select" id="selectOwnership" name="ownership" required>
                <option value="company" selected>Milik Perusahaan</option>
                <option value="rented">Sewa (Perusahaan Persewaan)</option>
              </select>
            </div>

            <!-- Vendor Sewa (Tampil jika kepemilikan = rented) -->
            <div class="col-12 col-md-6" id="wrapperRentalCompany" style="display: none;">
              <label class="form-label fw-semibold" for="selectRentalCompany">Perusahaan Persewaan <span class="text-danger">*</span></label>
              <select class="form-select" id="selectRentalCompany" name="rental_company_id">
                <option value="" selected disabled>-- Pilih Perusahaan Sewa --</option>
                @foreach($rentalCompanies as $rc)
                  <option value="{{ $rc->id }}">{{ $rc->name }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-rental_company_id">Pilih perusahaan sewa.</div>
            </div>

            <!-- Lokasi Pool / Stationed -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectLocation">Lokasi Pool Kendaraan <span class="text-danger">*</span></label>
              <select class="form-select" id="selectLocation" name="location_id" required>
                <option value="" selected disabled>-- Pilih Lokasi Pool --</option>
                @foreach($locations as $loc)
                  <option value="{{ $loc->id }}">{{ $loc->name }} ({{ ucfirst($loc->type) }})</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-location_id">Pilih lokasi pool kendaraan.</div>
            </div>

          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary fw-bold" id="btnSaveVehicle">
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
    const modalEl = document.getElementById('vehicleModal');
    const vehicleModal = new bootstrap.Modal(modalEl);

    const isAdmin = @json(Auth::user()->role?->name === 'admin');

    const vehicleColumns = [
      { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
      {
        data: 'name',
        name: 'name',
        render: function(data, type, row) {
          const plate = row.license_plate ? escapeHtml(row.license_plate) : '-';
          return `
            <strong class="d-block text-dark fs-6">${escapeHtml(data)}</strong>
            <span>${plate}</span>
          `;
        }
      },
      {
        data: 'type',
        name: 'type',
        render: function(data) {
          if (data === 'passenger') {
            return 'Angkutan Orang';
          }
          return 'Angkutan Barang';
        }
      },
      {
        data: 'ownership',
        name: 'ownership',
        render: function(data, type, row) {
          if (data === 'company') {
            return 'Milik Perusahaan';
          }
          const rentalName = row.rental_company ? escapeHtml(row.rental_company.name) : 'Perusahaan Sewa';
          return `
            <span class="badge p-1 text-bg-warning border">Sewa</span>
            <span>${rentalName}</span>
          `;
        }
      },
      {
        data: 'location',
        name: 'location.name',
        render: function(data) {
          return `${escapeHtml(data.name)}`;
        }
      },
      {
        data: 'fuel_type',
        name: 'fuel_type',
        render: function(data) {
          return `${escapeHtml(data)}`;
        }
      },
      {
        data: 'status',
        name: 'status',
        render: function(data) {
          if (data === 'available') {
            return '<span class="badge text-bg-success">Tersedia</span>';
          } else if (data === 'reserved') {
            return '<span class="badge text-bg-warning text-dark">Dipesan (Reserved)</span>';
          } else if (data === 'in_use') {
            return '<span class="badge text-bg-primary">Sedang Digunakan</span>';
          }
          return '<span class="badge text-bg-danger">Dalam Service</span>';
        }
      }
    ];

    if (isAdmin) {
      vehicleColumns.push({
        data: 'id',
        name: 'id',
        orderable: false,
        searchable: false,
        width: '1%',
        className: 'text-center text-nowrap',
        render: function(data, type, row) {
          const nameWithPlate = escapeHtml(row.name + ' (' + (row.license_plate || '') + ')');
          return `
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-primary btn-edit" data-id="${data}" title="Edit Kendaraan">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button type="button" class="btn btn-outline-danger btn-delete" data-id="${data}" data-name="${nameWithPlate}" title="Hapus Kendaraan">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          `;
        }
      });
    }

    // 1. Inisialisasi DataTables Server-Side Processing
    const tableVehicles = $('#tableVehicles').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('vehicles.index') }}",
      columns: vehicleColumns,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ kendaraan",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data kendaraan yang cocok",
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
    function refreshVehicleFormOptions(callback) {
      $.ajax({
        url: "{{ route('vehicles.options') }}",
        type: 'GET',
        dataType: 'json',
        success: function(res) {
          if (res.locations) {
            const locVal = $('#selectLocation').val();
            let locOpts = '<option value="" disabled selected>-- Pilih Lokasi Pool --</option>';
            res.locations.forEach(loc => {
              locOpts += `<option value="${loc.id}">${escapeHtml(loc.name)}</option>`;
            });
            $('#selectLocation').html(locOpts);
            if (locVal) $('#selectLocation').val(locVal);
          }
          if (res.rental_companies) {
            const rentVal = $('#selectRentalCompany').val();
            let rentOpts = '<option value="" disabled selected>-- Pilih Vendor Rental --</option>';
            res.rental_companies.forEach(rc => {
              rentOpts += `<option value="${rc.id}">${escapeHtml(rc.name)}</option>`;
            });
            $('#selectRentalCompany').html(rentOpts);
            if (rentVal) $('#selectRentalCompany').val(rentVal);
          }
          if (callback) callback();
        }
      });
    }

    // Toggle tampilan Vendor Sewa berdasarkan kepemilikan
    function toggleRentalCompany() {
      if ($('#selectOwnership').val() === 'rented') {
        $('#wrapperRentalCompany').slideDown(200);
        $('#selectRentalCompany').prop('required', true);
      } else {
        $('#wrapperRentalCompany').slideUp(200);
        $('#selectRentalCompany').prop('required', false).val('');
      }
    }

    $('#selectOwnership').on('change', toggleRentalCompany);

    // Tombol Tambah Kendaraan
    $('#btnCreateVehicle').on('click', function() {
      $('#formVehicle')[0].reset();
      $('#formVehicle').removeClass('was-validated');
      $('.invalid-feedback').text('');
      $('#modalTitle').text('Tambah Kendaraan Baru');
      $('#formMethod').val('POST');
      $('#formVehicle').attr('action', "{{ route('vehicles.store') }}");
      toggleRentalCompany();
      refreshVehicleFormOptions();
      vehicleModal.show();
    });

    // Submit Form (AJAX)
    $('#formVehicle').on('submit', function(e) {
      e.preventDefault();

      const form = $(this);
      const url = form.attr('action');
      const formData = form.serialize();

      $('#btnSaveVehicle').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...');

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          $('#btnSaveVehicle').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Data');
          if (response.success) {
            vehicleModal.hide();
            tableVehicles.ajax.reload(null, false);

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
          $('#btnSaveVehicle').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Data');
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

    // Tombol Edit Kendaraan (AJAX Fetch Data)
    $(document).on('click', '.btn-edit', function() {
      const vehicleId = $(this).data('id');
      const url = "{{ url('vehicles') }}/" + vehicleId;

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
          $('#formVehicle').removeClass('was-validated');
          $('#modalTitle').text('Edit Data Kendaraan: ' + data.name);
          $('#formMethod').val('PUT');
          $('#formVehicle').attr('action', url);

          $('#inputName').val(data.name);
          $('#inputLicensePlate').val(data.license_plate);
          $('#selectType').val(data.type);
          $('#inputFuelType').val(data.fuel_type);
          $('#selectOwnership').val(data.ownership);
          toggleRentalCompany();

          if (data.ownership === 'rented') {
            $('#selectRentalCompany').val(data.rental_company_id);
          }

          $('#selectLocation').val(data.location_id);

          vehicleModal.show();
        },
        error: function() {
          Swal.fire('Error', 'Gagal mengambil data kendaraan dari server.', 'error');
        }
      });
    });

    // Tombol Hapus Kendaraan (AJAX Delete)
    $(document).on('click', '.btn-delete', function() {
      const vehicleId = $(this).data('id');
      const vehicleName = $(this).data('name');
      const deleteUrl = "{{ url('vehicles') }}/" + vehicleId;

      Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus data kendaraan "' + vehicleName + '"?',
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
                tableVehicles.ajax.reload(null, false);
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
              Swal.fire('Error', 'Gagal menghapus data kendaraan.', 'error');
            }
          });
        }
      });
    });
  });
</script>
@endsection
