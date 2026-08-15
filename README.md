# Sistem Manajemen Operasional Armada Tambang (Mining Fleet Management System)

Aplikasi berbasis web untuk pengelolaan, penjadwalan, pemantauan konsumsi BBM, riwayat pemeliharaan/servis, dan alur persetujuan berjenjang 2 level (*two-level approval*) kendaraan operasional perusahaan tambang nikel di berbagai lokasi kantor dan tambang.

---

## 1. Spesifikasi Environment & Teknologi

* **PHP Version:** PHP 8.3.4 (PHP >= 8.3)
* **Framework:** Laravel 13.x (Laravel Framework 13.17)
* **Database:** MySQL 8.0.30 (MySQL >= 8.0)
* **Frontend:** Blade Templating, Bootstrap 5.3, Bootstrap Icons, DataTables (Server-side), Chart.js, SweetAlert2, Select2
* **Fitur Tambahan:** Yajra DataTables, Maatwebsite Excel (Ekspor Laporan .xlsx)

---

## 2. Daftar Akun Pengguna (Default Seeded Users)

Semua akun menggunakan password bawaan yang sama.

| Role / Peran | Email (Username) | Password | Level Approval | Hak Akses Utama |
| :--- | :--- | :--- | :---: | :--- |
| **Administrator** | `admin@fleet.com` | `password123` | - | Kelola master data (Armada, Driver, Lokasi, Perusahaan Sewa), input pemesanan kendaraan, input konsumsi BBM, input jadwal servis, ekspor laporan Excel, dan audit trail log aktivitas. |
| **Supervisor** | `spv@fleet.com` | `password123` | **Level 1** | Tinjau dan proses persetujuan pemesanan kendaraan tahap pertama (Level 1), monitoring dashboard operasional. |
| **Manager** | `manager@fleet.com` | `password123` | **Level 2** | Tinjau dan proses persetujuan pemesanan kendaraan tahap kedua (Level 2) setelah disetujui oleh Supervisor, monitoring dashboard operasional. |

---

## 3. Diagram Sistem & Dokumentasi

Berikut adalah tautan berkas diagram arsitektur dan alur kerja sistem:

* **Entity Relationship Diagram (ERD):** [erd.png](erd.png)
* **Activity Diagram:** [activity_diagram.png](activity_diagram.png)
* **Diagram Source File (Draw.io):** [diagram.drawio](diagram.drawio)

---

## 4. Panduan Instalasi & Menjalankan Aplikasi

### Langkah 1: Clone & Masuk ke Direktori Proyek
```bash
git clone https://github.com/mumtazharis/fleet-management.git
cd fleet-management
```

### Langkah 2: Install Dependensi PHP (Composer)
```bash
composer install
```

### Langkah 3: Konfigurasi Environment (`.env`)
Salin berkas `.env.example` menjadi `.env` dan sesuaikan koneksi database MySQL:
```bash
cp .env.example .env
php artisan key:generate
```
Pastikan konfigurasi database pada `.env` sudah benar:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fleet_management
DB_USERNAME=root
DB_PASSWORD=
```

### Langkah 4: Migrasi & Seeder Database
Jalankan migrasi database dan pengisian data master/demo:
```bash
php artisan migrate:fresh --seed
```

### Langkah 5: Menjalankan Server Aplikasi
Jalankan server pengembangan Laravel:
```bash
php artisan serve
```
Akses aplikasi melalui browser di: `http://127.0.0.1:8000`

---

## 5. Panduan Singkat Penggunaan Fitur Aplikasi

1. **Login Multi-Role**:
   * Buka halaman login dan masuk menggunakan akun Admin, Supervisor, atau Manager.
2. **Dashboard Monitoring**:
   * Menampilkan ringkasan armada (Tersedia, Dipesan, Digunakan, Servis), grafik tren pemakaian bulanan (Disetujui, Selesai, Ditolak), grafik kesiapan armada, konsumsi BBM, biaya perawatan, serta widget persetujuan menunggu tindakan.
3. **Pemesanan Kendaraan**:
   * Masuk ke menu **Pemesanan Kendaraan** (Role: Admin).
   * Klik tombol **Buat Pemesanan**, tentukan tanggal & waktu mulai serta selesai perjalanan.
   * Sistem otomatis memfilter dan hanya menampilkan kendaraan & pengemudi yang bebas jadwal pada rentang waktu tersebut.
   * Pilih atasan Penyetuju Level 1 (Supervisor) dan Penyetuju Level 2 (Manager), lalu simpan.
4. **Alur Persetujuan Berjenjang (*2-Level Approval*)**:
   * **Tahap 1**: Supervisor login (`spv@fleet.com`), buka menu **Persetujuan Pemesanan**, lalu klik tombol aksi untuk menyetujui atau menolak pengajuan.
   * **Tahap 2**: Setelah Level 1 disetujui, Manager login (`manager@fleet.com`) untuk memberikan persetujuan final (Level 2).
5. **Penyelesaian Perjalanan (*Complete Trip*)**:
   * Admin dapat menyelesaikan pesanan yang telah disetujui/berjalan dengan menekan tombol **Selesai**, yang otomatis mengembalikan status kendaraan dan driver ke status tersedia (*available*).
6. **Ekspor Laporan Excel**:
   * Pada menu **Pemesanan Kendaraan**, klik tombol **Export Excel** untuk mengunduh laporan rekap pemesanan lengkap dalam format `.xlsx`.
7. **Pencatatan Servis & Pemeliharaan Armada**:
   * Pada menu **Riwayat Service**, Admin dapat menjadwalkan servis berkala dengan rentang waktu mulai dan selesai. Sistem otomatis memastikan jadwal servis tidak bentrok dengan jadwal pemesanan operasional.
8. **Pencatatan Konsumsi BBM**:
   * Pada menu **Konsumsi BBM**, Admin mencatat pengisian bahan bakar, jumlah liter, total biaya, dan tanggal pengisian armada.
9. **Audit Trail (Log Aktivitas)**:
   * Menu khusus Administrator untuk memantau rekam jejak setiap aksi pengguna (pembuatan pemesanan, approval, cancel, servis, dan pengisian BBM).
