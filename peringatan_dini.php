<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor', 'pimpinan']);
refresh_all_reviu_status($pdo);

$page_title = 'Peringatan Dini';
$page_subtitle = 'Deteksi dini reviu yang mendekati tenggat tapi dokumennya belum tuntas';

$sql = "SELECT r.*, o.nama_opd, j.nama_jenis, t.nama_tim
        FROM reviu r
        JOIN opd o ON o.id = r.opd_id
        JOIN jenis_reviu j ON j.id = r.jenis_reviu_id
        JOIN tim_reviu t ON t.id = r.tim_reviu_id
        WHERE r.progres < 100
        ORDER BY r.tgl_target_selesai ASC";
$rows = $pdo->query($sql)->fetchAll();

$jenis_list = dokumen_jenis_list();
$kritis = [];
$peringatan = [];
$jumlah_lewat = 0;
$jumlah_aman = 0;

foreach ($rows as $r) {
    $sisa = hari_tersisa($r['tgl_target_selesai']);
    $level = reviu_urgency_level($r['progres'], $sisa);
    $r['sisa_hari'] = $sisa;

    $terunggah = dokumen_jenis_terunggah($pdo, $r['id']);
    $next = dokumen_jenis_berikutnya($terunggah);
    $r['dokumen_ditunggu'] = $next !== null ? $jenis_list[$next]['label'] : null;

    if ($level === 'kritis') $kritis[] = $r;
    elseif ($level === 'peringatan') $peringatan[] = $r;
    elseif ($level === 'lewat') $jumlah_lewat++;
    else $jumlah_aman++;
}

include __DIR__ . '/includes/header.php';

function render_ew_card($r, $level)
{
    $isKritis = $level === 'kritis';
    $accent = $isKritis ? '#ef4444' : '#f59e0b';
    $badgeBg = $isKritis ? 'rgba(239,68,68,.12)' : 'rgba(245,158,11,.14)';
    $badgeColor = $isKritis ? '#dc2626' : '#b9770e';
    $sisa = $r['sisa_hari'];
    $countdownText = $sisa === 0 ? 'JATUH TEMPO HARI INI' : ('H-' . $sisa);
    ?>
    <div class="ew-card <?= $isKritis ? 'ew-critical' : 'ew-warning' ?>" style="--ew-accent: <?= $accent ?>;">
      <div class="ew-pulse-wrap">
        <span class="ew-pulse-dot"></span>
      </div>
      <div class="ew-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <div class="ew-opd"><?= e($r['nama_opd']) ?></div>
            <div class="ew-sub"><?= e($r['nama_jenis']) ?> &middot; <?= e($r['nama_tim']) ?> &middot; Tahun <?= e($r['tahun']) ?></div>
          </div>
          <span class="ew-countdown" style="background:<?= $badgeBg ?>; color:<?= $badgeColor ?>;">
            <i class="bi <?= $isKritis ? 'bi-alarm-fill' : 'bi-hourglass-split' ?>"></i> <?= e($countdownText) ?>
          </span>
        </div>

        <div class="d-flex align-items-center gap-2 mt-2">
          <div class="progress-thin" style="flex:1;"><div class="bar" style="width:<?= (int)$r['progres'] ?>%; background:<?= progres_bar_color($r['progres']) ?>;"></div></div>
          <span style="font-weight:700; font-size:12px; min-width:36px; text-align:right;"><?= (int)$r['progres'] ?>%</span>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
          <div class="ew-target"><i class="bi bi-calendar-x"></i> Target selesai: <b><?= format_tanggal_indo($r['tgl_target_selesai']) ?></b></div>
          <?php if ($r['dokumen_ditunggu']): ?>
            <div class="ew-waiting"><i class="bi bi-file-earmark-arrow-up"></i> Menunggu: <b><?= e($r['dokumen_ditunggu']) ?></b></div>
          <?php endif; ?>
          <a href="reviu_detail.php?id=<?= (int)$r['id'] ?>" class="btn-x <?= $isKritis ? 'btn-danger-x' : 'btn-outline-x' ?>" style="padding:6px 14px; font-size:12.5px;">
            <i class="bi bi-eye"></i> Tindak Lanjuti
          </a>
        </div>
      </div>
    </div>
    <?php
}
?>

<div class="ew-hero">
  <div class="ew-hero-icon"><i class="bi bi-shield-exclamation"></i></div>
  <div>
    <div class="ew-hero-title">SISTEM PERINGATAN DINI REVIU</div>
    <div class="ew-hero-sub">Memantau reviu yang mendekati tanggal target selesai namun dokumennya belum lengkap, supaya bisa ditindaklanjuti sebelum terlambat.</div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div style="flex:1; min-width:170px;">
    <div class="stat-card stat-red">
      <div class="stat-icon"><i class="bi bi-alarm-fill"></i></div>
      <div><div class="stat-label">Kritis (&le; H-2)</div><div class="stat-value"><?= count($kritis) ?></div></div>
    </div>
  </div>
  <div style="flex:1; min-width:170px;">
    <div class="stat-card stat-amber">
      <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
      <div><div class="stat-label">Peringatan (H-3 s.d H-7)</div><div class="stat-value"><?= count($peringatan) ?></div></div>
    </div>
  </div>
  <div style="flex:1; min-width:170px;">
    <a href="reviu_tertunda.php" class="stat-card stat-purple" style="text-decoration:none;">
      <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
      <div><div class="stat-label">Sudah Tertunda</div><div class="stat-value"><?= $jumlah_lewat ?></div></div>
    </a>
  </div>
  <div style="flex:1; min-width:170px;">
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
      <div><div class="stat-label">Aman (&gt; H-7)</div><div class="stat-value"><?= $jumlah_aman ?></div></div>
    </div>
  </div>
</div>

<?php if (empty($kritis) && empty($peringatan)): ?>
  <div class="card-x">
    <div class="empty-state">
      <i class="bi bi-emoji-smile"></i>
      Tidak ada reviu yang mendekati tenggat saat ini. Semua dalam kendali. 🎉
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($kritis)): ?>
<div class="ew-section-title ew-section-critical"><i class="bi bi-exclamation-triangle-fill"></i> KRITIS &mdash; SEGERA BERTINDAK</div>
<?php foreach ($kritis as $r) render_ew_card($r, 'kritis'); ?>
<?php endif; ?>

<?php if (!empty($peringatan)): ?>
<div class="ew-section-title ew-section-warning"><i class="bi bi-hourglass-split"></i> PERINGATAN &mdash; PERLU PERHATIAN</div>
<?php foreach ($peringatan as $r) render_ew_card($r, 'peringatan'); ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
