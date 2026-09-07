<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor']);

$page_title = 'Dokumen';
$page_subtitle = 'Arsip dokumen reviu OPD dipisah per jenis';

$jenis_list = dokumen_jenis_list();

$tab = $_GET['jenis'] ?? 'semua';
if ($tab !== 'semua' && !isset($jenis_list[$tab])) $tab = 'semua';

$where = [];
$params = [];
if ($tab !== 'semua') { $where[] = 'd.jenis = ?'; $params[] = $tab; }
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT d.*, o.nama_opd, r.id AS reviu_id, j.nama_jenis
        FROM dokumen d
        JOIN reviu r ON r.id = d.reviu_id
        JOIN opd o ON o.id = r.opd_id
        JOIN jenis_reviu j ON j.id = r.jenis_reviu_id
        $where_sql
        ORDER BY FIELD(d.jenis,'SPT','Pemeriksaan','KKR','LHP'), d.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Rekap jumlah per jenis
$rekap = $pdo->query("SELECT jenis, COUNT(*) AS jml FROM dokumen GROUP BY jenis")->fetchAll(PDO::FETCH_KEY_PAIR);

include __DIR__ . '/includes/header.php';

function render_dok_table($rows)
{
    echo '<div style="overflow-x:auto;"><table class="table-x">';
    echo '<thead><tr><th>No</th><th>Nama Dokumen</th><th>OPD</th><th>Jenis Reviu</th><th>Tgl Upload</th><th>File</th><th>Aksi</th></tr></thead><tbody>';
    if (empty($rows)) {
        echo '<tr><td colspan="7" class="text-center py-4 small-muted">Belum ada dokumen.</td></tr>';
    } else {
        $no = 1;
        foreach ($rows as $d) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td><b>' . e($d['nama_dokumen']) . '</b></td>';
            echo '<td>' . e($d['nama_opd']) . '</td>';
            echo '<td>' . e($d['nama_jenis']) . '</td>';
            echo '<td>' . format_tanggal_indo($d['tanggal_upload']) . '</td>';
            if (!empty($d['file_path'])) {
                echo '<td><a href="uploads/dokumen/' . e($d['file_path']) . '" target="_blank" class="small-muted" style="color:var(--blue); font-weight:700;"><i class="bi bi-paperclip"></i> Lihat</a></td>';
            } else {
                echo '<td><span class="small-muted">-</span></td>';
            }
            echo '<td><a href="reviu_detail.php?id=' . (int)$d['reviu_id'] . '" class="btn-eye"><i class="bi bi-eye"></i></a></td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table></div>';
}
?>

<div class="row g-3 mb-3">
  <?php
  $total = array_sum($rekap);
  $cards = [
    ['Total Dokumen', $total, 'stat-blue', 'bi-folder-fill'],
    ['SPT', (int)($rekap['SPT'] ?? 0), 'stat-blue', 'bi-file-earmark-text-fill'],
    ['Pemeriksaan', (int)($rekap['Pemeriksaan'] ?? 0), 'stat-green', 'bi-search'],
    ['KKR', (int)($rekap['KKR'] ?? 0), 'stat-amber', 'bi-journal-text'],
    ['LHP', (int)($rekap['LHP'] ?? 0), 'stat-green', 'bi-file-earmark-check-fill'],
  ];
  foreach ($cards as $c): ?>
  <div style="flex:1; min-width:150px;">
    <div class="stat-card <?= $c[2] ?>"><div class="stat-icon"><i class="bi <?= $c[3] ?>"></i></div>
      <div><div class="stat-label"><?= e($c[0]) ?></div><div class="stat-value"><?= $c[1] ?></div></div></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="tab-x">
  <a href="?jenis=semua" style="text-decoration:none;"><button class="<?= $tab==='semua'?'active':'' ?>">Semua</button></a>
  <?php foreach ($jenis_list as $key => $cfg): ?>
    <a href="?jenis=<?= e($key) ?>" style="text-decoration:none;"><button class="<?= $tab===$key?'active':'' ?>"><?= e($cfg['singkat']) ?> (<?= (int)($rekap[$key] ?? 0) ?>)</button></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'semua'): ?>
  <?php
  $grouped = [];
  foreach ($data as $d) { $grouped[$d['jenis']][] = $d; }
  foreach ($jenis_list as $key => $cfg): ?>
  <div class="card-x mb-3">
    <div class="card-x-title"><i class="bi bi-folder-fill"></i> <?= e($cfg['label']) ?> &mdash; <?= count($grouped[$key] ?? []) ?> dokumen</div>
    <?php render_dok_table($grouped[$key] ?? []); ?>
  </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card-x">
    <div class="card-x-title"><i class="bi bi-folder-fill"></i> DAFTAR DOKUMEN &mdash; <?= e($jenis_list[$tab]['label']) ?></div>
    <?php render_dok_table($data); ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
