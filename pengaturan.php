<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor']);

$page_title = 'Pengaturan';
$page_subtitle = 'Kelola data master aplikasi';

$tab = $_GET['tab'] ?? 'opd';
if (!in_array($tab, ['opd', 'jenis_reviu', 'tim_reviu'], true)) $tab = 'opd';

$opd_list = $pdo->query("SELECT o.*, (SELECT COUNT(*) FROM reviu r WHERE r.opd_id = o.id) AS jumlah FROM opd o ORDER BY o.nama_opd ASC")->fetchAll();
$jenis_list = $pdo->query("SELECT j.*, (SELECT COUNT(*) FROM reviu r WHERE r.jenis_reviu_id = j.id) AS jumlah FROM jenis_reviu j ORDER BY j.nama_jenis ASC")->fetchAll();
$tim_list = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM reviu r WHERE r.tim_reviu_id = t.id) AS jumlah FROM tim_reviu t ORDER BY t.nama_tim ASC")->fetchAll();

include __DIR__ . '/includes/header.php';

function render_master_table($items, $type, $column, $label)
{
    echo '<table class="table-x"><thead><tr><th>No</th><th>' . e($label) . '</th><th>Jumlah Reviu</th><th>Aksi</th></tr></thead><tbody>';
    if (empty($items)) {
        echo '<tr><td colspan="4" class="text-center py-4 small-muted">Belum ada data.</td></tr>';
    } else {
        $no = 1;
        foreach ($items as $it) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td><b>' . e($it[$column]) . '</b></td>';
            echo '<td>' . (int) $it['jumlah'] . '</td>';
            echo '<td><div class="d-flex gap-1">';
            echo '<button type="button" class="btn-eye" style="background:var(--amber-light); color:#b9770e;" onclick="openEditMaster(\'' . $type . '\',' . (int)$it['id'] . ',' . json_encode($it[$column], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) . ')"><i class="bi bi-pencil"></i></button>';
            echo '<form method="POST" action="process/process_master.php" onsubmit="return confirm(\'Yakin hapus data ini?\');" style="display:inline;">';
            echo csrf_field();
            echo '<input type="hidden" name="type" value="' . e($type) . '"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="' . (int)$it['id'] . '">';
            echo '<button type="submit" class="btn-eye" style="background:var(--red-light); color:var(--red);"><i class="bi bi-trash"></i></button>';
            echo '</form></div></td></tr>';
        }
    }
    echo '</tbody></table>';
}
?>

<div class="tab-x">
  <a href="?tab=opd" style="text-decoration:none;"><button class="<?= $tab==='opd'?'active':'' ?>">Data OPD</button></a>
  <a href="?tab=jenis_reviu" style="text-decoration:none;"><button class="<?= $tab==='jenis_reviu'?'active':'' ?>">Jenis Reviu</button></a>
  <a href="?tab=tim_reviu" style="text-decoration:none;"><button class="<?= $tab==='tim_reviu'?'active':'' ?>">Tim Reviu</button></a>
</div>

<div class="card-x">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="card-x-title mb-0">
      <?php if ($tab === 'opd'): ?><i class="bi bi-bank2"></i> DAFTAR OPD
      <?php elseif ($tab === 'jenis_reviu'): ?><i class="bi bi-tags-fill"></i> DAFTAR JENIS REVIU
      <?php else: ?><i class="bi bi-people-fill"></i> DAFTAR TIM REVIU
      <?php endif; ?>
    </div>
    <button type="button" class="btn-x btn-primary-x" onclick="openAddMaster('<?= $tab ?>')"><i class="bi bi-plus-lg"></i> Tambah</button>
  </div>
  <div style="overflow-x:auto;">
    <?php
    if ($tab === 'opd') render_master_table($opd_list, 'opd', 'nama_opd', 'Nama OPD');
    elseif ($tab === 'jenis_reviu') render_master_table($jenis_list, 'jenis_reviu', 'nama_jenis', 'Nama Jenis Reviu');
    else render_master_table($tim_list, 'tim_reviu', 'nama_tim', 'Nama Tim Reviu');
    ?>
  </div>
</div>

<!-- Modal -->
<div class="modal-x-overlay" id="masterModalOverlay">
  <div class="modal-x">
    <div class="modal-x-header">
      <h3 id="masterModalTitle">Tambah Data</h3>
      <button type="button" class="modal-x-close" onclick="closeMasterModal()">&times;</button>
    </div>
    <form method="POST" action="process/process_master.php">
      <?= csrf_field() ?>
      <input type="hidden" name="type" id="m_type" value="opd">
      <input type="hidden" name="form_action" id="m_form_action" value="add">
      <input type="hidden" name="id" id="m_id" value="">
      <div class="form-row-x">
        <label id="m_label">Nama</label>
        <input type="text" name="nama" id="m_nama" class="form-control-x" required>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn-x btn-outline-x" onclick="closeMasterModal()">Batal</button>
        <button type="submit" class="btn-x btn-primary-x"><i class="bi bi-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
$page_scripts = <<<'JS'
<script>
const masterLabels = { opd: 'Nama OPD', jenis_reviu: 'Nama Jenis Reviu', tim_reviu: 'Nama Tim Reviu' };
function openAddMaster(type) {
  document.getElementById('masterModalTitle').textContent = 'Tambah Data';
  document.getElementById('m_type').value = type;
  document.getElementById('m_form_action').value = 'add';
  document.getElementById('m_id').value = '';
  document.getElementById('m_nama').value = '';
  document.getElementById('m_label').textContent = masterLabels[type] || 'Nama';
  document.getElementById('masterModalOverlay').classList.add('show');
}
function openEditMaster(type, id, nama) {
  document.getElementById('masterModalTitle').textContent = 'Edit Data';
  document.getElementById('m_type').value = type;
  document.getElementById('m_form_action').value = 'edit';
  document.getElementById('m_id').value = id;
  document.getElementById('m_nama').value = nama;
  document.getElementById('m_label').textContent = masterLabels[type] || 'Nama';
  document.getElementById('masterModalOverlay').classList.add('show');
}
function closeMasterModal() {
  document.getElementById('masterModalOverlay').classList.remove('show');
}
</script>
JS;
include __DIR__ . '/includes/footer.php';
?>
