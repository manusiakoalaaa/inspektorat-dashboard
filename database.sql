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
INSERT INTO users (username, password, nama_lengkap, jabatan, role_id, status) VALUES
('admin',    '$2y$10$tcdxQRY5g8NB6LvNVcwXaeXZgKU3gP21KqYWZB/2afgTYbTNi2.g.', 'Administrator Sistem', 'Admin Aplikasi', 1, 'aktif'),
('auditor1', '$2y$10$w66.pT9q1RCBbSXyfBNinuxZxytEXcxf9L1MSff.22BH5Fv47L.ru', 'Budi Santoso',          'PPUPD Ahli Pertama', 2, 'aktif'),
('pimpinan', '$2y$10$b6NkqsaXHAb2NIulKXjGpuAvTKXFndMeXK6X0a35rDm1oO2Ekw8Bm', 'Inspektur Kabupaten',   'Inspektur', 3, 'aktif');

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
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviu_opd FOREIGN KEY (opd_id) REFERENCES opd(id),
  CONSTRAINT fk_reviu_jenis FOREIGN KEY (jenis_reviu_id) REFERENCES jenis_reviu(id),
  CONSTRAINT fk_reviu_tim FOREIGN KEY (tim_reviu_id) REFERENCES tim_reviu(id)
) ENGINE=InnoDB;

INSERT INTO reviu (opd_id, jenis_reviu_id, tim_reviu_id, tahun, tgl_mulai, tgl_target_selesai, dokumen_status, status, progres, keterangan) VALUES
(1, 1, 1, 2025, '2025-05-01', '2025-05-10', 'Lengkap',       'Proses',  80, 'Menunggu klarifikasi'),
(2, 1, 2, 2025, '2025-05-02', '2025-05-12', 'Belum Lengkap', 'Tertunda',60, 'Dokumen belum lengkap'),
(3, 2, 1, 2025, '2025-04-28', '2025-05-08', 'Lengkap',       'Selesai', 100,'Selesai reviu'),
(4, 1, 3, 2025, '2025-05-03', '2025-05-13', 'Belum Lengkap', 'Proses',  45, 'Menunggu dokumen'),
(5, 2, 2, 2025, '2025-04-29', '2025-05-09', 'Lengkap',       'Proses',  70, 'Klarifikasi sedang berjalan'),
(6, 3, 3, 2025, '2025-04-30', '2025-05-11', 'Lengkap',       'Proses',  55, 'Menunggu klarifikasi'),
(7, 2, 1, 2025, '2025-04-27', '2025-05-07', 'Lengkap',       'Selesai', 90, 'Selesai, menunggu paraf'),
(8, 1, 2, 2025, '2025-05-04', '2025-05-14', 'Belum Lengkap', 'Tertunda',30, 'Dokumen terlambat');

-- ============ DOKUMEN ============
CREATE TABLE dokumen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reviu_id INT NOT NULL,
  nama_dokumen VARCHAR(150) NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  status ENUM('Lengkap','Belum Lengkap') NOT NULL DEFAULT 'Belum Lengkap',
  tanggal_upload DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dokumen_reviu FOREIGN KEY (reviu_id) REFERENCES reviu(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO dokumen (reviu_id, nama_dokumen, status, tanggal_upload) VALUES
(1, 'KKR Reviu RKA Dinas PUPR', 'Lengkap', '2025-05-05'),
(3, 'Berita Acara Reviu LKPD Dinas Pendidikan', 'Lengkap', '2025-05-06'),
(7, 'Laporan Hasil Reviu LKPD Badan Keuangan', 'Lengkap', '2025-05-04'),
(2, 'Data Pendukung Reviu RKA Dinas Kesehatan', 'Belum Lengkap', '2025-05-07');

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
('Reviu RKA', 1, '2025-05-10', 'biru'),
('Reviu LKPD', 2, '2025-05-12', 'kuning'),
('Rapat Klarifikasi', 4, '2025-05-10', 'hijau');

-- =========================================================
-- SELESAI. Setelah import, akses login.php dengan salah satu
-- akun di atas lalu segera ganti password melalui menu
-- "Edit Profil" pada sidebar.
-- =========================================================
