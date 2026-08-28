<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor']);

$page_title = 'Reviu Tertunda';
$page_subtitle = 'Daftar reviu yang berstatus tertunda dan perlu tindak lanjut';

$sql = "SELECT r.*, o.nama_opd, j.nama_jenis, t.nama_tim
        FROM reviu r
        JOIN opd o ON o.id = r.opd_id
        JOIN jenis_reviu j ON j.id = r.jenis_reviu_id
        JOIN tim_reviu t ON t.id = r.tim_reviu_id
        WHERE r.status = 'Tertunda'
        ORDER BY r.tgl_target_selesai ASC";
$data = $pdo->query($sql)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="card-x mb-3" style="background:var(--red-light); border:none;">
  <div class="d-flex align-items-center gap-3">
    <div class="stat-icon" style="background:var(--red); color:#fff; width:44px; height:44px;"><i class="bi bi-exclamation-triangle-fill"></i></div>
    <div>
      <div style="font-weight:800; color:var(--red); font-size:15px;"><?= count($data) ?> Reviu Berstatus Tertunda</div>
      <div class="small-muted">Segera lakukan tindak lanjut agar target penyelesaian tidak semakin terlambat.</div>
    </div>
  </div>
</div>

<div class="card-x">
  <div class="card-x-title"><i class="bi bi-clock-history"></i> DAFTAR REVIU TERTUNDA</div>
  <div style="overflow-x:auto;">
    <table class="table-x">
      <thead>
        <tr><th>No</th><th>OPD</th><th>Jenis</th><th>Tahun</th><th>Tim</th><th>Target Selesai</th><th>Keterlambatan</th><th>Dokumen</th><th style="min-width:130px;">Progres</th><th>Keterangan</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="11" class="text-center py-4 small-muted"><i class="bi bi-emoji-smile" style="font-size:22px; display:block; margin-bottom:6px;"></i>Tidak ada reviu yang tertunda saat ini.</td></tr>
        <?php else: $no=1; foreach ($data as $r):
            $target = strtotime($r['tgl_target_selesai']);
            $today = strtotime(date('Y-m-d'));
            $selisih = floor(($today - $target) / 86400);
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><b><?= e($r['nama_opd']) ?></b></td>
          <td><?= e($r['nama_jenis']) ?></td>
          <td><?= e($r['tahun']) ?></td>
          <td><?= e($r['nama_tim']) ?></td>
          <td><?= format_tanggal_indo($r['tgl_target_selesai']) ?></td>
          <td>
            <?php if ($selisih > 0): ?>
              <span class="badge-x badge-tertunda"><?= $selisih ?> hari terlambat</span>
            <?php else: ?>
              <span class="badge-x badge-belummulai">Belum lewat target</span>
            <?php endif; ?>
          </td>
          <td><span class="badge-x <?= dokumen_badge_class($r['dokumen_status']) ?>"><?= e($r['dokumen_status']) ?></span></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="progress-thin" style="flex:1;"><div class="bar" style="width:<?= (int)$r['progres'] ?>%; background:<?= progres_bar_color($r['progres']) ?>;"></div></div>
              <span style="font-weight:700; font-size:12px;"><?= (int)$r['progres'] ?>%</span>
            </div>
          </td>
          <td class="small-muted"><?= e($r['keterangan'] ?: '-') ?></td>
          <td><a href="reviu_detail.php?id=<?= (int)$r['id'] ?>" class="btn-eye"><i class="bi bi-eye"></i></a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
