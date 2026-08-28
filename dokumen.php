<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor']);

$page_title = 'Dokumen';
$page_subtitle = 'Seluruh dokumen pendukung reviu OPD';

$f_status = $_GET['status'] ?? '';
$where = [];
$params = [];
if ($f_status !== '') { $where[] = 'd.status = ?'; $params[] = $f_status; }
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT d.*, o.nama_opd, r.id AS reviu_id, j.nama_jenis
        FROM dokumen d
        JOIN reviu r ON r.id = d.reviu_id
        JOIN opd o ON o.id = r.opd_id
        JOIN jenis_reviu j ON j.id = r.jenis_reviu_id
        $where_sql
        ORDER BY d.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

$total = count($data);
$lengkap = count(array_filter($data, function ($d) { return $d['status'] === 'Lengkap'; }));
$belum = $total - $lengkap;

include __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-3">
  <div style="flex:1; min-width:180px;">
    <div class="stat-card stat-blue"><div class="stat-icon"><i class="bi bi-folder-fill"></i></div>
      <div><div class="stat-label">Total Dokumen</div><div class="stat-value"><?= $total ?></div></div></div>
  </div>
  <div style="flex:1; min-width:180px;">
    <div class="stat-card stat-green"><div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
      <div><div class="stat-label">Lengkap</div><div class="stat-value"><?= $lengkap ?></div></div></div>
  </div>
  <div style="flex:1; min-width:180px;">
    <div class="stat-card stat-red"><div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
      <div><div class="stat-label">Belum Lengkap</div><div class="stat-value"><?= $belum ?></div></div></div>
  </div>
</div>

<div class="card-x mb-3">
  <form method="GET" class="d-flex align-items-end gap-3 flex-wrap">
    <div class="filter-group" style="min-width:180px;">
      <label>Status Dokumen</label>
      <select name="status" class="form-select-x" onchange="this.form.submit()">
        <option value="">Semua</option>
        <option value="Lengkap" <?= $f_status === 'Lengkap' ? 'selected' : '' ?>>Lengkap</option>
        <option value="Belum Lengkap" <?= $f_status === 'Belum Lengkap' ? 'selected' : '' ?>>Belum Lengkap</option>
      </select>
    </div>
  </form>
</div>

<div class="card-x">
  <div class="card-x-title"><i class="bi bi-folder-fill"></i> DAFTAR DOKUMEN</div>
  <div style="overflow-x:auto;">
    <table class="table-x">
      <thead><tr><th>No</th><th>Nama Dokumen</th><th>OPD</th><th>Jenis Reviu</th><th>Status</th><th>Tgl Upload</th><th>File</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="8" class="text-center py-4 small-muted">Belum ada dokumen.</td></tr>
        <?php else: $no=1; foreach ($data as $d): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><b><?= e($d['nama_dokumen']) ?></b></td>
          <td><?= e($d['nama_opd']) ?></td>
          <td><?= e($d['nama_jenis']) ?></td>
          <td><span class="badge-x <?= dokumen_badge_class($d['status']) ?>"><?= e($d['status']) ?></span></td>
          <td><?= format_tanggal_indo($d['tanggal_upload']) ?></td>
          <td>
            <?php if (!empty($d['file_path'])): ?>
              <a href="uploads/dokumen/<?= e($d['file_path']) ?>" target="_blank" class="small-muted" style="color:var(--blue); font-weight:700;"><i class="bi bi-paperclip"></i> Lihat</a>
            <?php else: ?><span class="small-muted">-</span><?php endif; ?>
          </td>
          <td><a href="reviu_detail.php?id=<?= (int)$d['reviu_id'] ?>" class="btn-eye"><i class="bi bi-eye"></i></a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
