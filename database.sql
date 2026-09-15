-- =========================================================
-- Database: inspektorat_reviu
-- Sistem Dashboard Monitoring Progres Reviu OPD
-- Inspektorat Daerah Kabupaten Labuhanbatu Selatan
-- =========================================================

CREATE DATABASE IF NOT EXISTS inspektorat_reviu DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE inspektorat_reviu;

-- ============ ROLES ============
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_role VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (nama_role, slug) VALUES
('Administrator', 'administrator'),
('Auditor', 'auditor'),
('Pimpinan', 'pimpinan');

-- ============ USERS ============
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  jabatan VARCHAR(100) DEFAULT NULL,
  role_id INT NOT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- Password default (SILAKAN SEGERA DIGANTI setelah login pertama kali):
--   admin    / admin123     -> Administrator
--   auditor1 / auditor123   -> Auditor
--   pimpinan / pimpinan123  -> Pimpinan
-- Password default auditor2/auditor3: auditor123
INSERT INTO users (username, password, nama_lengkap, jabatan, role_id, status) VALUES
('admin',    '$2y$10$tcdxQRY5g8NB6LvNVcwXaeXZgKU3gP21KqYWZB/2afgTYbTNi2.g.', 'Administrator Sistem', 'Admin Aplikasi', 1, 'aktif'),
('auditor1', '$2y$10$w66.pT9q1RCBbSXyfBNinuxZxytEXcxf9L1MSff.22BH5Fv47L.ru', 'Budi Santoso',          'PPUPD Ahli Pertama', 2, 'aktif'),
('pimpinan', '$2y$10$b6NkqsaXHAb2NIulKXjGpuAvTKXFndMeXK6X0a35rDm1oO2Ekw8Bm', 'Inspektur Kabupaten',   'Inspektur', 3, 'aktif'),
('auditor2', '$2y$10$s/Ju/1.1NjyuXJJbUx8n3uoMoAd8ruoCv1/uI/YJ6Thz4OdgszV2O', 'Siti Rahma',            'PPUPD Ahli Muda', 2, 'aktif'),
('auditor3', '$2y$10$s/Ju/1.1NjyuXJJbUx8n3uoMoAd8ruoCv1/uI/YJ6Thz4OdgszV2O', 'Andi Wijaya',           'PPUPD Ahli Pertama', 2, 'aktif');

-- ============ MASTER: OPD ============
CREATE TABLE opd (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_opd VARCHAR(150) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO opd (nama_opd) VALUES
('Dinas PUPR'), ('Dinas Kesehatan'), ('Dinas Pendidikan'), ('Dinas Perhubungan'),
('Dinas Sosial'), ('Dinas Kominfo'), ('Badan Keuangan'), ('Kecamatan Kotapinang');

-- ============ MASTER: JENIS REVIU ============
CREATE TABLE jenis_reviu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_jenis VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO jenis_reviu (nama_jenis) VALUES
('Reviu RKA'), ('Reviu LKPD'), ('Reviu LPPD'), ('Reviu Lainnya');

-- ============ MASTER: TIM REVIU ============
CREATE TABLE tim_reviu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_tim VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

INSERT INTO tim_reviu (nama_tim) VALUES ('Tim 1'), ('Tim 2'), ('Tim 3');

-- ============ REVIU ============
CREATE TABLE reviu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  opd_id INT NOT NULL,
  jenis_reviu_id INT NOT NULL,
  tim_reviu_id INT NOT NULL,
  tahun YEAR NOT NULL,
  tgl_mulai DATE NOT NULL,
  tgl_target_selesai DATE NOT NULL,
  dokumen_status ENUM('Lengkap','Belum Lengkap') NOT NULL DEFAULT 'Belum Lengkap',
  status ENUM('Belum Mulai','Proses','Selesai','Tertunda') NOT NULL DEFAULT 'Belum Mulai',
  progres TINYINT UNSIGNED NOT NULL DEFAULT 0,
  keterangan VARCHAR(255) DEFAULT NULL,
  kendala VARCHAR(255) DEFAULT NULL,
  kendala_manual TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviu_opd FOREIGN KEY (opd_id) REFERENCES opd(id),
  CONSTRAINT fk_reviu_jenis FOREIGN KEY (jenis_reviu_id) REFERENCES jenis_reviu(id),
  CONSTRAINT fk_reviu_tim FOREIGN KEY (tim_reviu_id) REFERENCES tim_reviu(id)
) ENGINE=InnoDB;

-- Data contoh tahun 2026 yang sengaja dibuat variatif supaya seluruh fungsi
-- (status otomatis Selesai/Proses/Tertunda/Belum Mulai, level peringatan dini
-- aman/peringatan/kritis, serta kendala otomatis & manual) bisa langsung
-- didemokan tanpa perlu input manual lebih dulu.
INSERT INTO reviu (opd_id, jenis_reviu_id, tim_reviu_id, tahun, tgl_mulai, tgl_target_selesai, dokumen_status, status, progres, keterangan, kendala, kendala_manual) VALUES
(1, 1, 1, 2026, '2026-01-05', '2026-01-20', 'Lengkap',       'Selesai',    100, 'Reviu RKA selesai tepat waktu', NULL, 0),
(2, 2, 2, 2026, '2026-02-01', '2026-02-20', 'Lengkap',       'Selesai',    100, 'Selesai, sempat melewati target beberapa hari', NULL, 0),
(3, 3, 3, 2026, '2026-03-01', '2026-03-25', 'Belum Lengkap', 'Tertunda',   25,  'Menunggu dokumen pemeriksaan dari OPD', 'Melewati tanggal target selesai (25 Maret 2026) — Dokumen Pemeriksaan belum diunggah.', 0),
(4, 1, 1, 2026, '2026-04-01', '2026-04-20', 'Belum Lengkap', 'Tertunda',   0,   'OPD belum merespons permintaan dokumen', 'Melewati tanggal target selesai (20 April 2026) — SPT (Surat Perintah Tugas) belum diunggah.', 0),
(5, 2, 2, 2026, '2026-05-01', '2026-05-25', 'Lengkap',       'Tertunda',   75,  'Menunggu tanda tangan LHP', 'Tim reviu masih menunggu tanda tangan Inspektur untuk LHP, ditargetkan selesai minggu depan.', 1),
(6, 4, 3, 2026, '2026-08-20', '2026-10-05', 'Lengkap',       'Proses',     50,  'Verifikasi anggaran bersama OPD Kominfo', 'Data dari OPD Kominfo belum lengkap untuk verifikasi anggaran, namun jadwal reviu masih sesuai target.', 1),
(7, 2, 1, 2026, '2026-09-01', '2026-09-17', 'Lengkap',       'Proses',     75,  'LHP dalam tahap finalisasi', NULL, 0),
(8, 3, 2, 2026, '2026-09-05', '2026-09-20', 'Belum Lengkap', 'Proses',     25,  'Menunggu Dokumen Pemeriksaan dari OPD', NULL, 0),
(1, 3, 3, 2026, '2026-09-10', '2026-11-30', 'Belum Lengkap', 'Belum Mulai',0,   'Menunggu jadwal turun tim', NULL, 0),
(2, 4, 1, 2026, '2026-09-14', '2026-09-16', 'Belum Lengkap', 'Belum Mulai',0,   'Segera perlu tim turun, jadwal sangat mepet', NULL, 0),
(3, 1, 2, 2026, '2026-07-01', '2026-07-20', 'Lengkap',       'Selesai',    100, 'Selesai reviu RKA', NULL, 0),
(4, 2, 3, 2026, '2026-08-01', '2026-08-25', 'Belum Lengkap', 'Tertunda',   25,  'Menunggu Dokumen Pemeriksaan dari OPD', 'Melewati tanggal target selesai (25 Agustus 2026) — Dokumen Pemeriksaan belum diunggah.', 0);
-- Catatan: status/progres/kendala di atas sudah senilai hasil perhitungan
-- otomatis (recalculate_reviu). Nilai ini akan tetap dihitung ulang otomatis
-- setiap halaman reviu dibuka mengikuti tanggal berjalan & dokumen di bawah,
-- kecuali baris dengan kendala_manual = 1 (kendalanya dikunci manual).

-- ============ DOKUMEN ============
CREATE TABLE dokumen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reviu_id INT NOT NULL,
  jenis ENUM('SPT','Pemeriksaan','KKR','LHP') NOT NULL DEFAULT 'Pemeriksaan',
  nama_dokumen VARCHAR(150) NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  status ENUM('Lengkap','Belum Lengkap') NOT NULL DEFAULT 'Belum Lengkap',
  tanggal_upload DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dokumen_reviu FOREIGN KEY (reviu_id) REFERENCES reviu(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO dokumen (reviu_id, jenis, nama_dokumen, status, tanggal_upload) VALUES
(1,  'SPT',         'SPT (Surat Perintah Tugas) Dinas PUPR', 'Lengkap', '2026-01-08'),
(1,  'Pemeriksaan', 'Dokumen Pemeriksaan Dinas PUPR', 'Lengkap', '2026-01-12'),
(1,  'KKR',         'KKR (Kertas Kerja Reviu) Dinas PUPR', 'Lengkap', '2026-01-16'),
(1,  'LHP',         'LHP (Laporan Hasil Pemeriksaan) Dinas PUPR', 'Lengkap', '2026-01-19'),
(2,  'SPT',         'SPT (Surat Perintah Tugas) Dinas Kesehatan', 'Lengkap', '2026-02-03'),
(2,  'Pemeriksaan', 'Dokumen Pemeriksaan Dinas Kesehatan', 'Lengkap', '2026-02-10'),
(2,  'KKR',         'KKR (Kertas Kerja Reviu) Dinas Kesehatan', 'Lengkap', '2026-02-18'),
(2,  'LHP',         'LHP (Laporan Hasil Pemeriksaan) Dinas Kesehatan', 'Lengkap', '2026-02-25'),
(3,  'SPT',         'SPT (Surat Perintah Tugas) Dinas Pendidikan', 'Lengkap', '2026-03-05'),
(5,  'SPT',         'SPT (Surat Perintah Tugas) Dinas Sosial', 'Lengkap', '2026-05-04'),
(5,  'Pemeriksaan', 'Dokumen Pemeriksaan Dinas Sosial', 'Lengkap', '2026-05-12'),
(5,  'KKR',         'KKR (Kertas Kerja Reviu) Dinas Sosial', 'Lengkap', '2026-05-20'),
(6,  'SPT',         'SPT (Surat Perintah Tugas) Dinas Kominfo', 'Lengkap', '2026-08-22'),
(6,  'Pemeriksaan', 'Dokumen Pemeriksaan Dinas Kominfo', 'Lengkap', '2026-09-15'),
(7,  'SPT',         'SPT (Surat Perintah Tugas) Badan Keuangan', 'Lengkap', '2026-09-02'),
(7,  'Pemeriksaan', 'Dokumen Pemeriksaan Badan Keuangan', 'Lengkap', '2026-09-08'),
(7,  'KKR',         'KKR (Kertas Kerja Reviu) Badan Keuangan', 'Lengkap', '2026-09-14'),
(8,  'SPT',         'SPT (Surat Perintah Tugas) Kecamatan Kotapinang', 'Lengkap', '2026-09-15'),
(11, 'SPT',         'SPT (Surat Perintah Tugas) Dinas Pendidikan', 'Lengkap', '2026-07-03'),
(11, 'Pemeriksaan', 'Dokumen Pemeriksaan Dinas Pendidikan', 'Lengkap', '2026-07-08'),
(11, 'KKR',         'KKR (Kertas Kerja Reviu) Dinas Pendidikan', 'Lengkap', '2026-07-14'),
(11, 'LHP',         'LHP (Laporan Hasil Pemeriksaan) Dinas Pendidikan', 'Lengkap', '2026-07-19'),
(12, 'SPT',         'SPT (Surat Perintah Tugas) Dinas Perhubungan', 'Lengkap', '2026-08-04');

-- ============ ANGGOTA TIM REVIU ============
CREATE TABLE tim_anggota (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tim_reviu_id INT NOT NULL,
  user_id INT NOT NULL,
  peran ENUM('Ketua','Anggota') NOT NULL DEFAULT 'Anggota',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ta_tim FOREIGN KEY (tim_reviu_id) REFERENCES tim_reviu(id) ON DELETE CASCADE,
  CONSTRAINT fk_ta_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_tim_user (tim_reviu_id, user_id)
) ENGINE=InnoDB;

INSERT INTO tim_anggota (tim_reviu_id, user_id, peran) VALUES
(1, 2, 'Ketua'),   -- auditor1 (Budi Santoso)
(1, 5, 'Anggota'), -- auditor3 (Andi Wijaya)
(2, 4, 'Ketua'),   -- auditor2 (Siti Rahma)
(2, 2, 'Anggota'), -- auditor1
(3, 5, 'Ketua'),   -- auditor3
(3, 4, 'Anggota'); -- auditor2

-- ============ JADWAL KEGIATAN ============
CREATE TABLE jadwal_kegiatan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  opd_id INT DEFAULT NULL,
  tanggal DATE NOT NULL,
  warna ENUM('biru','kuning','hijau','merah') NOT NULL DEFAULT 'biru',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_jadwal_opd FOREIGN KEY (opd_id) REFERENCES opd(id)
) ENGINE=InnoDB;

INSERT INTO jadwal_kegiatan (judul, opd_id, tanggal, warna) VALUES
('Turun Tim Reviu Lainnya Dinas Kesehatan', 2, '2026-09-16', 'kuning'),
('Klarifikasi LHP Badan Keuangan', 7, '2026-09-17', 'merah'),
('Rapat Pembukaan Reviu LPPD Kec. Kotapinang', 8, '2026-09-18', 'biru'),
('Rapat Evaluasi Reviu RKA Dinas PUPR', 1, '2026-09-25', 'hijau'),
('Sosialisasi Reviu LPPD Dinas Pendidikan', 3, '2026-10-02', 'biru');

-- =========================================================
-- SELESAI. Setelah import, akses login.php dengan salah satu
-- akun di atas lalu segera ganti password melalui menu
-- "Edit Profil" pada sidebar.
-- =========================================================
