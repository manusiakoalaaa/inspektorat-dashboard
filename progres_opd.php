<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor']);

$page_title = 'Progres OPD';
$page_subtitle = 'Progres reviu dikelompokkan per Perangkat Daerah';

$years = $pdo->query("SELECT DISTINCT tahun FROM reviu ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($years)) $years = [date('Y')];
$tahun = isset($_GET['tahun']) && in_array($_GET['tahun'], $years) ? (int) $_GET['tahun'] : (int) $years[0];
$selected_opd = isset($_GET['opd_id']) ? (int) $_GET['opd_id'] : 0;

$sql = "SELECT o.id, o.nama_opd, COUNT(r.id) AS total,
          SUM(CASE WHEN r.status='Selesai' THEN 1 ELSE 0 END) AS selesai,
          COALESCE(AVG(r.progres),0) AS rata_progres
        FROM opd o
        LEFT JOIN reviu r ON r.opd_id = o.id AND r.tahun = ?
        GROUP BY o.id, o.nama_opd
        ORDER BY o.nama_opd ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tahun]);
$opd_summary = $stmt->fetchAll();

$detail = [];
if ($selected_opd) {
    $stmt = $pdo->prepare("SELECT r.*, j.nama_jenis, t.nama_tim FROM reviu r
                            JOIN jenis_reviu j ON j.id = r.jenis_reviu_id
                            JOIN tim_reviu t ON t.id = r.tim_reviu_id
                            WHERE r.opd_id = ? AND r.tahun = ? ORDER BY r.id DESC");
    $stmt->execute([$selected_opd, $tahun]);
    $detail = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<div class="card-x mb-3">
  <form method="GET" class="d-flex align-items-end gap-3 flex-wrap">
    <?php if ($selected_opd): ?><input type="hidden" name="opd_id" value="<?= $selected_opd ?>"><?php endif; ?>
    <div class="filter-group" style="min-width:160px;">
      <label>Tahun</label>
      <select class="form-select-x" name="tahun" onchange="this.form.submit()">
        <?php foreach ($years as $y): ?><option value="<?= e($y) ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php if ($selected_opd): ?>
      <a href="progres_opd.php?tahun=<?= $tahun ?>" class="btn-x btn-outline-x"><i class="bi bi-arrow-left"></i> Semua OPD</a>
    <?php endif; ?>
  </form>
</div>

<?php if (!$selected_opd): ?>
<div class="row g-3">
  <?php foreach ($opd_summary as $o): $prog = round((float)$o['rata_progres']); ?>
  <div class="col-md-6 col-lg-4">
    <a href="progres_opd.php?opd_id=<?= (int)$o['id'] ?>&tahun=<?= $tahun ?>" class="card-x d-block" style="color:inherit;">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div style="font-weight:800; font-size:14.5px; color:var(--navy);"><?= e($o['nama_opd']) ?></div>
        <span class="badge-x badge-belummulai"><?= (int)$o['total'] ?> Reviu</span>
      </div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="progress-thin" style="flex:1;"><div class="bar" style="width:<?= $prog ?>%; background:<?= progres_bar_color($prog) ?>;"></div></div>
        <b style="font-size:13px;"><?= $prog ?>%</b>
      </div>
      <div class="small-muted"><?= (int)$o['selesai'] ?> dari <?= (int)$o['total'] ?> reviu selesai</div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<?php else:
  $opd_name = '';
  foreach ($opd_summary as $o) { if ($o['id'] == $selected_opd) { $opd_name = $o['nama_opd']; break; } }
?>
<div class="card-x">
  <div class="card-x-title"><i class="bi bi-bank2"></i> DETAIL PROGRES - <?= e($opd_name) ?> (<?= e($tahun) ?>)</div>
  <div style="overflow-x:auto;">
    <table class="table-x">
      <thead><tr><th>No</th><th>Jenis Reviu</th><th>Tim</th><th>Tgl Mulai</th><th>Target Selesai</th><th>Dokumen</th><th>Status</th><th style="min-width:140px;">Progres</th><th>Keterangan</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($detail)): ?>
          <tr><td colspan="10" class="text-center py-4 small-muted">Belum ada reviu untuk OPD ini di tahun <?= e($tahun) ?>.</td></tr>
        <?php else: $no=1; foreach ($detail as $r): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= e($r['nama_jenis']) ?></td>
          <td><?= e($r['nama_tim']) ?></td>
          <td><?= format_tanggal_indo($r['tgl_mulai']) ?></td>
          <td><?= format_tanggal_indo($r['tgl_target_selesai']) ?></td>
          <td><span class="badge-x <?= dokumen_badge_class($r['dokumen_status']) ?>"><?= e($r['dokumen_status']) ?></span></td>
          <td><span class="badge-x <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
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
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
