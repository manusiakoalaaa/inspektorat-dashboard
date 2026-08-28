<?php
define('ROOT_URL', '../');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['administrator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('user_management.php');
}
verify_csrf();

$form_action = $_POST['form_action'] ?? '';
$back = 'user_management.php';

if ($form_action === 'add') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $role_id = (int) ($_POST['role_id'] ?? 0);
    $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    if ($username === '' || $password === '' || $nama === '' || !$role_id) {
        flash_set('error', 'Semua field wajib diisi.');
        redirect($back);
    }
    if (strlen($password) < 6) {
        flash_set('error', 'Password minimal 6 karakter.');
        redirect($back);
    }

    $cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $cek->execute([$username]);
    if ($cek->fetch()) {
        flash_set('error', 'Username sudah digunakan, silakan pilih username lain.');
        redirect($back);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, jabatan, role_id, status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$username, $hash, $nama, $jabatan, $role_id, $status]);
    flash_set('success', 'User baru berhasil ditambahkan.');
    redirect($back);
}

if ($form_action === 'edit') {
    $id = (int) ($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $role_id = (int) ($_POST['role_id'] ?? 0);
    $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
    $password = $_POST['password'] ?? '';

    if (!$id || $username === '' || $nama === '' || !$role_id) {
        flash_set('error', 'Data tidak valid.');
        redirect($back);
    }

    $cek = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $cek->execute([$username, $id]);
    if ($cek->fetch()) {
        flash_set('error', 'Username sudah digunakan oleh user lain.');
        redirect($back);
    }

    if ($password !== '') {
        if (strlen($password) < 6) {
            flash_set('error', 'Password minimal 6 karakter.');
            redirect($back);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username=?, nama_lengkap=?, jabatan=?, role_id=?, status=?, password=? WHERE id=?");
        $stmt->execute([$username, $nama, $jabatan, $role_id, $status, $hash, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username=?, nama_lengkap=?, jabatan=?, role_id=?, status=? WHERE id=?");
        $stmt->execute([$username, $nama, $jabatan, $role_id, $status, $id]);
    }
    flash_set('success', 'Data user berhasil diperbarui.');
    redirect($back);
}

if ($form_action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === (int) $_SESSION['user_id']) {
        flash_set('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        redirect($back);
    }
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    flash_set('success', 'User berhasil dihapus.');
    redirect($back);
}

redirect($back);
