<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor', 'pimpinan']);

$page_title = 'Edit Profil';
$page_subtitle = 'Kelola informasi akun, foto profil, dan password Anda';

$user = current_user_data();
$avatar_src = avatar_url($user['avatar']);

include __DIR__ . '/includes/header.php';
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-x text-center">
      <div class="card-x-title" style="justify-content:center;"><i class="bi bi-person-circle"></i> FOTO PROFIL</div>
      <?php if ($avatar_src): ?>
        <img src="<?= e($avatar_src) ?>" class="avatar-lg mx-auto" style="display:block; margin:0 auto;">
      <?php else: ?>
        <div class="avatar-lg mx-auto" style="display:flex; margin:0 auto;"><?= e(initials($user['nama_lengkap'])) ?></div>
      <?php endif; ?>
      <div style="font-weight:800; margin-top:12px; color:var(--navy);"><?= e($user['nama_lengkap']) ?></div>
      <div class="small-muted"><?= e(role_label($user['role_slug'])) ?></div>

      <form method="POST" action="process/process_profile.php" enctype="multipart/form-data" class="mt-3 text-start">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="change_avatar">
        <div class="form-row-x">
          <label>Ganti Foto (JPG/PNG/WEBP, maks 2MB)</label>
          <input type="file" name="avatar" class="form-control-x" accept=".jpg,.jpeg,.png,.webp" required>
        </div>
        <button type="submit" class="btn-x btn-primary-x w-100"><i class="bi bi-upload"></i> Unggah Foto</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card-x mb-3">
      <div class="card-x-title"><i class="bi bi-person-lines-fill"></i> INFORMASI AKUN</div>
      <form method="POST" action="process/process_profile.php">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="update_info">
        <div class="form-grid-2">
          <div class="form-row-x">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control-x" value="<?= e($user['nama_lengkap']) ?>" required>
          </div>
          <div class="form-row-x">
            <label>Jabatan</label>
            <input type="text" name="jabatan" class="form-control-x" value="<?= e($user['jabatan']) ?>">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-row-x">
            <label>Username</label>
            <input type="text" class="form-control-x" value="<?= e($user['username']) ?>" disabled style="background:#f3f4f8;">
          </div>
          <div class="form-row-x">
            <label>Role</label>
            <input type="text" class="form-control-x" value="<?= e(role_label($user['role_slug'])) ?>" disabled style="background:#f3f4f8;">
          </div>
        </div>
        <button type="submit" class="btn-x btn-primary-x"><i class="bi bi-save"></i> Simpan Perubahan</button>
      </form>
    </div>

    <div class="card-x">
      <div class="card-x-title"><i class="bi bi-shield-lock-fill"></i> GANTI PASSWORD</div>
      <form method="POST" action="process/process_profile.php">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="change_password">
        <div class="form-row-x">
          <label>Password Saat Ini</label>
          <input type="password" name="current_password" class="form-control-x" required>
        </div>
        <div class="form-grid-2">
          <div class="form-row-x">
            <label>Password Baru</label>
            <input type="password" name="new_password" class="form-control-x" minlength="6" required>
          </div>
          <div class="form-row-x">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control-x" minlength="6" required>
          </div>
        </div>
        <button type="submit" class="btn-x btn-primary-x"><i class="bi bi-key-fill"></i> Ubah Password</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
