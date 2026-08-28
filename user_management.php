<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator']);

$page_title = 'Manajemen User';
$page_subtitle = 'Kelola pengguna sistem dan hak akses (role)';

$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
$users = $pdo->query("SELECT u.*, r.nama_role, r.slug AS role_slug
                       FROM users u JOIN roles r ON r.id = u.role_id
                       ORDER BY u.id ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="card-x mb-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="small-muted">Total <b><?= count($users) ?></b> pengguna terdaftar pada sistem.</div>
    <button type="button" class="btn-x btn-primary-x" onclick="openAddUser()"><i class="bi bi-person-plus-fill"></i> Tambah User</button>
  </div>
</div>

<div class="card-x">
  <div class="card-x-title"><i class="bi bi-people-fill"></i> DAFTAR USER</div>
  <div style="overflow-x:auto;">
    <table class="table-x">
      <thead><tr><th>No</th><th>Avatar</th><th>Nama Lengkap</th><th>Username</th><th>Jabatan</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="8" class="text-center py-4 small-muted">Belum ada user.</td></tr>
        <?php else: $no=1; foreach ($users as $u): $av = avatar_url($u['avatar']); ?>
        <tr>
          <td><?= $no++ ?></td>
          <td>
            <?php if ($av): ?>
              <img src="<?= e($av) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
            <?php else: ?>
              <div class="avatar-sm" style="background:var(--blue);"><?= e(initials($u['nama_lengkap'])) ?></div>
            <?php endif; ?>
          </td>
          <td><b><?= e($u['nama_lengkap']) ?></b></td>
          <td><?= e($u['username']) ?></td>
          <td><?= e($u['jabatan'] ?: '-') ?></td>
          <td>
            <span class="badge-x <?= $u['role_slug']==='administrator'?'badge-tertunda':($u['role_slug']==='auditor'?'badge-proses':'badge-selesai') ?>">
              <?= e($u['nama_role']) ?>
            </span>
          </td>
          <td>
            <?php if ($u['status'] === 'aktif'): ?>
              <span class="badge-x badge-lengkap">Aktif</span>
            <?php else: ?>
              <span class="badge-x badge-belumlengkap">Nonaktif</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <button type="button" class="btn-eye" style="background:var(--amber-light); color:#b9770e;" title="Edit"
                onclick='openEditUser(<?= json_encode([
                    "id"=>$u["id"], "username"=>$u["username"], "nama_lengkap"=>$u["nama_lengkap"],
                    "jabatan"=>$u["jabatan"], "role_id"=>$u["role_id"], "status"=>$u["status"],
                ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <i class="bi bi-pencil"></i>
              </button>
              <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
              <form method="POST" action="process/process_user.php" onsubmit="return confirm('Yakin hapus user <?= e(addslashes($u['nama_lengkap'])) ?>?');" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn-eye" style="background:var(--red-light); color:var(--red);" title="Hapus"><i class="bi bi-trash"></i></button>
              </form>
              <?php else: ?>
                <span class="small-muted" style="padding:6px 8px;">Anda</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah/Edit User -->
<div class="modal-x-overlay" id="userModalOverlay">
  <div class="modal-x">
    <div class="modal-x-header">
      <h3 id="userModalTitle">Tambah User</h3>
      <button type="button" class="modal-x-close" onclick="closeUserModal()">&times;</button>
    </div>
    <form method="POST" action="process/process_user.php">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" id="u_form_action" value="add">
      <input type="hidden" name="id" id="u_id" value="">

      <div class="form-grid-2">
        <div class="form-row-x">
          <label>Nama Lengkap</label>
          <input type="text" name="nama_lengkap" id="u_nama" class="form-control-x" required>
        </div>
        <div class="form-row-x">
          <label>Jabatan</label>
          <input type="text" name="jabatan" id="u_jabatan" class="form-control-x" placeholder="Contoh: PPUPD Ahli Pertama">
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row-x">
          <label>Username</label>
          <input type="text" name="username" id="u_username" class="form-control-x" required>
        </div>
        <div class="form-row-x">
          <label id="u_password_label">Password</label>
          <input type="password" name="password" id="u_password" class="form-control-x" placeholder="Minimal 6 karakter">
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-row-x">
          <label>Role</label>
          <select name="role_id" id="u_role_id" class="form-select-x" required>
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['id'] ?>"><?= e($r['nama_role']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row-x">
          <label>Status</label>
          <select name="status" id="u_status" class="form-select-x">
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
          </select>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn-x btn-outline-x" onclick="closeUserModal()">Batal</button>
        <button type="submit" class="btn-x btn-primary-x"><i class="bi bi-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
$page_scripts = <<<'JS'
<script>
function openAddUser() {
  document.getElementById('userModalTitle').textContent = 'Tambah User';
  document.getElementById('u_form_action').value = 'add';
  document.getElementById('u_id').value = '';
  document.getElementById('u_nama').value = '';
  document.getElementById('u_jabatan').value = '';
  document.getElementById('u_username').value = '';
  document.getElementById('u_password').value = '';
  document.getElementById('u_password').required = true;
  document.getElementById('u_password_label').textContent = 'Password';
  document.getElementById('u_role_id').selectedIndex = 0;
  document.getElementById('u_status').value = 'aktif';
  document.getElementById('userModalOverlay').classList.add('show');
}
function openEditUser(d) {
  document.getElementById('userModalTitle').textContent = 'Edit User';
  document.getElementById('u_form_action').value = 'edit';
  document.getElementById('u_id').value = d.id;
  document.getElementById('u_nama').value = d.nama_lengkap;
  document.getElementById('u_jabatan').value = d.jabatan || '';
  document.getElementById('u_username').value = d.username;
  document.getElementById('u_password').value = '';
  document.getElementById('u_password').required = false;
  document.getElementById('u_password_label').textContent = 'Password (kosongkan jika tidak diubah)';
  document.getElementById('u_role_id').value = d.role_id;
  document.getElementById('u_status').value = d.status;
  document.getElementById('userModalOverlay').classList.add('show');
}
function closeUserModal() {
  document.getElementById('userModalOverlay').classList.remove('show');
}
</script>
JS;
include __DIR__ . '/includes/footer.php';
?>
