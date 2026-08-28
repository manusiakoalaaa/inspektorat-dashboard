<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor', 'pimpinan']);

$page_title = 'Rekapitulasi Reviu OPD';
$page_subtitle = 'Ringkasan progres reviu per Perangkat Daerah';

$years = $pdo->query("SELECT DISTINCT tahun FROM reviu ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($years)) $years = [date('Y')];
$tahun = isset($_GET['tahun']) && in_array($_GET['tahun'], $years) ? (int) $_GET['tahun'] : (int) $years[0];

$sql = "SELECT o.id, o.nama_opd,
          COUNT(r.id) AS total,
          SUM(CASE WHEN r.status='Selesai' THEN 1 ELSE 0 END) AS selesai,
          SUM(CASE WHEN r.status='Proses' THEN 1 ELSE 0 END) AS proses,
          SUM(CASE WHEN r.status='Tertunda' THEN 1 ELSE 0 END) AS tertunda,
          SUM(CASE WHEN r.status='Belum Mulai' THEN 1 ELSE 0 END) AS belum_mulai,
          COALESCE(AVG(r.progres),0) AS rata_progres
        FROM opd o
        LEFT JOIN reviu r ON r.opd_id = o.id AND r.tahun = ?
        GROUP BY o.id, o.nama_opd
        ORDER BY o.nama_opd ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tahun]);
$rekap = $stmt->fetchAll();

$grand_total = 0; $grand_selesai = 0; $grand_proses = 0; $grand_tertunda = 0; $grand_belum = 0; $sum_progres = 0; $count_opd_ada_reviu = 0;
foreach ($rekap as $r) {
    $grand_total += (int)$r['total'];
    $grand_selesai += (int)$r['selesai'];
    $grand_proses += (int)$r['proses'];
    $grand_tertunda += (int)$r['tertunda'];
    $grand_belum += (int)$r['belum_mulai'];
    if ((int)$r['total'] > 0) { $sum_progres += (float)$r['rata_progres']; $count_opd_ada_reviu++; }
}
$rata_keseluruhan = $count_opd_ada_reviu > 0 ? round($sum_progres / $count_opd_ada_reviu) : 0;

include __DIR__ . '/includes/header.php';
?>

<div class="card-x mb-3">
  <form method="GET" class="d-flex align-items-end gap-3 flex-wrap">
    <div class="filter-group" style="min-width:180px;">
      <label>Tahun</label>
      <select class="form-select-x" name="tahun" onchange="this.form.submit()">
        <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= e($y) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="small-muted mb-2">Menampilkan rekapitulasi reviu tahun <b><?= e($tahun) ?></b> untuk seluruh OPD.</div>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-2-4" style="flex:1; min-width:150px;">
    <div class="stat-card stat-blue"><div class="stat-icon"><i class="bi bi-journal-text"></i></div>
      <div><div class="stat-label">Total Reviu</div><div class="stat-value"><?= $grand_total ?></div></div></div>
  </div>
  <div style="flex:1; min-width:150px;">
    <div class="stat-card stat-green"><div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
      <div><div class="stat-label">Selesai</div><div class="stat-value"><?= $grand_selesai ?></div></div></div>
  </div>
  <div style="flex:1; min-width:150px;">
    <div class="stat-card stat-amber"><div class="stat-icon"><i class="bi bi-gear-fill"></i></div>
      <div><div class="stat-label">Proses</div><div class="stat-value"><?= $grand_proses ?></div></div></div>
  </div>
  <div style="flex:1; min-width:150px;">
    <div class="stat-card stat-red"><div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
      <div><div class="stat-label">Tertunda</div><div class="stat-value"><?= $grand_tertunda ?></div></div></div>
  </div>
  <div style="flex:1; min-width:150px;">
    <div class="stat-card stat-purple"><div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div>
      <div><div class="stat-label">Rata-rata Progres</div><div class="stat-value"><?= $rata_keseluruhan ?>%</div></div></div>
  </div>
</div>

<div class="card-x">
  <div class="card-x-title"><i class="bi bi-clipboard-data-fill"></i> REKAPITULASI PER OPD - TAHUN <?= e($tahun) ?></div>
  <div style="overflow-x:auto;">
    <table class="table-x">
      <thead>
        <tr>
          <th>No</th><th>OPD</th><th>Total Reviu</th><th>Selesai</th><th>Proses</th><th>Tertunda</th><th>Belum Mulai</th><th style="min-width:160px;">Rata-rata Progres</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rekap)): ?>
          <tr><td colspan="8" class="text-center py-4 small-muted">Belum ada data OPD.</td></tr>
        <?php else: $no = 1; foreach ($rekap as $r): $prog = round((float)$r['rata_progres']); ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><b><?= e($r['nama_opd']) ?></b></td>
          <td><?= (int)$r['total'] ?></td>
          <td><span class="badge-x badge-selesai"><?= (int)$r['selesai'] ?></span></td>
          <td><span class="badge-x badge-proses"><?= (int)$r['proses'] ?></span></td>
          <td><span class="badge-x badge-tertunda"><?= (int)$r['tertunda'] ?></span></td>
          <td><span class="badge-x badge-belummulai"><?= (int)$r['belum_mulai'] ?></span></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="progress-thin" style="flex:1;"><div class="bar" style="width:<?= $prog ?>%; background:<?= progres_bar_color($prog) ?>;"></div></div>
              <span style="font-weight:700; font-size:12px;"><?= $prog ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>.col-md-2-4{}</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
