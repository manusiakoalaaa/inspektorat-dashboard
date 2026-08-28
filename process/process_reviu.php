<?php
define('ROOT_URL', '../');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['administrator', 'auditor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('daftar_reviu.php');
}
verify_csrf();

$form_action = $_POST['form_action'] ?? '';
$redirect_qs = isset($_POST['redirect_qs']) ? $_POST['redirect_qs'] : '';
$back = 'daftar_reviu.php' . ($redirect_qs ? ('?' . $redirect_qs) : '');

$valid_status = ['Belum Mulai', 'Proses', 'Selesai', 'Tertunda'];
$valid_dok = ['Lengkap', 'Belum Lengkap'];

if ($form_action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM reviu WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Data reviu berhasil dihapus.');
    }
    redirect($back);
}

if ($form_action === 'add' || $form_action === 'edit') {
    $opd_id      = (int) ($_POST['opd_id'] ?? 0);
    $jenis_id    = (int) ($_POST['jenis_reviu_id'] ?? 0);
    $tim_id      = (int) ($_POST['tim_reviu_id'] ?? 0);
    $tahun       = (int) ($_POST['tahun'] ?? date('Y'));
    $tgl_mulai   = $_POST['tgl_mulai'] ?? null;
    $tgl_target  = $_POST['tgl_target_selesai'] ?? null;
    $dok_status  = in_array($_POST['dokumen_status'] ?? '', $valid_dok, true) ? $_POST['dokumen_status'] : 'Belum Lengkap';
    $status      = in_array($_POST['status'] ?? '', $valid_status, true) ? $_POST['status'] : 'Belum Mulai';
    $progres     = max(0, min(100, (int) ($_POST['progres'] ?? 0)));
    $keterangan  = trim($_POST['keterangan'] ?? '');

    if (!$opd_id || !$jenis_id || !$tim_id || !$tgl_mulai || !$tgl_target) {
        flash_set('error', 'Semua field wajib diisi dengan benar.');
        redirect($back);
    }

    if ($form_action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO reviu
            (opd_id, jenis_reviu_id, tim_reviu_id, tahun, tgl_mulai, tgl_target_selesai, dokumen_status, status, progres, keterangan, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$opd_id, $jenis_id, $tim_id, $tahun, $tgl_mulai, $tgl_target, $dok_status, $status, $progres, $keterangan, $_SESSION['user_id']]);
        flash_set('success', 'Data reviu baru berhasil ditambahkan.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE reviu SET opd_id=?, jenis_reviu_id=?, tim_reviu_id=?, tahun=?, tgl_mulai=?, tgl_target_selesai=?, dokumen_status=?, status=?, progres=?, keterangan=? WHERE id=?");
        $stmt->execute([$opd_id, $jenis_id, $tim_id, $tahun, $tgl_mulai, $tgl_target, $dok_status, $status, $progres, $keterangan, $id]);
        flash_set('success', 'Data reviu berhasil diperbarui.');
    }
    redirect($back);
}

redirect($back);
