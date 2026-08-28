# Dashboard Monitoring Progres Reviu OPD
Inspektorat Daerah Kabupaten Labuhanbatu Selatan

Aplikasi web berbasis **PHP native** (kompatibel PHP 7.4 – 8.x, tanpa framework) + **MySQL/MariaDB**,
dibuat mengikuti desain dashboard yang diberikan. Semua data pada dashboard bersifat **dinamis**
(diambil langsung dari database, tidak ada yang di-hardcode).

---

## 1. Fitur Utama

- **Login** berbasis session dengan proteksi CSRF & password hash (bcrypt).
- **3 Role pengguna** dengan menu berbeda:
  | Role | Menu yang aktif |
  |---|---|
  | **Administrator** | Semua menu, termasuk **Manajemen User** |
  | **Auditor** | Semua menu **kecuali** Manajemen User |
  | **Pimpinan** | Hanya **Beranda (Dashboard)** dan **Rekapitulasi** |
- **Dashboard (Beranda)** — 100% dinamis via AJAX:
  - 5 kartu statistik (Total, Selesai, Proses, Tertunda, Rata-rata Progres)
  - Chart progres per OPD, status reviu (donut), progres per jenis reviu
  - Tabel daftar progres reviu + panel Filter, Jadwal Kegiatan, Dokumen Masuk
  - Tombol **Refresh Data** dan filter **Tahun** yang memuat ulang data via AJAX tanpa reload halaman
- **Rekapitulasi** — ringkasan progres per OPD per tahun (bisa diakses semua role)
- **Daftar Reviu** — CRUD lengkap data reviu (tambah/edit/hapus via modal)
- **Progres OPD** — progres dikelompokkan per Perangkat Daerah
- **Reviu Tertunda** — daftar reviu berstatus tertunda + hitungan keterlambatan otomatis
- **Dokumen** — daftar seluruh dokumen pendukung reviu, dengan upload file
- **Laporan** — filter, cetak (print-friendly), dan **export CSV**
- **Pengaturan** — kelola data master: OPD, Jenis Reviu, Tim Reviu
- **Manajemen User** *(khusus Administrator)* — tambah/edit/hapus user & atur role
- **Edit Profil** (menu di bawah sidebar, klik nama Anda) — ganti nama/jabatan, **ganti password**, **ganti foto profil (avatar)**
- **Logout**

---

## 2. Kebutuhan Server

- PHP **7.4** atau lebih baru (diuji juga pada PHP 8.3) dengan ekstensi: `pdo_mysql`, `session`, `fileinfo`
- MySQL 5.7+ atau MariaDB 10+
- Web server: Apache (XAMPP/Laragon/cPanel) atau Nginx, atau `php -S` untuk testing lokal

---

## 3. Cara Cepat: Jalankan & Bagikan via GitHub Codespaces (tanpa hosting)

Cara ini paling cocok kalau Anda hanya ingin **menunjukkan sistem ke orang lain dari jarak jauh**
tanpa menyewa hosting. Semua sudah dikonfigurasi otomatis lewat folder `.devcontainer/`.

1. Buat repository baru di GitHub (boleh **Private**), lalu push/upload seluruh folder ini ke sana
   (termasuk folder `.devcontainer` dan file `database.sql`).
2. Di halaman repo, klik tombol hijau **Code** → tab **Codespaces** → **Create codespace on main**.
3. Tunggu 1–3 menit. Codespace akan otomatis:
   - Menginstall PHP & MariaDB
   - Membuat user database khusus (`inspektorat` / `inspektorat123`) — **bukan** root, supaya lebih aman
   - Meng-import `database.sql`
   - Menyesuaikan `config/database.php` secara otomatis
   - Menjalankan server di port **8080**
4. Buka tab **PORTS** di bagian bawah VS Code. Port `8080` akan otomatis berlabel **Public**
   (kalau belum, klik kanan → **Port Visibility** → **Public**).
5. Klik ikon **globe** di sebelah port tersebut, tambahkan `/login.php` di akhir URL, lalu
   **bagikan link itu ke siapa pun** — mereka bisa langsung membuka dan mencoba sistemnya dari
   browser mana saja, tanpa perlu install apa-apa.
6. Kalau Codespace ditutup/dihentikan, cukup buka lagi dari tab **Codespaces** di GitHub — server
   akan otomatis menyala kembali (lihat `postStartCommand`).

> Akun GitHub gratis mendapat kuota ±60 jam/bulan untuk Codespaces (mesin 2-core) — lebih dari
> cukup untuk demo. Codespace otomatis berhenti setelah 30 menit tanpa aktivitas.

---

## 4. Cara Instalasi Lokal (XAMPP / Laragon)

1. Salin seluruh folder `inspektorat-dashboard` ke folder web server Anda:
   - XAMPP: `C:\xampp\htdocs\inspektorat-dashboard`
   - Laragon: `C:\laragon\www\inspektorat-dashboard`
2. Buat database baru bernama `inspektorat_reviu` melalui phpMyAdmin, **atau** cukup import
   langsung file `database.sql` (file ini sudah berisi perintah `CREATE DATABASE`).
3. Buka phpMyAdmin → tab **Import** → pilih file `database.sql` → klik **Go**.
4. Buka `config/database.php`, sesuaikan jika perlu:
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'inspektorat_reviu';
   $DB_USER = 'root';
   $DB_PASS = '';        // isi sesuai password MySQL Anda
   ```
5. Pastikan folder berikut **dapat ditulis (writable)** oleh web server:
   - `uploads/avatars/`
   - `uploads/dokumen/`
6. Akses melalui browser: `http://localhost/inspektorat-dashboard/login.php`

---

## 5. Akun Default (Segera Ganti Password Setelah Login!)

| Username | Password | Role |
|---|---|---|
| `admin` | `admin123` | Administrator |
| `auditor1` | `auditor123` | Auditor |
| `pimpinan` | `pimpinan123` | Pimpinan |

Ganti password melalui menu **profil (klik nama Anda di pojok kiri bawah) → Edit Profil → Ganti Password**.

---

## 6. Struktur Folder

```
inspektorat-dashboard/
├── .devcontainer/               # Konfigurasi GitHub Codespaces (setup otomatis)
│   ├── devcontainer.json
│   ├── setup.sh
│   └── start.sh
├── api/
│   └── dashboard_data.php     # Endpoint JSON untuk dashboard (AJAX)
├── assets/
│   ├── css/style.css
│   └── js/ (main.js, dashboard.js)
├── config/
│   └── database.php           # Kredensial database
├── includes/
│   ├── auth.php                # Session, role-check, CSRF
│   ├── functions.php           # Helper (format tanggal, badge, dsb)
│   ├── header.php / footer.php / sidebar.php
├── process/                    # Handler form (tambah/edit/hapus)
│   ├── process_reviu.php
│   ├── process_dokumen.php
│   ├── process_master.php
│   ├── process_user.php
│   └── process_profile.php
├── uploads/
│   ├── avatars/                # Foto profil user
│   └── dokumen/                # File dokumen reviu
├── login.php / logout.php
├── index.php                   # Beranda / Dashboard
├── rekapitulasi.php
├── daftar_reviu.php
├── reviu_detail.php
├── progres_opd.php
├── reviu_tertunda.php
├── dokumen.php
├── laporan.php
├── pengaturan.php
├── user_management.php
├── profile.php
└── database.sql                # Skema + data contoh
```

---

## 7. Catatan Teknis

- Semua query database memakai **PDO prepared statements** untuk mencegah SQL Injection.
- Setiap form POST dilindungi **CSRF token**.
- Password disimpan dengan `password_hash()` (bcrypt) — tidak pernah disimpan sebagai plain text.
- Kolom `opd`, `jenis_reviu`, `tim_reviu` bersifat **master data** yang bisa dikelola lewat menu **Pengaturan**;
  data ini akan otomatis muncul pada seluruh dropdown filter/form terkait.
- Data pada bagian **Dashboard** diambil melalui `api/dashboard_data.php` (format JSON) dan dirender oleh
  `assets/js/dashboard.js` menggunakan **Chart.js** — setiap kali filter/tahun diubah atau tombol
  **Refresh Data** ditekan, data ditarik ulang dari database secara real-time.
- Statistik **"Dokumen Masuk (Minggu Ini)"** dihitung otomatis berdasarkan `tanggal_upload` dokumen pada
  minggu berjalan (bukan angka tetap).
- Role & hak akses menu diatur terpusat pada `includes/sidebar.php` (array `$menu_items`) dan
  fungsi `require_role()` di setiap halaman — mudah disesuaikan bila suatu saat perlu menambah role baru.

---

## 8. Pengembangan Lanjutan (Opsional)

- Tambah fitur notifikasi email saat reviu mendekati tenggat waktu.
- Tambah log aktivitas (audit trail) per user.
- Tambah role kustom dinamis (saat ini 3 role bersifat tetap sesuai kebutuhan awal).
