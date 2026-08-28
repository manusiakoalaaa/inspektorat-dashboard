<?php
define('ROOT_URL', '../');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['administrator', 'auditor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pengaturan.php');
}
verify_csrf();

$tables = [
    'opd'         => ['table' => 'opd', 'column' => 'nama_opd'],
    'jenis_reviu' => ['table' => 'jenis_reviu', 'column' => 'nama_jenis'],
    'tim_reviu'   => ['table' => 'tim_reviu', 'column' => 'nama_tim'],
];

$type = $_POST['type'] ?? '';
$tab = in_array($type, ['opd', 'jenis_reviu', 'tim_reviu'], true) ? $type : 'opd';
$back = 'pengaturan.php?tab=' . $tab;

if (!isset($tables[$type])) {
    flash_set('error', 'Jenis data master tidak dikenali.');
    redirect($back);
}
$table = $tables[$type]['table'];
$column = $tables[$type]['column'];
$form_action = $_POST['form_action'] ?? '';
$nama = trim($_POST['nama'] ?? '');

if ($form_action === 'add') {
    if ($nama === '') {
        flash_set('error', 'Nama tidak boleh kosong.');
        redirect($back);
    }
    $stmt = $pdo->prepare("INSERT INTO `$table` (`$column`) VALUES (?)");
    $stmt->execute([$nama]);
    flash_set('success', 'Data berhasil ditambahkan.');
} elseif ($form_action === 'edit') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($nama === '' || !$id) {
        flash_set('error', 'Data tidak valid.');
        redirect($back);
    }
    $stmt = $pdo->prepare("UPDATE `$table` SET `$column` = ? WHERE id = ?");
    $stmt->execute([$nama, $id]);
    flash_set('success', 'Data berhasil diperbarui.');
} elseif ($form_action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Data berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Data tidak dapat dihapus karena masih digunakan pada data reviu.');
    }
}

redirect($back);
