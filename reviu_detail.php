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

$stmt = $pdo->prepare("SELECT * FROM dokumen WHERE reviu_id = ? ORDER BY id DESC");
$stmt->execute([$id]);
$dokumen_list = $stmt->fetchAll();

$is_editor = in_array(current_role(), ['administrator', 'auditor'], true);

$page_title = 'Detail Reviu - ' . $reviu['nama_opd'];
$page_subtitle = $reviu['nama_jenis'] . ' Tahun ' . $reviu['tahun'];
include __DIR__ . '/includes/header.php';
?>

<a href="javascript:history.back()" class="small-muted mb-3 d-inline-block" style="font-weight:700; color:var(--blue);"><i class="bi bi-arrow-left"></i> Kembali</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-x mb-3">
      <div class="card-x-title"><i class="bi bi-info-circle-fill"></i> INFORMASI REVIU</div>
      <table class="table-x" style="font-size:13.5px;">
        <tbody>
          <tr><td class="small-muted" style="width:180px;">OPD</td><td><b><?= e($reviu['nama_opd']) ?></b></td></tr>
          <tr><td class="small-muted">Jenis Reviu</td><td><?= e($reviu['nama_jenis']) ?></td></tr>
          <tr><td class="small-muted">Tim Reviu</td><td><?= e($reviu['nama_tim']) ?></td></tr>
          <tr><td class="small-muted">Tahun</td><td><?= e($reviu['tahun']) ?></td></tr>
          <tr><td class="small-muted">Tanggal Mulai</td><td><?= format_tanggal_indo($reviu['tgl_mulai']) ?></td></tr>
          <tr><td class="small-muted">Target Selesai</td><td><?= format_tanggal_indo($reviu['tgl_target_selesai']) ?></td></tr>
          <tr><td class="small-muted">Status Dokumen</td><td><span class="badge-x <?= dokumen_badge_class($reviu['dokumen_status']) ?>"><?= e($reviu['dokumen_status']) ?></span></td></tr>
          <tr><td class="small-muted">Status Reviu</td><td><span class="badge-x <?= status_badge_class($reviu['status']) ?>"><?= e($reviu['status']) ?></span></td></tr>
          <tr><td class="small-muted">Progres</td><td>
            <div class="d-flex align-items-center gap-2">
              <div class="progress-thin" style="width:200px;"><div class="bar" style="width:<?= (int)$reviu['progres'] ?>%; background:<?= progres_bar_color($reviu['progres']) ?>;"></div></div>
              <b><?= (int)$reviu['progres'] ?>%</b>
            </div>
          </td></tr>
          <tr><td class="small-muted">Keterangan</td><td><?= e($reviu['keterangan'] ?: '-') ?></td></tr>
        </tbody>
      </table>
      <?php if ($is_editor): ?>
      <a href="daftar_reviu.php" class="btn-x btn-outline-x mt-2"><i class="bi bi-pencil"></i> Kelola di Daftar Reviu</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-x mb-3">
      <div class="card-x-title"><i class="bi bi-folder-fill"></i> DOKUMEN PENDUKUNG</div>
      <?php if (empty($dokumen_list)): ?>
        <div class="empty-state py-3"><i class="bi bi-inbox"></i>Belum ada dokumen diunggah.</div>
      <?php else: foreach ($dokumen_list as $d): ?>
        <div class="jadwal-item">
          <i class="bi bi-file-earmark-text" style="color:var(--blue); font-size:18px;"></i>
          <div style="flex:1;">
            <div class="jadwal-title"><?= e($d['nama_dokumen']) ?></div>
            <div class="jadwal-sub"><?= format_tanggal_indo($d['tanggal_upload']) ?></div>
          </div>
          <span class="badge-x <?= dokumen_badge_class($d['status']) ?>"><?= e($d['status']) ?></span>
          <?php if ($is_editor): ?>
          <form method="POST" action="process/process_dokumen.php" onsubmit="return confirm('Hapus dokumen ini?');">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
            <input type="hidden" name="reviu_id" value="<?= (int)$id ?>">
            <button type="submit" class="btn-eye" style="background:var(--red-light); color:var(--red); width:28px; height:28px;"><i class="bi bi-trash" style="font-size:12px;"></i></button>
          </form>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <?php if ($is_editor): ?>
    <div class="card-x">
      <div class="card-x-title"><i class="bi bi-upload"></i> TAMBAH DOKUMEN</div>
      <form method="POST" action="process/process_dokumen.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="add">
        <input type="hidden" name="reviu_id" value="<?= (int)$id ?>">
        <div class="form-row-x">
          <label>Nama Dokumen</label>
          <input type="text" name="nama_dokumen" class="form-control-x" placeholder="Contoh: Berita Acara Reviu" required>
        </div>
        <div class="form-row-x">
          <label>File (opsional)</label>
          <input type="file" name="file_dokumen" class="form-control-x">
        </div>
        <div class="form-row-x">
          <label>Status</label>
          <select name="status" class="form-select-x">
            <option value="Lengkap">Lengkap</option>
            <option value="Belum Lengkap">Belum Lengkap</option>
          </select>
        </div>
        <button type="submit" class="btn-x btn-primary-x w-100"><i class="bi bi-plus-lg"></i> Tambah Dokumen</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
