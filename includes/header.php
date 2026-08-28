<?php
/**
 * Include ini dipakai di setiap halaman (setelah require_role()).
 * Variabel yang bisa di-set sebelum include:
 *   $page_title     - judul halaman (default: APP_NAME)
 *   $page_subtitle   - subjudul kecil di bawah judul
 *   $show_year_filter - true untuk tampilkan dropdown TAHUN di topbar
 *   $active_years    - array tahun untuk dropdown (opsional, kalau tidak diisi ambil dari DB)
 */
$page_title = isset($page_title) ? $page_title : APP_NAME;
$page_subtitle = isset($page_subtitle) ? $page_subtitle : APP_INSTANSI;
$show_year_filter = isset($show_year_filter) ? $show_year_filter : false;

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?> - <?= e(APP_INSTANSI) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-wrapper">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h1 class="page-title"><?= e($page_title) ?></h1>
      <div class="page-subtitle"><?= e($page_subtitle) ?></div>
    </div>
    <div class="topbar-right">
      <div class="datetime-box">
        <i class="bi bi-calendar3"></i>
        <span id="liveDatetime">-</span>
      </div>
      <button type="button" class="btn-refresh" id="btnRefreshData">
        <i class="bi bi-arrow-clockwise"></i> Refresh Data
      </button>
      <?php if ($show_year_filter): ?>
      <select class="year-select" id="filterTahun">
        <?php
        $years = [];
        try {
            $years = $pdo->query("SELECT DISTINCT tahun FROM reviu ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $ex) { $years = []; }
        if (empty($years)) $years = [date('Y')];
        foreach ($years as $y): ?>
          <option value="<?= e($y) ?>"><?= e($y) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
    </div>
  </div>

  <div class="content-area">
    <?php if ($flash): ?>
      <div class="alert-x <?= $flash['type'] === 'success' ? 'alert-success-x' : 'alert-danger-x' ?>">
        <?= e($flash['message']) ?>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'akses_ditolak'): ?>
      <div class="alert-x alert-danger-x">Anda tidak memiliki akses ke halaman tersebut.</div>
    <?php endif; ?>
