<?php
define('ROOT_URL', '../');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['administrator', 'auditor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dokumen.php');
}
verify_csrf();

$form_action = $_POST['form_action'] ?? '';
$reviu_id = (int) ($_POST['reviu_id'] ?? 0);
$back = $reviu_id ? ('reviu_detail.php?id=' . $reviu_id) : 'dokumen.php';

if ($form_action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT file_path FROM dokumen WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    if ($doc && !empty($doc['file_path'])) {
        $full = __DIR__ . '/../uploads/dokumen/' . $doc['file_path'];
        if (file_exists($full)) @unlink($full);
    }
    $pdo->prepare("DELETE FROM dokumen WHERE id = ?")->execute([$id]);
    flash_set('success', 'Dokumen berhasil dihapus.');
    redirect($back);
}

if ($form_action === 'add') {
    $nama = trim($_POST['nama_dokumen'] ?? '');
    $status = ($_POST['status'] ?? '') === 'Lengkap' ? 'Lengkap' : 'Belum Lengkap';

    if ($nama === '' || !$reviu_id) {
        flash_set('error', 'Nama dokumen wajib diisi.');
        redirect($back);
    }

    $file_name = null;
    if (!empty($_FILES['file_dokumen']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_dokumen']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        if (in_array($ext, $allowed, true) && $_FILES['file_dokumen']['error'] === UPLOAD_ERR_OK) {
            $file_name = 'dok_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['file_dokumen']['tmp_name'], __DIR__ . '/../uploads/dokumen/' . $file_name);
        }
    }

    $stmt = $pdo->prepare("INSERT INTO dokumen (reviu_id, nama_dokumen, file_path, status, tanggal_upload) VALUES (?,?,?,?,CURDATE())");
    $stmt->execute([$reviu_id, $nama, $file_name, $status]);

    // Update status dokumen pada reviu induk jika semua dokumen lengkap
    $pdo->prepare("UPDATE reviu SET dokumen_status = ? WHERE id = ?")->execute([$status, $reviu_id]);

    flash_set('success', 'Dokumen berhasil ditambahkan.');
    redirect($back);
}

redirect($back);
