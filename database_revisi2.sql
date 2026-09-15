-- =========================================================
-- MIGRASI REVISI 2: Status Tertunda otomatis berdasarkan
-- tanggal target selesai + penjelasan kendala
-- Jalankan pada database: inspektorat_reviu
-- =========================================================
USE inspektorat_reviu;

ALTER TABLE reviu
  ADD COLUMN kendala VARCHAR(255) DEFAULT NULL AFTER keterangan;

-- Status & kendala akan otomatis dihitung ulang oleh aplikasi
-- (fungsi refresh_all_reviu_status di includes/functions.php)
-- setiap kali halaman daftar/rekap/detail reviu dibuka, jadi
-- tidak perlu UPDATE manual di sini.

-- =========================================================
-- SELESAI
-- =========================================================
