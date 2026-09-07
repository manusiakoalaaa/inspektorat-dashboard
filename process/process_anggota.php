<?php
define('ROOT_URL', '../');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['administrator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('anggota_tim.php');
}
verify_csrf();

$form_action = $_POST['form_action'] ?? '';
$back = 'anggota_tim.php';

if ($form_action === 'remove') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("DELETE FROM tim_anggota WHERE id = ?")->execute([$id]);
        flash_set('success', 'Anggota berhasil dikeluarkan dari tim.');
    }
    redirect($back);
}

if ($form_action === 'add') {
    $tim_id = (int) ($_POST['tim_reviu_id'] ?? 0);
    $peran  = ($_POST['peran'] ?? 'Anggota') === 'Ketua' ? 'Ketua' : 'Anggota';
    $mode   = $_POST['mode'] ?? 'baru';

    $cek = $pdo->prepare("SELECT id FROM tim_reviu WHERE id = ?");
    $cek->execute([$tim_id]);
    if (!$cek->fetch()) {
        flash_set('error', 'Tim reviu tidak valid.');
        redirect($back);
    }

    $auditor_role_id = (int) $pdo->query("SELECT id FROM roles WHERE slug = 'auditor' LIMIT 1")->fetchColumn();
    if (!$auditor_role_id) {
        flash_set('error', 'Role Auditor tidak ditemukan pada sistem.');
        redirect($back);
    }

    if ($mode === 'ada') {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $u = $pdo->prepare("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND r.slug = 'auditor'");
        $u->execute([$user_id]);
        if (!$u->fetch()) {
            flash_set('error', 'Akun auditor tidak valid.');
            redirect($back);
        }
    } else {
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nama === '' || $username === '' || $password === '') {
            flash_set('error', 'Nama lengkap, username, dan password wajib diisi.');
            redirect($back);
        }
        if (strlen($password) < 6) {
            flash_set('error', 'Password minimal 6 karakter.');
            redirect($back);
        }

        $cekU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $cekU->execute([$username]);
        if ($cekU->fetch()) {
            flash_set('error', 'Username sudah digunakan, silakan pilih yang lain.');
            redirect($back);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, jabatan, role_id, status) VALUES (?,?,?,?,?, 'aktif')");
        $ins->execute([$username, $hash, $nama, $jabatan, $auditor_role_id]);
        $user_id = (int) $pdo->lastInsertId();
    }

    // Cek duplikasi keanggotaan
    $dup = $pdo->prepare("SELECT id FROM tim_anggota WHERE tim_reviu_id = ? AND user_id = ?");
    $dup->execute([$tim_id, $user_id]);
    if ($dup->fetch()) {
        flash_set('error', 'Anggota tersebut sudah tergabung pada tim ini.');
        redirect($back);
    }

    // Hanya boleh ada satu Ketua per tim
    if ($peran === 'Ketua') {
        $pdo->prepare("UPDATE tim_anggota SET peran = 'Anggota' WHERE tim_reviu_id = ? AND peran = 'Ketua'")->execute([$tim_id]);
    }

    $pdo->prepare("INSERT INTO tim_anggota (tim_reviu_id, user_id, peran) VALUES (?,?,?)")
        ->execute([$tim_id, $user_id, $peran]);

    flash_set('success', 'Anggota berhasil ditambahkan ke tim.');
    redirect($back);
}

redirect($back);
