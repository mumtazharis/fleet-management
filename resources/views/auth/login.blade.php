<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Monitoring & Pemesanan Kendaraan Tambang Nikel">
  <title>Login | Fleet Management Tambang Nikel</title>

  <!-- Template Base CSS Assets -->
  <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

  <!-- Local SweetAlert2 CSS -->
  <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
  
  <style>
    .auth-card {
      max-width: 440px;
    }
    .brand-mining-badge {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #38bdf8;
      border: 1px solid #334155;
    }
  </style>
</head>

<body class="auth-body">
  {{-- <button class="icon-button theme-toggle auth-theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme" title="Switch color theme">
    <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
  </button> --}}

  <main class="auth-page">
    <section class="auth-card shadow-lg rounded-4 p-4 p-md-5">
      <a class="auth-brand mb-3" href="{{ url('/') }}">
        <span class="brand-icon bg-warning text-dark"><i class="bi bi-truck-front-fill" aria-hidden="true"></i></span>
        <span>
          <strong class="fs-4">NICKEL FLEET</strong>
          <small class="text-muted d-block">Sistem Monitoring & Pemesanan Kendaraan</small>
        </span>
      </a>

      <!-- Notification Alerts -->
      @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $errors->first() }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      <form action="{{ route('login') }}" method="POST" class="needs-validation" novalidate>
        @csrf

        <div class="mb-4">
          <h1 class="h3 mb-1">Masuk ke Sistem</h1>
          <p class="text-muted mb-0">Silakan masukkan email dan password akun Anda.</p>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold" for="loginEmail">Alamat Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input class="form-control @error('email') is-invalid @enderror" id="loginEmail" name="email" type="email" value="{{ old('email') }}" placeholder="contoh: admin@fleet.com" required autofocus>
          </div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <label class="form-label fw-semibold" for="loginPassword">Password</label>
          </div>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-key"></i></span>
            <input class="form-control @error('password') is-invalid @enderror" id="loginPassword" name="password" type="password" placeholder="Masukkan password" required>
          </div>
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" {{ old('remember') ? 'checked' : '' }}>
          <label class="form-check-label text-muted" for="rememberMe">Ingat Saya</label>
        </div>

        <button class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm" type="submit">
          <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Sekarang
        </button>
      </form>
      
      <div class="auth-footer mt-4 pt-3 border-top text-center text-muted small">
        <i class="bi bi-info-circle me-1"></i> Demo Akun Testing:
        <div class="mt-1 d-flex gap-1">
          <span class="badge text-dark">admin@fleet.com</span>
          <span class="badge text-dark">spv@fleet.com</span>
          <span class="badge text-dark">manager@fleet.com</span>
        </div>
        <small class="d-block mt-1 text-muted">Password: <code>password123</code></small>
      </div>
    </section>
  </main>

  <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

  @if (session('error'))
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: '{{ session('error') }}',
        confirmColor: '#0d6efd'
      });
    </script>
  @endif
</body>
</html>
