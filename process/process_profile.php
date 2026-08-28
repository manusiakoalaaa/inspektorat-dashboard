<?php
define('ROOT_URL', '../');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profile.php');
}
verify_csrf();

$form_action = $_POST['form_action'] ?? '';
$user_id = (int) $_SESSION['user_id'];
$back = 'profile.php';

if ($form_action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($current, $u['password'])) {
        flash_set('error', 'Password saat ini salah.');
        redirect($back);
    }
    if (strlen($new) < 6) {
        flash_set('error', 'Password baru minimal 6 karakter.');
        redirect($back);
    }
    if ($new !== $confirm) {
        flash_set('error', 'Konfirmasi password baru tidak cocok.');
        redirect($back);
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
    flash_set('success', 'Password berhasil diubah.');
    redirect($back);
}

if ($form_action === 'change_avatar') {
    if (empty($_FILES['avatar']['name']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        flash_set('error', 'Silakan pilih file foto terlebih dahulu.');
        redirect($back);
    }
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        flash_set('error', 'Format file harus JPG, PNG, atau WEBP.');
        redirect($back);
    }
    if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
        flash_set('error', 'Ukuran file maksimal 2MB.');
        redirect($back);
    }

    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old = $stmt->fetchColumn();

    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/../uploads/avatars/' . $filename)) {
        $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$filename, $user_id]);
        if ($old && file_exists(__DIR__ . '/../uploads/avatars/' . $old)) {
            @unlink(__DIR__ . '/../uploads/avatars/' . $old);
        }
        flash_set('success', 'Foto profil berhasil diperbarui.');
    } else {
        flash_set('error', 'Gagal mengunggah file.');
    }
    redirect($back);
}

if ($form_action === 'update_info') {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    if ($nama === '') {
        flash_set('error', 'Nama lengkap wajib diisi.');
        redirect($back);
    }
    $pdo->prepare("UPDATE users SET nama_lengkap = ?, jabatan = ? WHERE id = ?")->execute([$nama, $jabatan, $user_id]);
    $_SESSION['nama'] = $nama;
    flash_set('success', 'Informasi profil berhasil diperbarui.');
    redirect($back);
}

redirect($back);
