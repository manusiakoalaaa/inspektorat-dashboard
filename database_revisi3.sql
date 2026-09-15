-- =========================================================
-- MIGRASI REVISI 3: Kendala otomatis + manual
-- Jalankan pada database: inspektorat_reviu
-- =========================================================
USE inspektorat_reviu;

ALTER TABLE reviu
  ADD COLUMN kendala_manual TINYINT(1) NOT NULL DEFAULT 0 AFTER kendala;

-- kendala_manual = 0 -> teks kendala dihitung otomatis oleh aplikasi
--                       (mengikuti dokumen yang belum diunggah & tanggal target)
-- kendala_manual = 1 -> teks kendala diisi manual oleh admin/auditor lewat
--                       form Tambah/Edit Reviu, dan tidak akan ditimpa oleh
--                       proses hitung ulang otomatis (recalculate_reviu)
--                       sampai diubah kembali ke mode otomatis.

-- =========================================================
-- SELESAI
-- =========================================================
