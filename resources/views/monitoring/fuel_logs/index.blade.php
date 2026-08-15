@extends('layouts.app')

@section('title', 'Konsumsi BBM')

@section('content')
<!-- Page Heading -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <div class="page-heading-copy mb-0">
    <span class="page-icon bg-warning text-dark"><i class="bi bi-fuel-pump-fill" aria-hidden="true"></i></span>
    <div>
      <p class="eyebrow mb-1 text-warning fw-bold"><i class="bi bi-activity"></i> Monitoring Fleet</p>
      <h1 class="h3 mb-1">Pencatatan Konsumsi BBM</h1>
      <p class="text-muted mb-0">Kelola & pantau riwayat pengisian bahan bakar armada tambang, jumlah liter, biaya, dan odometer.</p>
    </div>
  </div>

  @if(Auth::user()->role?->name === 'admin')
  <button type="button" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm" id="btnCreateFuelLog">
    <i class="bi bi-plus-lg me-1"></i> Catat BBM Baru
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
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">TOTAL BIAYA BBM</small>
          <h5 class="mb-0 fw-bold text-dark">Rp {{ number_format($totalCost, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-warning-subtle text-warning-emphasis p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-fuel-pump fs-4"></i>
        </div>
        <div>
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">TOTAL VOLUME BBM</small>
          <h5 class="mb-0 fw-bold text-dark">{{ number_format($totalLiters, 2, ',', '.') }} Liter</h5>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="badge bg-info-subtle text-info p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-receipt fs-4"></i>
        </div>
        <div>
          <small class="text-muted text-uppercase font-monospace fw-bold" style="font-size: 0.725rem;">TOTAL TRANSAKSI</small>
          <h5 class="mb-0 fw-bold text-dark">{{ number_format($totalTransactions, 0, ',', '.') }} Pengisian</h5>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DATA TABLE PANEL (SERVER-SIDE) -->
<div class="panel shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle w-100" id="tableFuelLogs">
      <thead class="table-light">
        <tr>
          <th class="text-center">No.</th>
          <th>Tanggal</th>
          <th>Kendaraan & BBM</th>
          <th>Jumlah Liter</th>
          <th>Biaya (Harga/L & Total)</th>
          <th>Odometer</th>
          <th>Inputor</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Data loaded dynamically via DataTables Server-Side -->
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL FORM INPUT / EDIT BBM -->
<div class="modal fade" id="fuelLogModal" tabindex="-1" aria-labelledby="fuelLogModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="fuelLogModalLabel">
          <i class="bi bi-fuel-pump me-2"></i><span id="modalTitle">Catat Pengisian BBM Baru</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formFuelLog" action="{{ route('fuel-logs.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="modal-body p-4">
          <div class="row g-3">
            <!-- Pilih Kendaraan -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="selectVehicle">Pilih Kendaraan Operasional <span class="text-danger">*</span></label>
              <select class="form-select select2" id="selectVehicle" name="vehicle_id" required>
                <option value="" selected disabled>-- Pilih Kendaraan --</option>
                @foreach($vehicles as $v)
                  <option value="{{ $v->id }}">
                    {{ $v->name }} (Plat: {{ $v->license_plate }} | BBM: {{ $v->fuel_type ?? '-' }})
                  </option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err-vehicle_id">Pilih kendaraan operasional.</div>
            </div>

            <!-- Tanggal Pengisian -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold" for="inputDate">Tanggal Pengisian <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="inputDate" name="date" value="{{ date('Y-m-d') }}" required>
              <div class="invalid-feedback" id="err-date">Tanggal pengisian wajib diisi.</div>
            </div>

            <!-- Jumlah Liter BBM -->
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold" for="inputFuelAmount">Jumlah BBM (Liter) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="inputFuelAmount" name="fuel_amount" placeholder="Contoh: 45.5" required>
              <div class="invalid-feedback" id="err-fuel_amount">Jumlah liter wajib diisi.</div>
            </div>

            <!-- Harga per Liter -->
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold" for="inputFuelPrice">Harga per Liter (Rp) <span class="text-danger">*</span></label>
              <input type="number" step="1" min="0" class="form-control" id="inputFuelPrice" name="fuel_price" placeholder="Contoh: 14500" required>
              <div class="invalid-feedback" id="err-fuel_price">Harga per liter wajib diisi.</div>
            </div>

            <!-- Total Biaya (Otomatis Hitung) -->
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold" for="inputTotalCost">Total Biaya (Rp)</label>
              <input type="number" step="1" min="0" class="form-control bg-light" id="inputTotalCost" name="total_cost" placeholder="Otomatis terhitung">
              <div class="form-text text-muted" style="font-size: 0.75rem;">Terhitung otomatis dari Liter &times; Harga.</div>
            </div>

            <!-- Odometer KM -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="inputOdometer">Angka Odometer / KM Kendaraan Saat Pengisian <span class="text-danger">*</span></label>
              <input type="number" step="1" min="0" class="form-control" id="inputOdometer" name="odometer_reading" placeholder="Contoh: 45210" required>
              <div class="invalid-feedback" id="err-odometer_reading">Angka odometer (KM) saat pengisian BBM wajib diisi.</div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary fw-bold" id="btnSaveFuelLog">
            <i class="bi bi-save me-1"></i> Simpan Pencatatan BBM
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL DETAIL KONSUMSI BBM -->
<div class="modal fade" id="detailFuelLogModal" tabindex="-1" aria-labelledby="detailFuelLogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="detailFuelLogModalLabel">
          <i class="bi bi-info-circle me-2"></i>Rincian Pengisian BBM
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="detailFuelLogContent">
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

@push('styles')
<style>
  #tableFuelLogs th:first-child,
  #tableFuelLogs td:first-child {
    width: 40px !important;
    max-width: 40px !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
    text-align: center;
  }
  #tableFuelLogs th:first-child::before,
  #tableFuelLogs th:first-child::after {
    display: none !important;
  }
</style>
@endpush

@section('scripts')
<script>
  $(document).ready(function() {
    const fuelLogModalEl = document.getElementById('fuelLogModal');
    const fuelLogModal = new bootstrap.Modal(fuelLogModalEl);
    const detailModalEl = document.getElementById('detailFuelLogModal');
    const detailModal = new bootstrap.Modal(detailModalEl);

    let editFuelLogId = null;

    // 1. DataTables Server-Side Processing
    const tableFuelLogs = $('#tableFuelLogs').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('fuel-logs.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' },
        { data: 'formatted_date', name: 'date' },
        { data: 'vehicle_info', name: 'vehicle.name' },
        { data: 'fuel_amount_formatted', name: 'fuel_amount' },
        { data: 'cost_info', name: 'total_cost' },
        { data: 'odometer_formatted', name: 'odometer_reading' },
        { data: 'creator_name', name: 'creator.name' },
        { data: 'action', name: 'action', orderable: false, searchable: false, width: '1%', className: 'text-center text-nowrap' }
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ pencatatan",
        infoEmpty: "Menampilkan 0 data",
        zeroRecords: "Tidak ada data pencatatan BBM yang cocok",
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

    // Auto compute total cost on input
    function calculateTotalCost() {
      const amount = parseFloat($('#inputFuelAmount').val()) || 0;
      const price = parseFloat($('#inputFuelPrice').val()) || 0;
      if (amount > 0 && price > 0) {
        $('#inputTotalCost').val(Math.round(amount * price));
      }
    }

    $('#inputFuelAmount, #inputFuelPrice').on('input keyup change', calculateTotalCost);

    // Tombol Tambah BBM
    $('#btnCreateFuelLog').on('click', function() {
      editFuelLogId = null;
      $('#modalTitle').text('Catat Pengisian BBM Baru');
      $('#formFuelLog')[0].reset();
      $('#formMethod').val('POST');
      $('#formFuelLog').attr('action', "{{ route('fuel-logs.store') }}");
      $('#formFuelLog').removeClass('was-validated');
      $('.invalid-feedback').text('');
      $('#inputDate').val(new Date().toISOString().split('T')[0]);
      $('.select2').val('').trigger('change');
      fuelLogModal.show();
    });

    // Submit Form (AJAX Store / Update)
    $('#formFuelLog').on('submit', function(e) {
      e.preventDefault();

      const form = $(this);
      const url = form.attr('action');
      const formData = form.serialize();

      $('#btnSaveFuelLog').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...');
      $('.invalid-feedback').hide();

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          $('#btnSaveFuelLog').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Pencatatan BBM');
          if (response.success) {
            fuelLogModal.hide();
            tableFuelLogs.ajax.reload(null, false);

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
          $('#btnSaveFuelLog').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Pencatatan BBM');
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
      editFuelLogId = id;
      const url = "{{ url('fuel-logs') }}/" + id;

      $('#modalTitle').text('Edit Pencatatan Konsumsi BBM');
      $('#formMethod').val('PUT');
      $('#formFuelLog').attr('action', url);
      $('#formFuelLog').removeClass('was-validated');
      $('.invalid-feedback').text('');

      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          $('#selectVehicle').val(data.vehicle_id).trigger('change');
          $('#inputDate').val(data.date ? data.date.split('T')[0] : '');
          $('#inputFuelAmount').val(data.fuel_amount);
          $('#inputFuelPrice').val(data.fuel_price);
          $('#inputTotalCost').val(data.total_cost);
          $('#inputOdometer').val(data.odometer_reading || '');

          fuelLogModal.show();
        },
        error: function() {
          Swal.fire('Error', 'Gagal memuat data pencatatan BBM.', 'error');
        }
      });
    });

    // Helper format datetime / date
    function formatDateStr(dateStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}/${month}/${year}`;
    }

    function formatRupiah(num) {
      return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
    }

    // Tombol Detail
    $(document).on('click', '.btn-detail', function() {
      const id = $(this).data('id');
      const url = "{{ url('fuel-logs') }}/" + id;

      $('#detailFuelLogContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
      detailModal.show();

      $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          const vehicleName = data.vehicle ? `${data.vehicle.name} (${data.vehicle.license_plate})` : 'Kendaraan Terhapus';
          const vehiclePool = data.vehicle && data.vehicle.location ? data.vehicle.location.name : '-';
          const fuelType = data.vehicle && data.vehicle.fuel_type ? data.vehicle.fuel_type : '-';
          const creatorName = data.creator ? data.creator.name : 'Sistem';

          const html = `
            <div class="row g-3">
              <div class="col-12">
                <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-1">Informasi Transaksi & Armada</small>
              </div>
              <div class="col-md-6 mt-1">
                <div class="p-3 rounded-3 h-100 border">
                  <div class="mb-2"><strong>Tanggal Pengisian:</strong> <span class="text-primary fw-bold">${formatDateStr(data.date)}</span></div>
                  <div class="mb-2"><strong>Jumlah BBM:</strong> <span class="fw-bold text-dark">${Number(data.fuel_amount).toLocaleString('id-ID')} Liter</span></div>
                  <div class="mb-2"><strong>Harga / Liter:</strong> ${formatRupiah(data.fuel_price)}</div>
                  <div class="mb-2"><strong>Total Biaya:</strong> <span class="text-success fw-bold fs-6">${formatRupiah(data.total_cost)}</span></div>
                </div>
              </div>
              <div class="col-md-6 mt-1">
                <div class="p-3 rounded-3 h-100 border">
                  <div class="mb-2"><strong>Kendaraan:</strong> ${vehicleName}</div>
                  <div class="mb-2"><strong>Jenis BBM:</strong> ${fuelType}</div>
                  <div class="mb-2"><strong>Lokasi Pool:</strong> ${vehiclePool}</div>
                  <div class="mb-2"><strong>Odometer:</strong> ${data.odometer_reading ? Number(data.odometer_reading).toLocaleString('id-ID') + ' KM' : '-'}</div>
                </div>
              </div>
              <div class="col-12 mt-3">
                <small class="text-uppercase font-monospace text-muted fw-bold d-block mb-2">Petugas Input</small>
                <div class="rounded-3 border p-3 bg-light text-dark">
                  <i class="bi bi-person-circle me-1 text-primary"></i> Documented by <strong>${creatorName}</strong>
                </div>
              </div>
            </div>
          `;

          $('#detailFuelLogContent').html(html);
        },
        error: function() {
          $('#detailFuelLogContent').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Gagal memuat rincian pencatatan BBM.</div>');
        }
      });
    });

    // Tombol Hapus
    $(document).on('click', '.btn-delete', function() {
      const id = $(this).data('id');
      const vehicleName = $(this).data('vehicle');
      const deleteUrl = "{{ url('fuel-logs') }}/" + id;

      Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus pencatatan BBM kendaraan "' + vehicleName + '"?',
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
                tableFuelLogs.ajax.reload(null, false);
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
              Swal.fire('Error', 'Gagal menghapus pencatatan BBM.', 'error');
            }
          });
        }
      });
    });
  });
</script>
@endsection
