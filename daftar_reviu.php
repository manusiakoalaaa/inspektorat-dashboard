<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor']);

$page_title = 'Daftar Reviu';
$page_subtitle = 'Kelola seluruh data reviu OPD';

$opd_list = $pdo->query("SELECT id, nama_opd FROM opd ORDER BY nama_opd ASC")->fetchAll();
$jenis_list = $pdo->query("SELECT id, nama_jenis FROM jenis_reviu ORDER BY nama_jenis ASC")->fetchAll();
$tim_list = $pdo->query("SELECT id, nama_tim FROM tim_reviu ORDER BY nama_tim ASC")->fetchAll();
$years = $pdo->query("SELECT DISTINCT tahun FROM reviu ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);

$q = trim($_GET['q'] ?? '');
$f_tahun = $_GET['tahun'] ?? '';
$f_opd = $_GET['opd'] ?? '';
$f_status = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($q !== '') { $where[] = 'o.nama_opd LIKE ?'; $params[] = '%' . $q . '%'; }
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
        ORDER BY r.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

$redirect_qs = http_build_query(['q' => $q, 'tahun' => $f_tahun, 'opd' => $f_opd, 'status' => $f_status]);

include __DIR__ . '/includes/header.php';
?>

<div class="card-x mb-3">
  <form method="GET" class="d-flex align-items-end gap-3 flex-wrap">
    <div class="filter-group" style="min-width:200px;">
      <label>Cari OPD</label>
      <input type="text" name="q" class="form-control-x" placeholder="Cari nama OPD..." value="<?= e($q) ?>">
    </div>
    <div class="filter-group" style="min-width:130px;">
      <label>Tahun</label>
      <select name="tahun" class="form-select-x">
        <option value="">Semua</option>
        <?php foreach ($years as $y): ?><option value="<?= e($y) ?>" <?= $f_tahun == $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group" style="min-width:180px;">
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
    <button type="submit" class="btn-x btn-outline-x"><i class="bi bi-search"></i> Filter</button>
    <a href="daftar_reviu.php" class="btn-x btn-outline-x"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
    <button type="button" class="btn-x btn-primary-x ms-auto" onclick="openAddModal()"><i class="bi bi-plus-lg"></i> Tambah Reviu</button>
  </form>
</div>

<div class="card-x">
  <div class="card-x-title"><i class="bi bi-file-earmark-text-fill"></i> DATA REVIU OPD (<?= count($data) ?> data)</div>
  <div style="overflow-x:auto;">
    <table class="table-x">
      <thead>
        <tr>
          <th>No</th><th>OPD</th><th>Jenis</th><th>Tahun</th><th>Tim</th><th>Tgl Mulai</th><th>Tgl Target</th>
          <th>Dokumen</th><th>Status</th><th style="min-width:130px;">Progres</th><th>Keterangan</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="12" class="text-center py-4 small-muted">Tidak ada data.</td></tr>
        <?php else: $no = 1; foreach ($data as $r): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><b><?= e($r['nama_opd']) ?></b></td>
          <td><?= e($r['nama_jenis']) ?></td>
          <td><?= e($r['tahun']) ?></td>
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
          <td>
            <div class="d-flex gap-1">
              <a href="reviu_detail.php?id=<?= (int)$r['id'] ?>" class="btn-eye" title="Lihat"><i class="bi bi-eye"></i></a>
              <button type="button" class="btn-eye" style="background:var(--amber-light); color:#b9770e;" title="Edit"
                onclick='openEditModal(<?= json_encode([
                    "id" => $r['id'], "opd_id" => $r['opd_id'], "jenis_reviu_id" => $r['jenis_reviu_id'],
                    "tim_reviu_id" => $r['tim_reviu_id'], "tahun" => $r['tahun'], "tgl_mulai" => $r['tgl_mulai'],
                    "tgl_target_selesai" => $r['tgl_target_selesai'], "dokumen_status" => $r['dokumen_status'],
                    "status" => $r['status'], "progres" => $r['progres'], "keterangan" => $r['keterangan'],
                ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <i class="bi bi-pencil"></i>
              </button>
              <form method="POST" action="process/process_reviu.php" onsubmit="return confirm('Yakin hapus data reviu ini?');" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="redirect_qs" value="<?= e($redirect_qs) ?>">
                <button type="submit" class="btn-eye" style="background:var(--red-light); color:var(--red);" title="Hapus"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== Modal Tambah/Edit Reviu ===== -->
<div class="modal-x-overlay" id="reviuModalOverlay">
  <div class="modal-x">
    <div class="modal-x-header">
      <h3 id="reviuModalTitle">Tambah Reviu</h3>
      <button type="button" class="modal-x-close" onclick="closeReviuModal()">&times;</button>
    </div>
    <form method="POST" action="process/process_reviu.php" id="reviuForm">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" id="form_action" value="add">
      <input type="hidden" name="id" id="f_id" value="">
      <input type="hidden" name="redirect_qs" value="<?= e($redirect_qs) ?>">

      <div class="form-grid-2">
        <div class="form-row-x">
          <label>OPD</label>
          <select name="opd_id" id="f_opd_id" class="form-select-x" required>
            <option value="">-- Pilih OPD --</option>
            <?php foreach ($opd_list as $o): ?><option value="<?= (int)$o['id'] ?>"><?= e($o['nama_opd']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-row-x">
          <label>Jenis Reviu</label>
          <select name="jenis_reviu_id" id="f_jenis_id" class="form-select-x" required>
            <option value="">-- Pilih Jenis --</option>
            <?php foreach ($jenis_list as $j): ?><option value="<?= (int)$j['id'] ?>"><?= e($j['nama_jenis']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row-x">
          <label>Tim Reviu</label>
          <select name="tim_reviu_id" id="f_tim_id" class="form-select-x" required>
            <option value="">-- Pilih Tim --</option>
            <?php foreach ($tim_list as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['nama_tim']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-row-x">
          <label>Tahun</label>
          <input type="number" name="tahun" id="f_tahun" class="form-control-x" value="<?= date('Y') ?>" min="2000" max="2100" required>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row-x">
          <label>Tanggal Mulai</label>
          <input type="date" name="tgl_mulai" id="f_tgl_mulai" class="form-control-x" required>
        </div>
        <div class="form-row-x">
          <label>Tanggal Target Selesai</label>
          <input type="date" name="tgl_target_selesai" id="f_tgl_target" class="form-control-x" required>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row-x">
          <label>Status Dokumen</label>
          <select name="dokumen_status" id="f_dok_status" class="form-select-x">
            <option value="Lengkap">Lengkap</option>
            <option value="Belum Lengkap">Belum Lengkap</option>
          </select>
        </div>
        <div class="form-row-x">
          <label>Status Reviu</label>
          <select name="status" id="f_status" class="form-select-x">
            <option value="Belum Mulai">Belum Mulai</option>
            <option value="Proses">Proses</option>
            <option value="Selesai">Selesai</option>
            <option value="Tertunda">Tertunda</option>
          </select>
        </div>
      </div>
      <div class="form-row-x">
        <label>Progres (%)</label>
        <input type="range" name="progres" id="f_progres" min="0" max="100" value="0" oninput="document.getElementById('progresLabel').textContent=this.value+'%'">
        <div class="small-muted" id="progresLabel">0%</div>
      </div>
      <div class="form-row-x">
        <label>Keterangan</label>
        <input type="text" name="keterangan" id="f_keterangan" class="form-control-x" placeholder="Contoh: Menunggu dokumen dari OPD">
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn-x btn-outline-x" onclick="closeReviuModal()">Batal</button>
        <button type="submit" class="btn-x btn-primary-x"><i class="bi bi-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
$page_scripts = <<<'JS'
<script>
function openAddModal() {
  document.getElementById('reviuModalTitle').textContent = 'Tambah Reviu';
  document.getElementById('form_action').value = 'add';
  document.getElementById('reviuForm').reset();
  document.getElementById('f_id').value = '';
  document.getElementById('progresLabel').textContent = '0%';
  document.getElementById('reviuModalOverlay').classList.add('show');
}
function openEditModal(d) {
  document.getElementById('reviuModalTitle').textContent = 'Edit Reviu';
  document.getElementById('form_action').value = 'edit';
  document.getElementById('f_id').value = d.id;
  document.getElementById('f_opd_id').value = d.opd_id;
  document.getElementById('f_jenis_id').value = d.jenis_reviu_id;
  document.getElementById('f_tim_id').value = d.tim_reviu_id;
  document.getElementById('f_tahun').value = d.tahun;
  document.getElementById('f_tgl_mulai').value = d.tgl_mulai;
  document.getElementById('f_tgl_target').value = d.tgl_target_selesai;
  document.getElementById('f_dok_status').value = d.dokumen_status;
  document.getElementById('f_status').value = d.status;
  document.getElementById('f_progres').value = d.progres;
  document.getElementById('progresLabel').textContent = d.progres + '%';
  document.getElementById('f_keterangan').value = d.keterangan || '';
  document.getElementById('reviuModalOverlay').classList.add('show');
}
function closeReviuModal() {
  document.getElementById('reviuModalOverlay').classList.remove('show');
}
</script>
JS;
include __DIR__ . '/includes/footer.php';
?>
