<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator']);

$page_title = 'Anggota Tim Reviu';
$page_subtitle = 'Kelola anggota tiap tim reviu dan pendaftaran akun Auditor';

$tim_list = $pdo->query("SELECT * FROM tim_reviu ORDER BY nama_tim ASC")->fetchAll();

// role auditor id (untuk info)
$auditor_role = $pdo->query("SELECT id FROM roles WHERE slug = 'auditor' LIMIT 1")->fetchColumn();

// Anggota per tim
$anggota = $pdo->query("SELECT ta.id, ta.tim_reviu_id, ta.peran, ta.created_at,
                               u.id AS user_id, u.nama_lengkap, u.username, u.jabatan, u.status
                        FROM tim_anggota ta
                        JOIN users u ON u.id = ta.user_id
                        ORDER BY (ta.peran = 'Ketua') DESC, u.nama_lengkap ASC")->fetchAll();
$anggota_by_tim = [];
foreach ($anggota as $a) {
    $anggota_by_tim[$a['tim_reviu_id']][] = $a;
}

// Auditor yang belum tergabung ke tim tertentu -> untuk opsi "tambah dari akun yang ada"
$auditor_users = $pdo->query("SELECT u.id, u.nama_lengkap, u.username
                              FROM users u JOIN roles r ON r.id = u.role_id
                              WHERE r.slug = 'auditor'
                              ORDER BY u.nama_lengkap ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="card-x mb-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="small-muted">
      Total <b><?= count($tim_list) ?></b> tim reviu &middot; <b><?= count($anggota) ?></b> anggota terdaftar.
      Tim reviu dikelola pada menu <a href="pengaturan.php?tab=tim_reviu" style="font-weight:700; color:var(--blue);">Pengaturan</a>.
    </div>
    <button type="button" class="btn-x btn-primary-x" onclick="openAddAnggota()"><i class="bi bi-person-plus-fill"></i> Tambah Anggota</button>
  </div>
</div>

<?php if (empty($tim_list)): ?>
  <div class="card-x"><div class="empty-state"><i class="bi bi-people"></i>Belum ada tim reviu. Tambahkan lebih dulu di menu Pengaturan &rarr; Tim Reviu.</div></div>
<?php else: foreach ($tim_list as $t): $rows = $anggota_by_tim[$t['id']] ?? []; ?>
<div class="card-x mb-3">
  <div class="d-flex justify-content-between align-items-center" style="margin-bottom:12px;">
    <div class="card-x-title mb-0"><i class="bi bi-people-fill"></i> <?= e($t['nama_tim']) ?> &mdash; <?= count($rows) ?> anggota</div>
    <button type="button" class="btn-x btn-outline-x" onclick="openAddAnggota(<?= (int)$t['id'] ?>)"><i class="bi bi-plus-lg"></i> Tambah ke tim ini</button>
  </div>
  <div style="overflow-x:auto;">
    <table class="table-x">
      <thead><tr><th>No</th><th>Nama Lengkap</th><th>Username</th><th>Jabatan</th><th>Peran</th><th>Status Akun</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center py-4 small-muted">Belum ada anggota pada tim ini.</td></tr>
        <?php else: $no = 1; foreach ($rows as $a): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><b><?= e($a['nama_lengkap']) ?></b></td>
          <td><?= e($a['username']) ?></td>
          <td><?= e($a['jabatan'] ?: '-') ?></td>
          <td><span class="badge-x <?= $a['peran'] === 'Ketua' ? 'badge-proses' : 'badge-belummulai' ?>"><?= e($a['peran']) ?></span></td>
          <td><span class="badge-x <?= $a['status'] === 'aktif' ? 'badge-lengkap' : 'badge-belumlengkap' ?>"><?= $a['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?></span></td>
          <td>
            <form method="POST" action="process/process_anggota.php" onsubmit="return confirm('Keluarkan anggota ini dari tim? Akun tetap ada di Manajemen User.');" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="form_action" value="remove">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button type="submit" class="btn-eye" style="background:var(--red-light); color:var(--red);" title="Keluarkan dari tim"><i class="bi bi-person-dash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; endif; ?>

<!-- Modal Tambah Anggota -->
<div class="modal-x-overlay" id="anggotaModalOverlay">
  <div class="modal-x">
    <div class="modal-x-header">
      <h3>Tambah Anggota Tim</h3>
      <button type="button" class="modal-x-close" onclick="closeAnggotaModal()">&times;</button>
    </div>
    <form method="POST" action="process/process_anggota.php">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="add">

      <div class="form-grid-2">
        <div class="form-row-x">
          <label>Tim Reviu</label>
          <select name="tim_reviu_id" id="a_tim" class="form-select-x" required>
            <option value="">-- Pilih Tim --</option>
            <?php foreach ($tim_list as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= e($t['nama_tim']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row-x">
          <label>Peran dalam Tim</label>
          <select name="peran" class="form-select-x">
            <option value="Anggota">Anggota</option>
            <option value="Ketua">Ketua</option>
          </select>
        </div>
      </div>

      <div class="form-row-x">
        <label>Sumber Anggota</label>
        <select name="mode" id="a_mode" class="form-select-x" onchange="toggleAnggotaMode()">
          <option value="baru">Daftarkan akun Auditor baru</option>
          <option value="ada">Pilih dari akun Auditor yang sudah ada</option>
        </select>
      </div>

      <div id="a_box_ada" hidden>
        <div class="form-row-x">
          <label>Akun Auditor</label>
          <select name="user_id" class="form-select-x">
            <option value="">-- Pilih Auditor --</option>
            <?php foreach ($auditor_users as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= e($u['nama_lengkap']) ?> (<?= e($u['username']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div id="a_box_baru">
        <div class="form-grid-2">
          <div class="form-row-x">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" id="a_nama" class="form-control-x">
          </div>
          <div class="form-row-x">
            <label>Jabatan</label>
            <input type="text" name="jabatan" id="a_jabatan" class="form-control-x" placeholder="Contoh: PPUPD Ahli Pertama">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-row-x">
            <label>Username</label>
            <input type="text" name="username" id="a_username" class="form-control-x" placeholder="untuk login">
          </div>
          <div class="form-row-x">
            <label>Password</label>
            <input type="text" name="password" id="a_password" class="form-control-x" placeholder="Minimal 6 karakter">
          </div>
        </div>
        <div class="small-muted" style="font-size:12px;">
          <i class="bi bi-shield-lock"></i> Akun dibuat otomatis dengan role <b>Auditor</b> dan status aktif.
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn-x btn-outline-x" onclick="closeAnggotaModal()">Batal</button>
        <button type="submit" class="btn-x btn-primary-x"><i class="bi bi-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
$page_scripts = <<<'JS'
<script>
function openAddAnggota(timId) {
  if (timId) document.getElementById('a_tim').value = timId;
  document.getElementById('anggotaModalOverlay').classList.add('show');
}
function closeAnggotaModal() {
  document.getElementById('anggotaModalOverlay').classList.remove('show');
}
function toggleAnggotaMode() {
  var mode = document.getElementById('a_mode').value;
  document.getElementById('a_box_ada').hidden = (mode !== 'ada');
  document.getElementById('a_box_baru').hidden = (mode !== 'baru');
}
(function () {
  var p = new URLSearchParams(window.location.search);
  if (p.get('tim')) openAddAnggota(p.get('tim'));
})();
</script>
JS;
include __DIR__ . '/includes/footer.php';
?>
