@extends('layouts.app')

@section('title', 'Data Master Perusahaan Sewa')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div class="page-heading-copy mb-0">
    <span class="page-icon bg-warning text-dark"><i class="bi bi-building" aria-hidden="true"></i></span>
    <div>
      <p class="eyebrow mb-1 text-warning fw-bold"><i class="bi bi-folder2-open"></i> Master Data</p>
      <h1 class="h3 mb-1">Data Master Perusahaan Sewa</h1>
      <p class="text-muted mb-0">Kelola daftar vendor penyedia persewaan kendaraan untuk armada sewa tambang.</p>
    </div>
  </div>

  <button type="button" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm" id="btnCreateRentalCompany">
    <i class="bi bi-plus-lg me-1"></i> Tambah Perusahaan Sewa
  </button>
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle w-100" id="tableRentalCompanies">
      <thead class="table-light">
        <tr>
          <th class="text-center">No.</th>
          <th>Nama Perusahaan</th>
          <th>Penanggung Jawab (PIC)</th>
          <th>Nomor Telepon</th>
          <th>Alamat Perusahaan</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Data loaded dynamically via DataTables Server-Side -->
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL FORM TAMBAH / EDIT PERUSAHAAN SEWA -->
<div class="modal fade" id="rentalCompanyModal" tabindex="-1" aria-labelledby="rentalCompanyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="rentalCompanyModalLabel">
          <i class="bi bi-building text-warning me-2"></i> <span id="modalTitle">Tambah Perusahaan Sewa Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formRentalCompany" method="POST" action="{{ route('rental-companies.store') }}" class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="modal-body p-4">
          <div class="row g-3">
            <!-- Nama Perusahaan -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputName">Nama Perusahaan Sewa <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="inputName" name="name" placeholder="Contoh: PT Trans Tambang Utama" required>
              <div class="invalid-feedback" id="err-name">Nama perusahaan sewa wajib diisi.</div>
            </div>

            <!-- Penanggung Jawab (PIC) -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputContactPerson">Penanggung Jawab (PIC)</label>
              <input type="text" class="form-control" id="inputContactPerson" name="contact_person" placeholder="Contoh: Hendra Syahputra">
            </div>

            <!-- Nomor Telepon -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputPhone">Nomor Telepon / HP</label>
              <input type="text" class="form-control" id="inputPhone" name="phone" placeholder="Contoh: 082111223344">
            </div>

            <!-- Alamat Perusahaan -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputAddress">Alamat Lengkap Perusahaan</label>
              <textarea class="form-control" id="inputAddress" name="address" rows="3" placeholder="Contoh: Morowali Industrial Park, Blok B-12"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary fw-bold" id="btnSaveRentalCompany">
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
    const modalEl = document.getElementById('rentalCompanyModal');
    const rentalCompanyModal = new bootstrap.Modal(modalEl);

    // 1. Inisialisasi DataTables Server-Side Processing
    const tableRentalCompanies = $('#tableRentalCompanies').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('rental-companies.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
        { data: 'company_name', name: 'name' },
        { data: 'contact', name: 'contact_person' },
        { data: 'phone_formatted', name: 'phone' },
        { data: 'address_formatted', name: 'address' },
        { data: 'action', name: 'action', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' }
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ perusahaan sewa",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data perusahaan sewa yang cocok",
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

    // Tombol Tambah Perusahaan Sewa
    $('#btnCreateRentalCompany').on('click', function() {
      $('#formRentalCompany')[0].reset();
      $('#formRentalCompany').removeClass('was-validated');
      $('.invalid-feedback').text('');
      $('#modalTitle').text('Tambah Perusahaan Sewa Baru');
      $('#formMethod').val('POST');
      $('#formRentalCompany').attr('action', "{{ route('rental-companies.store') }}");
      rentalCompanyModal.show();
    });

    // Submit Form (AJAX)
    $('#formRentalCompany').on('submit', function(e) {
      e.preventDefault();

      const form = $(this);
      const url = form.attr('action');
      const formData = form.serialize();

      $('#btnSaveRentalCompany').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...');

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          $('#btnSaveRentalCompany').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Data');
          if (response.success) {
            rentalCompanyModal.hide();
            tableRentalCompanies.ajax.reload(null, false);

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
          $('#btnSaveRentalCompany').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Data');
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

    // Tombol Edit Perusahaan Sewa (AJAX Fetch Data)
    $(document).on('click', '.btn-edit', function() {
      const companyId = $(this).data('id');
      const url = "{{ url('rental-companies') }}/" + companyId;

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
          $('#formRentalCompany').removeClass('was-validated');
          $('#modalTitle').text('Edit Perusahaan Sewa: ' + data.name);
          $('#formMethod').val('PUT');
          $('#formRentalCompany').attr('action', url);

          $('#inputName').val(data.name);
          $('#inputContactPerson').val(data.contact_person);
          $('#inputPhone').val(data.phone);
          $('#inputAddress').val(data.address);

          rentalCompanyModal.show();
        },
        error: function() {
          Swal.fire('Error', 'Gagal mengambil data perusahaan sewa dari server.', 'error');
        }
      });
    });

    // Tombol Hapus Perusahaan Sewa (AJAX Delete)
    $(document).on('click', '.btn-delete', function() {
      const companyId = $(this).data('id');
      const companyName = $(this).data('name');
      const deleteUrl = "{{ url('rental-companies') }}/" + companyId;

      Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus perusahaan sewa "' + companyName + '"?',
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
                tableRentalCompanies.ajax.reload(null, false);
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
              Swal.fire('Error', 'Gagal menghapus perusahaan sewa.', 'error');
            }
          });
        }
      });
    });
  });
</script>
@endsection
