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
    $stmt = $pdo->prepare("SELECT file_path, reviu_id FROM dokumen WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    if ($doc) {
        if (!empty($doc['file_path'])) {
            $full = __DIR__ . '/../uploads/dokumen/' . $doc['file_path'];
            if (file_exists($full)) @unlink($full);
        }
        $pdo->prepare("DELETE FROM dokumen WHERE id = ?")->execute([$id]);
        recalculate_reviu($pdo, $doc['reviu_id']);
    }
    flash_set('success', 'Dokumen berhasil dihapus.');
    redirect($back);
}

if ($form_action === 'add') {
    $jenis_list = dokumen_jenis_list();
    $jenis = $_POST['jenis'] ?? '';

    if (!$reviu_id || !isset($jenis_list[$jenis])) {
        flash_set('error', 'Jenis dokumen tidak valid.');
        redirect($back);
    }

    // Pastikan reviu ada
    $cek = $pdo->prepare("SELECT id FROM reviu WHERE id = ?");
    $cek->execute([$reviu_id]);
    if (!$cek->fetch()) {
        flash_set('error', 'Data reviu tidak ditemukan.');
        redirect('dokumen.php');
    }

    // Enforce urutan unggah: jenis sebelumnya harus sudah ada
    $terunggah = dokumen_jenis_terunggah($pdo, $reviu_id);
    if (!dokumen_jenis_terbuka($jenis, $terunggah)) {
        flash_set('error', 'Dokumen "' . $jenis_list[$jenis]['label'] . '" belum dapat diunggah. Selesaikan dokumen pada urutan sebelumnya terlebih dahulu.');
        redirect($back);
    }

    // File wajib diunggah (progres dihitung dari file yang masuk)
    if (empty($_FILES['file_dokumen']['name']) || $_FILES['file_dokumen']['error'] !== UPLOAD_ERR_OK) {
        flash_set('error', 'File dokumen wajib diunggah.');
        redirect($back);
    }

    $ext = strtolower(pathinfo($_FILES['file_dokumen']['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed, true)) {
        flash_set('error', 'Format file tidak didukung. Gunakan PDF, Word, Excel, atau gambar.');
        redirect($back);
    }

    $file_name = 'dok_' . $jenis . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($_FILES['file_dokumen']['tmp_name'], __DIR__ . '/../uploads/dokumen/' . $file_name)) {
        flash_set('error', 'Gagal menyimpan file. Coba lagi.');
        redirect($back);
    }

    $nama = trim($_POST['nama_dokumen'] ?? '');
    if ($nama === '') {
        $nama = $jenis_list[$jenis]['label'];
    }

    $stmt = $pdo->prepare("INSERT INTO dokumen (reviu_id, jenis, nama_dokumen, file_path, status, tanggal_upload) VALUES (?,?,?,?,?,CURDATE())");
    $stmt->execute([$reviu_id, $jenis, $nama, $file_name, 'Lengkap']);

    // Perbarui progres, status reviu, dan status dokumen secara otomatis
    recalculate_reviu($pdo, $reviu_id);

    flash_set('success', 'Dokumen "' . $jenis_list[$jenis]['singkat'] . '" berhasil diunggah. Progres reviu diperbarui otomatis.');
    redirect($back);
}

redirect($back);
