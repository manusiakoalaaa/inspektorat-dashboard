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
    $keterangan  = trim($_POST['keterangan'] ?? '');
    $kendala_mode = ($_POST['kendala_mode'] ?? 'otomatis') === 'manual' ? 'manual' : 'otomatis';
    $kendala_manual_text = trim($_POST['kendala_manual_text'] ?? '');

    if (!$opd_id || !$jenis_id || !$tim_id || !$tgl_mulai || !$tgl_target) {
        flash_set('error', 'Semua field wajib diisi dengan benar.');
        redirect($back);
    }

    if ($form_action === 'add') {
        // Progres, status, dan status dokumen dihitung otomatis dari dokumen yang diunggah.
        $stmt = $pdo->prepare("INSERT INTO reviu
            (opd_id, jenis_reviu_id, tim_reviu_id, tahun, tgl_mulai, tgl_target_selesai, dokumen_status, status, progres, keterangan, created_by)
            VALUES (?,?,?,?,?,?,'Belum Lengkap','Belum Mulai',0,?,?)");
        $stmt->execute([$opd_id, $jenis_id, $tim_id, $tahun, $tgl_mulai, $tgl_target, $keterangan, $_SESSION['user_id']]);
        $id = (int) $pdo->lastInsertId();

        if ($kendala_mode === 'manual') {
            $pdo->prepare("UPDATE reviu SET kendala = ?, kendala_manual = 1 WHERE id = ?")
                ->execute([$kendala_manual_text !== '' ? $kendala_manual_text : null, $id]);
        }
        // Jaga-jaga kalau tanggal target yang diinput sudah lewat dari awal.
        // Kendala manual (jika diisi) tetap dipertahankan oleh recalculate_reviu().
        recalculate_reviu($pdo, $id);
        flash_set('success', 'Data reviu baru berhasil ditambahkan.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE reviu SET opd_id=?, jenis_reviu_id=?, tim_reviu_id=?, tahun=?, tgl_mulai=?, tgl_target_selesai=?, keterangan=? WHERE id=?");
        $stmt->execute([$opd_id, $jenis_id, $tim_id, $tahun, $tgl_mulai, $tgl_target, $keterangan, $id]);

        if ($kendala_mode === 'manual') {
            $pdo->prepare("UPDATE reviu SET kendala = ?, kendala_manual = 1 WHERE id = ?")
                ->execute([$kendala_manual_text !== '' ? $kendala_manual_text : null, $id]);
        } else {
            // Kembali ke mode otomatis -> lepas kunci manual, teks kendala akan dihitung ulang.
            $pdo->prepare("UPDATE reviu SET kendala_manual = 0 WHERE id = ?")->execute([$id]);
        }
        // Sinkronkan kembali progres/status/kendala sesuai mode yang dipilih.
        recalculate_reviu($pdo, $id);
        flash_set('success', 'Data reviu berhasil diperbarui.');
    }
    redirect($back);
}

redirect($back);
