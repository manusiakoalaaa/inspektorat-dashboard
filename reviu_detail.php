<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor', 'pimpinan']);

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, o.nama_opd, j.nama_jenis, t.nama_tim
                        FROM reviu r
                        JOIN opd o ON o.id = r.opd_id
                        JOIN jenis_reviu j ON j.id = r.jenis_reviu_id
                        JOIN tim_reviu t ON t.id = r.tim_reviu_id
                        WHERE r.id = ?");
$stmt->execute([$id]);
$reviu = $stmt->fetch();

if (!$reviu) {
    flash_set('error', 'Data reviu tidak ditemukan.');
    redirect('index.php');
}

// Pastikan status (termasuk Tertunda berdasarkan tanggal target) selalu terkini
recalculate_reviu($pdo, $id);
$stmt->execute([$id]);
$reviu = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM dokumen WHERE reviu_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$dokumen_list = $stmt->fetchAll();

// Kelompokkan dokumen berdasarkan jenis
$dok_by_jenis = [];
foreach ($dokumen_list as $d) {
    $dok_by_jenis[$d['jenis']][] = $d;
}
$terunggah = array_keys($dok_by_jenis);

// Anggota tim reviu
$stmt = $pdo->prepare("SELECT ta.peran, u.nama_lengkap, u.jabatan
                        FROM tim_anggota ta JOIN users u ON u.id = ta.user_id
                        WHERE ta.tim_reviu_id = ?
                        ORDER BY (ta.peran = 'Ketua') DESC, u.nama_lengkap ASC");
$stmt->execute([$reviu['tim_reviu_id']]);
$anggota_tim = $stmt->fetchAll();

$is_editor = in_array(current_role(), ['administrator', 'auditor'], true);
$jenis_list = dokumen_jenis_list();

$page_title = 'Detail Reviu - ' . $reviu['nama_opd'];
$page_subtitle = $reviu['nama_jenis'] . ' Tahun ' . $reviu['tahun'];
include __DIR__ . '/includes/header.php';
?>

<a href="javascript:history.back()" class="small-muted mb-3 d-inline-block" style="font-weight:700; color:var(--blue);"><i class="bi bi-arrow-left"></i> Kembali</a>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card-x mb-3">
      <div class="card-x-title"><i class="bi bi-info-circle-fill"></i> INFORMASI REVIU</div>
      <table class="table-x" style="font-size:13.5px;">
        <tbody>
          <tr><td class="small-muted" style="width:170px;">OPD</td><td><b><?= e($reviu['nama_opd']) ?></b></td></tr>
          <tr><td class="small-muted">Jenis Reviu</td><td><?= e($reviu['nama_jenis']) ?></td></tr>
          <tr><td class="small-muted">Tim Reviu</td><td><?= e($reviu['nama_tim']) ?></td></tr>
          <tr><td class="small-muted">Tahun</td><td><?= e($reviu['tahun']) ?></td></tr>
          <tr><td class="small-muted">Tanggal Mulai</td><td><?= format_tanggal_indo($reviu['tgl_mulai']) ?></td></tr>
          <tr><td class="small-muted">Target Selesai</td><td><?= format_tanggal_indo($reviu['tgl_target_selesai']) ?></td></tr>
          <tr><td class="small-muted">Status Dokumen</td><td><span class="badge-x <?= dokumen_badge_class($reviu['dokumen_status']) ?>"><?= e($reviu['dokumen_status']) ?></span></td></tr>
          <tr><td class="small-muted">Status Reviu</td><td><span class="badge-x <?= status_badge_class($reviu['status']) ?>"><?= e($reviu['status']) ?></span></td></tr>
          <?php if ($reviu['status'] === 'Tertunda' && !empty($reviu['kendala'])): ?>
          <tr><td class="small-muted">Kendala</td><td style="color:var(--red); font-weight:600;"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($reviu['kendala']) ?></td></tr>
          <?php endif; ?>
          <tr><td class="small-muted">Progres</td><td>
            <div class="d-flex align-items-center gap-2">
              <div class="progress-thin" style="width:180px;"><div class="bar" style="width:<?= (int)$reviu['progres'] ?>%; background:<?= progres_bar_color($reviu['progres']) ?>;"></div></div>
              <b><?= (int)$reviu['progres'] ?>%</b>
            </div>
          </td></tr>
          <tr><td class="small-muted">Keterangan</td><td><?= e($reviu['keterangan'] ?: '-') ?></td></tr>
        </tbody>
      </table>
      <div class="small-muted" style="font-size:12px; margin-top:6px;">
        <i class="bi bi-lightbulb"></i> Progres &amp; status diperbarui otomatis mengikuti dokumen yang diunggah.
        Status berubah menjadi <b>Tertunda</b> otomatis apabila tanggal target selesai sudah lewat namun reviu belum tuntas.
      </div>
    </div>

    <div class="card-x mb-3">
      <div class="card-x-title"><i class="bi bi-people-fill"></i> ANGGOTA TIM REVIU</div>
      <?php if (empty($anggota_tim)): ?>
        <div class="empty-state py-3"><i class="bi bi-person-x"></i>Belum ada anggota pada <?= e($reviu['nama_tim']) ?>.</div>
        <?php if (current_role() === 'administrator'): ?>
          <a href="anggota_tim.php?tim=<?= (int)$reviu['tim_reviu_id'] ?>" class="btn-x btn-outline-x"><i class="bi bi-person-plus"></i> Tambah Anggota</a>
        <?php endif; ?>
      <?php else: foreach ($anggota_tim as $a): ?>
        <div class="jadwal-item">
          <i class="bi bi-person-badge" style="color:var(--blue); font-size:18px;"></i>
          <div style="flex:1;">
            <div class="jadwal-title"><?= e($a['nama_lengkap']) ?></div>
            <div class="jadwal-sub"><?= e($a['jabatan'] ?: 'Auditor') ?></div>
          </div>
          <span class="badge-x <?= $a['peran'] === 'Ketua' ? 'badge-proses' : 'badge-belummulai' ?>"><?= e($a['peran']) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card-x mb-3" style="background:#f7f9ff;">
      <div class="card-x-title mb-2"><i class="bi bi-list-ol"></i> ALUR UNGGAH DOKUMEN (BERURUTAN)</div>
      <div class="small-muted" style="font-size:12.5px;">
        Unggah dokumen sesuai urutan. Setiap dokumen menaikkan progres <b>25%</b>:
        SPT &rarr; 25%, Pemeriksaan &rarr; 50%, KKR &rarr; 75%, LHP &rarr; 100%.
      </div>
    </div>

    <?php
    $step = 0;
    foreach ($jenis_list as $key => $cfg):
        $step++;
        $items = $dok_by_jenis[$key] ?? [];
        $sudah = !empty($items);
        $terbuka = dokumen_jenis_terbuka($key, $terunggah);
        // status kartu
        if ($sudah) { $badge = '<span class="badge-x badge-lengkap">Selesai</span>'; }
        elseif ($terbuka) { $badge = '<span class="badge-x badge-proses">Bisa Diunggah</span>'; }
        else { $badge = '<span class="badge-x badge-belummulai">Terkunci</span>'; }
    ?>
    <div class="card-x mb-3" style="<?= (!$sudah && !$terbuka) ? 'opacity:.7;' : '' ?>">
      <div class="d-flex justify-content-between align-items-center" style="margin-bottom:12px;">
        <div class="card-x-title mb-0">
          <i class="bi bi-<?= $sudah ? 'check-circle-fill' : ($terbuka ? 'upload' : 'lock-fill') ?>"></i>
          LANGKAH <?= $step ?> &mdash; <?= e($cfg['label']) ?>
        </div>
        <?= $badge ?>
      </div>

      <?php if ($sudah): ?>
        <?php foreach ($items as $d): ?>
        <div class="jadwal-item">
          <i class="bi bi-file-earmark-text" style="color:var(--blue); font-size:18px;"></i>
          <div style="flex:1;">
            <div class="jadwal-title"><?= e($d['nama_dokumen']) ?></div>
            <div class="jadwal-sub">Diunggah <?= format_tanggal_indo($d['tanggal_upload']) ?></div>
          </div>
          <?php if (!empty($d['file_path'])): ?>
            <a href="uploads/dokumen/<?= e($d['file_path']) ?>" target="_blank" class="btn-eye" title="Lihat file"><i class="bi bi-eye"></i></a>
          <?php endif; ?>
          <?php if ($is_editor): ?>
          <form method="POST" action="process/process_dokumen.php"
                data-confirm="Hapus dokumen ini? Progres reviu akan dihitung ulang secara otomatis." data-confirm-button="Ya, Hapus">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
            <input type="hidden" name="reviu_id" value="<?= (int)$id ?>">
            <button type="submit" class="btn-eye" style="background:var(--red-light); color:var(--red);"><i class="bi bi-trash" style="font-size:12px;"></i></button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($is_editor): ?>
        <?php if ($terbuka): ?>
        <form method="POST" action="process/process_dokumen.php" enctype="multipart/form-data" class="mt-2">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="add">
          <input type="hidden" name="reviu_id" value="<?= (int)$id ?>">
          <input type="hidden" name="jenis" value="<?= e($key) ?>">
          <div class="form-row-x">
            <label>Nama Dokumen (opsional)</label>
            <input type="text" name="nama_dokumen" class="form-control-x" placeholder="<?= e($cfg['label']) ?>">
          </div>
          <div class="form-row-x">
            <label>File Dokumen <span style="color:var(--red);">*</span></label>
            <input type="file" name="file_dokumen" class="form-control-x" required
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
          </div>
          <button type="submit" class="btn-x btn-primary-x w-100">
            <i class="bi bi-upload"></i> Unggah <?= e($cfg['singkat']) ?> <?= $sudah ? '(tambah berkas)' : '&mdash; progres jadi ' . $cfg['progres'] . '%' ?>
          </button>
        </form>
        <?php elseif (!$sudah): ?>
        <div class="small-muted" style="font-size:12.5px;">
          <i class="bi bi-lock"></i> Selesaikan langkah sebelumnya terlebih dahulu untuk membuka unggahan ini.
        </div>
        <?php endif; ?>
      <?php elseif (!$sudah): ?>
        <div class="empty-state py-3"><i class="bi bi-inbox"></i>Belum diunggah.</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
