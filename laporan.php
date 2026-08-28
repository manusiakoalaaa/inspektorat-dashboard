<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor']);

$years = $pdo->query("SELECT DISTINCT tahun FROM reviu ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($years)) $years = [date('Y')];
$opd_list = $pdo->query("SELECT id, nama_opd FROM opd ORDER BY nama_opd ASC")->fetchAll();

$f_tahun = $_GET['tahun'] ?? '';
$f_opd = $_GET['opd'] ?? '';
$f_status = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($f_tahun !== '') { $where[] = 'r.tahun = ?'; $params[] = $f_tahun; }
if ($f_opd !== '') { $where[] = 'r.opd_id = ?'; $params[] = $f_opd; }
if ($f_status !== '') { $where[] = 'r.status = ?'; $params[] = $f_status; }
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT r.*, o.nama_opd, j.nama_jenis, t.nama_tim
        FROM reviu r
        JOIN opd o ON o.id = r.opd_id
        JOIN jenis_reviu j ON j.id = r.jenis_reviu_id
        JOIN tim_reviu t ON t.id = r.tim_reviu_id
        $where_sql
        ORDER BY o.nama_opd ASC, r.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// ===== Export CSV =====
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_reviu_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM utk Excel
    fputcsv($out, ['No', 'OPD', 'Jenis Reviu', 'Tahun', 'Tim Reviu', 'Tgl Mulai', 'Tgl Target Selesai', 'Status Dokumen', 'Status', 'Progres (%)', 'Keterangan']);
    $no = 1;
    foreach ($data as $r) {
        fputcsv($out, [$no++, $r['nama_opd'], $r['nama_jenis'], $r['tahun'], $r['nama_tim'], $r['tgl_mulai'], $r['tgl_target_selesai'], $r['dokumen_status'], $r['status'], $r['progres'], $r['keterangan']]);
    }
    fclose($out);
    exit;
}

$page_title = 'Laporan Reviu OPD';
$page_subtitle = 'Cetak atau unduh laporan progres reviu';
include __DIR__ . '/includes/header.php';
?>

<div class="card-x mb-3 no-print">
  <form method="GET" class="d-flex align-items-end gap-3 flex-wrap">
    <div class="filter-group" style="min-width:130px;">
      <label>Tahun</label>
      <select name="tahun" class="form-select-x">
        <option value="">Semua</option>
        <?php foreach ($years as $y): ?><option value="<?= e($y) ?>" <?= $f_tahun == $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group" style="min-width:200px;">
      <label>OPD</label>
      <select name="opd" class="form-select-x">
        <option value="">Semua</option>
        <?php foreach ($opd_list as $o): ?><option value="<?= (int)$o['id'] ?>" <?= $f_opd == $o['id'] ? 'selected' : '' ?>><?= e($o['nama_opd']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group" style="min-width:150px;">
      <label>Status</label>
      <select name="status" class="form-select-x">
        <option value="">Semua</option>
        <?php foreach (['Belum Mulai','Proses','Selesai','Tertunda'] as $s): ?>
        <option value="<?= $s ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-x btn-outline-x"><i class="bi bi-search"></i> Terapkan</button>
    <div class="ms-auto d-flex gap-2">
      <button type="button" class="btn-x btn-outline-x" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
      <a class="btn-x btn-primary-x" href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"><i class="bi bi-download"></i> Export CSV</a>
    </div>
  </form>
</div>

<div class="card-x">
  <div class="text-center mb-3">
    <div style="font-weight:800; font-size:16px; color:var(--navy);">LAPORAN PROGRES REVIU OPD</div>
    <div class="small-muted"><?= e(APP_INSTANSI) ?></div>
    <div class="small-muted">Dicetak pada: <?= format_tanggal_indo(date('Y-m-d')) ?></div>
  </div>
  <div style="overflow-x:auto;">
    <table class="table-x">
      <thead><tr><th>No</th><th>OPD</th><th>Jenis</th><th>Tahun</th><th>Tim</th><th>Tgl Mulai</th><th>Target</th><th>Dokumen</th><th>Status</th><th>Progres</th><th>Keterangan</th></tr></thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="11" class="text-center py-4 small-muted">Tidak ada data sesuai filter.</td></tr>
        <?php else: $no=1; foreach ($data as $r): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= e($r['nama_opd']) ?></td>
          <td><?= e($r['nama_jenis']) ?></td>
          <td><?= e($r['tahun']) ?></td>
          <td><?= e($r['nama_tim']) ?></td>
          <td><?= format_tanggal_indo($r['tgl_mulai']) ?></td>
          <td><?= format_tanggal_indo($r['tgl_target_selesai']) ?></td>
          <td><span class="badge-x <?= dokumen_badge_class($r['dokumen_status']) ?>"><?= e($r['dokumen_status']) ?></span></td>
          <td><span class="badge-x <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
          <td><?= (int)$r['progres'] ?>%</td>
          <td class="small-muted"><?= e($r['keterangan'] ?: '-') ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
