-- =========================================================
-- MIGRASI REVISI: Progres Dinamis, Jenis Dokumen, Anggota Tim
-- Jalankan pada database: inspektorat_reviu
-- =========================================================
USE inspektorat_reviu;

-- ---------------------------------------------------------
-- 1) Tambah kolom jenis dokumen (urutan: SPT -> Pemeriksaan -> KKR -> LHP)
-- ---------------------------------------------------------
ALTER TABLE dokumen
  ADD COLUMN jenis ENUM('SPT','Pemeriksaan','KKR','LHP') NOT NULL DEFAULT 'Pemeriksaan' AFTER reviu_id;

-- Data lama (opsional): anggap dokumen lama sebagai "Pemeriksaan"
UPDATE dokumen SET jenis = 'Pemeriksaan' WHERE jenis IS NULL;

-- ---------------------------------------------------------
-- 2) Tabel anggota tim reviu
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS tim_anggota (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tim_reviu_id INT NOT NULL,
  user_id INT NOT NULL,
  peran ENUM('Ketua','Anggota') NOT NULL DEFAULT 'Anggota',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ta_tim FOREIGN KEY (tim_reviu_id) REFERENCES tim_reviu(id) ON DELETE CASCADE,
  CONSTRAINT fk_ta_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_tim_user (tim_reviu_id, user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3) Sinkronkan progres & status reviu yang sudah ada
--    berdasarkan dokumen yang tersimpan.
--    (progres: 25/50/75/100 sesuai urutan dokumen terunggah)
-- ---------------------------------------------------------
UPDATE reviu r SET progres = (
  SELECT
    CASE
      WHEN SUM(d.jenis = 'LHP') > 0
       AND SUM(d.jenis = 'KKR') > 0
       AND SUM(d.jenis = 'Pemeriksaan') > 0
       AND SUM(d.jenis = 'SPT') > 0 THEN 100
      WHEN SUM(d.jenis = 'KKR') > 0
       AND SUM(d.jenis = 'Pemeriksaan') > 0
       AND SUM(d.jenis = 'SPT') > 0 THEN 75
      WHEN SUM(d.jenis = 'Pemeriksaan') > 0
       AND SUM(d.jenis = 'SPT') > 0 THEN 50
      WHEN SUM(d.jenis = 'SPT') > 0 THEN 25
      ELSE 0
    END
  FROM dokumen d WHERE d.reviu_id = r.id
);

UPDATE reviu SET status = CASE
  WHEN progres >= 100 THEN 'Selesai'
  WHEN progres > 0 THEN 'Proses'
  ELSE 'Belum Mulai'
END;

UPDATE reviu r SET dokumen_status = (
  SELECT CASE WHEN SUM(d.jenis = 'Pemeriksaan') > 0 THEN 'Lengkap' ELSE 'Belum Lengkap' END
  FROM dokumen d WHERE d.reviu_id = r.id
);
UPDATE reviu SET dokumen_status = 'Belum Lengkap' WHERE dokumen_status IS NULL;

-- =========================================================
-- SELESAI
-- =========================================================
