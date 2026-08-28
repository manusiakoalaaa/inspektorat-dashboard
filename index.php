<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['administrator', 'auditor', 'pimpinan']);

$page_title = 'Dashboard Monitoring Progres Reviu OPD';
$page_subtitle = APP_INSTANSI;
$show_year_filter = true;

// Data master untuk dropdown filter (diambil langsung dari DB supaya selalu up to date)
$opd_list = $pdo->query("SELECT id, nama_opd FROM opd ORDER BY nama_opd ASC")->fetchAll();
$jenis_list = $pdo->query("SELECT id, nama_jenis FROM jenis_reviu ORDER BY nama_jenis ASC")->fetchAll();
$tim_list = $pdo->query("SELECT id, nama_tim FROM tim_reviu ORDER BY nama_tim ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Stat cards ===== -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-4 col-xl">
    <div class="stat-card stat-blue">
      <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
      <div>
        <div class="stat-label">Total Reviu</div>
        <div class="stat-value" id="statTotal">-</div>
        <div class="stat-sub">Seluruh Reviu</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl">
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
      <div>
        <div class="stat-label">Selesai</div>
        <div class="stat-value" id="statSelesai">-</div>
        <div class="stat-sub" id="statSelesaiPct">0% dari total</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl">
    <div class="stat-card stat-amber">
      <div class="stat-icon"><i class="bi bi-gear-fill"></i></div>
      <div>
        <div class="stat-label">Dalam Proses</div>
        <div class="stat-value" id="statProses">-</div>
        <div class="stat-sub" id="statProsesPct">0% dari total</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl">
    <div class="stat-card stat-red">
      <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
      <div>
        <div class="stat-label">Tertunda</div>
        <div class="stat-value" id="statTertunda">-</div>
        <div class="stat-sub" id="statTertundaPct">0% dari total</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl">
    <div class="stat-card stat-purple">
      <div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div>
      <div>
        <div class="stat-label">Rata-rata Progres</div>
        <div class="stat-value" id="statRata">-</div>
        <div class="stat-sub">Persentase rata-rata</div>
      </div>
    </div>
  </div>
</div>

<!-- ===== Grid utama: kiri (charts + table) - kanan (filter, jadwal, dokumen) ===== -->
<div class="dash-grid">
  <div class="dash-left">

    <div class="charts-row mb-3">
      <div class="card-x">
        <div class="card-x-title"><i class="bi bi-bar-chart-steps"></i> PROGRES REVIU PER OPD</div>
        <div style="height:280px;"><canvas id="chartProgresOpd"></canvas></div>
      </div>
      <div class="card-x">
        <div class="card-x-title"><i class="bi bi-pie-chart-fill"></i> STATUS REVIU</div>
        <div style="height:280px;"><canvas id="chartStatusReviu"></canvas></div>
      </div>
      <div class="card-x">
        <div class="card-x-title"><i class="bi bi-bar-chart-fill"></i> PROGRES BERDASARKAN JENIS REVIU</div>
        <div style="height:280px;"><canvas id="chartJenisReviu"></canvas></div>
      </div>
    </div>

    <div class="card-x">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="card-x-title mb-0"><i class="bi bi-table"></i> DAFTAR PROGRES REVIU OPD</div>
      </div>
      <div style="overflow-x:auto;">
        <table class="table-x">
          <thead>
            <tr>
              <th>No</th><th>OPD</th><th>Jenis Reviu</th><th>Tahun</th><th>Tim Reviu</th>
              <th>Tgl Mulai</th><th>Tgl Target Selesai</th><th>Dokumen</th><th>Status</th>
              <th style="min-width:140px;">Progres</th><th>Keterangan</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody id="tabelReviuBody">
            <tr><td colspan="12" class="text-center py-4">Memuat data...</td></tr>
          </tbody>
        </table>
      </div>
      <div class="text-end mt-2">
        <a href="rekapitulasi.php" class="small-muted" style="font-weight:700; color:var(--blue);">Lihat Semua Reviu <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>

  </div>

  <div class="dash-right">
    <div class="card-x mb-3">
      <div class="card-x-title"><i class="bi bi-funnel-fill"></i> FILTER</div>
      <div class="filter-group mb-3">
        <label>Jenis Reviu</label>
        <select class="form-select-x" id="filterJenisReviu">
          <option value="">Semua</option>
          <?php foreach ($jenis_list as $j): ?>
            <option value="<?= (int)$j['id'] ?>"><?= e($j['nama_jenis']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group mb-3">
        <label>Status</label>
        <select class="form-select-x" id="filterStatus">
          <option value="">Semua</option>
          <option value="Selesai">Selesai</option>
          <option value="Proses">Proses</option>
          <option value="Tertunda">Tertunda</option>
          <option value="Belum Mulai">Belum Mulai</option>
        </select>
      </div>
      <div class="filter-group mb-3">
        <label>OPD</label>
        <select class="form-select-x" id="filterOpd">
          <option value="">Semua</option>
          <?php foreach ($opd_list as $o): ?>
            <option value="<?= (int)$o['id'] ?>"><?= e($o['nama_opd']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Tim Reviu</label>
        <select class="form-select-x" id="filterTimReviu">
          <option value="">Semua</option>
          <?php foreach ($tim_list as $t): ?>
            <option value="<?= (int)$t['id'] ?>"><?= e($t['nama_tim']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card-x mb-3">
      <div class="card-x-title"><i class="bi bi-calendar-event-fill"></i> JADWAL KEGIATAN</div>
      <div id="jadwalList">
        <div class="small-muted">Memuat jadwal...</div>
      </div>
      <div class="text-end mt-2">
        <a href="laporan.php" class="small-muted" style="font-weight:700; color:var(--blue);">Lihat Selengkapnya <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>

    <div class="card-x">
      <div class="card-x-title"><i class="bi bi-file-earmark-arrow-up-fill"></i> DOKUMEN MASUK (MINGGU INI)</div>
      <div class="doc-summary-num" id="docTotal">-</div>
      <div class="small-muted mb-2">Dokumen</div>
      <div class="doc-summary-row">
        <span class="badge-x badge-lengkap"><i class="bi bi-check-circle"></i> Lengkap</span>
        <b id="docLengkap">-</b>
      </div>
      <div class="doc-summary-row">
        <span class="badge-x badge-belumlengkap"><i class="bi bi-x-circle"></i> Belum Lengkap</span>
        <b id="docBelumLengkap">-</b>
      </div>
      <div class="text-end mt-2">
        <a href="dokumen.php" class="small-muted" style="font-weight:700; color:var(--blue);">Lihat Daftar Dokumen <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>

<style>
.dash-grid{display:grid; grid-template-columns:1fr 300px; gap:18px;}
.charts-row{display:grid; grid-template-columns:1.4fr 1fr 1.1fr; gap:16px;}
@media (max-width:1200px){ .dash-grid{grid-template-columns:1fr;} .charts-row{grid-template-columns:1fr;} }
</style>

<?php
$page_scripts = '<script src="' . base_url('assets/js/dashboard.js') . '"></script>';
include __DIR__ . '/includes/footer.php';
?>
