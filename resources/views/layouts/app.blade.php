<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="Aplikasi Pemesanan & Pemonitoran Kendaraan Tambang Nikel">
  <title>@yield('title', 'Dashboard') | Fleet Management Nikel</title>

  <!-- Template Base CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

  <!-- Local Vendor CSS (No CDN) -->
  <!-- 1. DataTables CSS -->
  <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">

  <!-- 2. Flatpickr (Datepicker) CSS -->
  <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">

  <!-- 3. Select2 CSS & Bootstrap 5 Theme -->
  <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2-bootstrap-5-theme.min.css') }}">

  <!-- 4. SweetAlert2 CSS -->
  <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">

  @yield('styles')
  @stack('styles')
</head>

<body>
  <div class="admin-shell">
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <!-- SIDEBAR NAV -->
    @include('layouts.sidebar')

    <!-- MAIN WRAPPER -->
    <div class="admin-main">
      <!-- NAVBAR TOPBAR -->
      @include('layouts.navbar')

      <!-- MAIN CONTENT -->
      <main class="dashboard-content">
        <div class="container-fluid px-3 px-lg-4 py-4">
          @yield('content')
        </div>
      </main>

      <!-- FOOTER -->
      @include('layouts.footer')
    </div>
  </div>

  <!-- Local JavaScript Libraries (No CDN) -->
  <!-- 1. jQuery -->
  <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

  <!-- 2. Bootstrap Bundle -->
  <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

  <!-- 3. Template Main JS -->
  <script src="{{ asset('assets/js/main.js') }}"></script>

  <!-- 4. DataTables JS -->
  <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>

  <!-- 5. Flatpickr (Datepicker) -->
  <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
  <script src="{{ asset('vendor/flatpickr/l10n/id.js') }}"></script>

  <!-- 6. Chart.js -->
  <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>

  <!-- 7. SweetAlert2 -->
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

  <!-- 8. Select2 -->
  <script src="{{ asset('vendor/select2/js/select2.min.js') }}"></script>

  <!-- Global Initialization Script -->
  <script>
    $(document).ready(function() {
      // CSRF token setup for AJAX
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      // Initialize DataTables if element exists
      if ($('.datatable').length) {
        $('.datatable').DataTable({
          language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 data",
            zeroRecords: "Tidak ada data yang cocok",
            paginate: {
              first: "Pertama",
              last: "Terakhir",
              next: "Berikutnya",
              previous: "Sebelumnya"
            }
          },
          pageLength: 10,
          responsive: true
        });
      }

      // Initialize Select2 if element exists
      if ($('.select2').length) {
        $('.select2').select2({
          theme: 'bootstrap-5',
          width: '100%'
        });
      }

      // Initialize Flatpickr Datepicker if element exists
      if ($('.datepicker').length) {
        $('.datepicker').flatpickr({
          locale: 'id',
          dateFormat: 'Y-m-d',
          altInput: true,
          altFormat: 'j F Y'
        });
      }
    });
  </script>

  <!-- Flash Notification Popups -->
  @if (session('success'))
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '{{ session('success') }}',
        timer: 3000,
        showConfirmButton: false
      });
    </script>
  @endif

  @if (session('error'))
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Terjadi Kesalahan',
        text: '{{ session('error') }}',
        confirmColor: '#dc3545'
      });
    </script>
  @endif

  @yield('scripts')
  @stack('scripts')
</body>
</html>
