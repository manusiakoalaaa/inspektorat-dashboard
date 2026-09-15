let chartOpdInstance = null;
let chartStatusInstance = null;
let chartJenisInstance = null;

function statusBadgeClass(status) {
  switch (status) {
    case 'Selesai': return 'badge-selesai';
    case 'Proses': return 'badge-proses';
    case 'Tertunda': return 'badge-tertunda';
    default: return 'badge-belummulai';
  }
}
function progresColor(p) {
  if (p >= 100) return '#16a34a';
  if (p >= 50) return '#2f6fed';
  if (p >= 1) return '#f59e0b';
  return '#9ca3af';
}

function buildQuery() {
  const params = new URLSearchParams();
  const tahunEl = document.getElementById('filterTahun');
  if (tahunEl && tahunEl.value) params.set('tahun', tahunEl.value);
  const map = {
    filterJenisReviu: 'jenis_reviu',
    filterStatus: 'status',
    filterOpd: 'opd',
    filterTimReviu: 'tim_reviu'
  };
  Object.keys(map).forEach(function (id) {
    const el = document.getElementById(id);
    if (el && el.value) params.set(map[id], el.value);
  });
  return params.toString();
}

function renderStats(stats) {
  document.getElementById('statTotal').textContent = formatNumber(stats.total);
  document.getElementById('statSelesai').textContent = formatNumber(stats.selesai);
  document.getElementById('statSelesaiPct').textContent = stats.pct_selesai + '% dari total';
  document.getElementById('statProses').textContent = formatNumber(stats.proses);
  document.getElementById('statProsesPct').textContent = stats.pct_proses + '% dari total';
  document.getElementById('statTertunda').textContent = formatNumber(stats.tertunda);
  document.getElementById('statTertundaPct').textContent = stats.pct_tertunda + '% dari total';
  document.getElementById('statRata').textContent = stats.rata_progres + '%';
}

function renderChartOpd(data) {
  const ctx = document.getElementById('chartProgresOpd');
  if (!ctx) return;
  if (chartOpdInstance) chartOpdInstance.destroy();
  chartOpdInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.labels,
      datasets: [{
        label: 'Progres (%)',
        data: data.data,
        backgroundColor: '#2f6fed',
        borderRadius: 5,
        maxBarThickness: 16
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { min: 0, max: 100, ticks: { callback: v => v + '%' }, grid: { color: '#f0f1f6' } },
        y: { grid: { display: false } }
      }
    }
  });
}

function renderChartStatus(data) {
  const ctx = document.getElementById('chartStatusReviu');
  if (!ctx) return;
  if (chartStatusInstance) chartStatusInstance.destroy();
  chartStatusInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.labels,
      datasets: [{ data: data.data, backgroundColor: data.colors, borderWidth: 3, borderColor: '#fff' }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: { legend: { position: 'right', labels: { boxWidth: 10, font: { size: 11 } } } }
    }
  });
}

function renderChartJenis(data) {
  const ctx = document.getElementById('chartJenisReviu');
  if (!ctx) return;
  if (chartJenisInstance) chartJenisInstance.destroy();
  chartJenisInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.labels,
      datasets: [{
        label: 'Rata-rata Progres',
        data: data.data,
        backgroundColor: ['#2f6fed', '#16a34a', '#f59e0b', '#8b5cf6'],
        borderRadius: 6,
        maxBarThickness: 40
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: c => c.formattedValue + '%' } }
      },
      scales: {
        y: { min: 0, max: 100, ticks: { callback: v => v + '%' }, grid: { color: '#f0f1f6' } },
        x: { grid: { display: false } }
      }
    }
  });
}

function renderTable(rows) {
  const tbody = document.getElementById('tabelReviuBody');
  if (!tbody) return;
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 small-muted">Tidak ada data yang sesuai filter.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map(function (r) {
    const dokBadge = r.dokumen_status === 'Lengkap' ? 'badge-lengkap' : 'badge-belumlengkap';
    const kendalaAttr = (r.status === 'Tertunda' && r.kendala) ? ' title="' + escapeHtml(r.kendala) + '"' : '';
    return '<tr>' +
      '<td>' + r.no + '</td>' +
      '<td><b>' + escapeHtml(r.nama_opd) + '</b></td>' +
      '<td>' + escapeHtml(r.nama_jenis) + '</td>' +
      '<td>' + r.tahun + '</td>' +
      '<td>' + escapeHtml(r.nama_tim) + '</td>' +
      '<td>' + r.tgl_mulai_fmt + '</td>' +
      '<td>' + r.tgl_target_fmt + '</td>' +
      '<td><span class="badge-x ' + dokBadge + '">' + escapeHtml(r.dokumen_status) + '</span></td>' +
      '<td><span class="badge-x ' + statusBadgeClass(r.status) + '"' + kendalaAttr + '>' + escapeHtml(r.status) + '</span></td>' +
      '<td>' +
        '<div class="d-flex align-items-center gap-2">' +
          '<div class="progress-thin" style="flex:1;"><div class="bar" style="width:' + r.progres + '%; background:' + progresColor(r.progres) + ';"></div></div>' +
          '<span style="font-weight:700; font-size:12px;">' + r.progres + '%</span>' +
        '</div>' +
      '</td>' +
      '<td class="small-muted">' + escapeHtml(r.keterangan || '-') + '</td>' +
      '<td><a href="reviu_detail.php?id=' + r.id + '" class="btn-eye"><i class="bi bi-eye"></i></a></td>' +
      '</tr>';
  }).join('');
}

function renderJadwal(items) {
  const el = document.getElementById('jadwalList');
  if (!el) return;
  if (!items.length) {
    el.innerHTML = '<div class="small-muted">Belum ada jadwal kegiatan.</div>';
    return;
  }
  const colorMap = { biru: '#2f6fed', kuning: '#f59e0b', hijau: '#16a34a', merah: '#ef4444' };
  el.innerHTML = items.map(function (j) {
    return '<div class="jadwal-item">' +
      '<div class="jadwal-dot" style="background:' + (colorMap[j.warna] || '#2f6fed') + ';"></div>' +
      '<div style="flex:1;">' +
        '<div class="jadwal-title">' + escapeHtml(j.judul) + '</div>' +
        '<div class="jadwal-sub">' + escapeHtml(j.nama_opd || '') + '</div>' +
      '</div>' +
      '<div class="jadwal-date">' + j.tanggal_fmt + '</div>' +
    '</div>';
  }).join('');
}

function renderDokumen(d) {
  document.getElementById('docTotal').textContent = formatNumber(d.total);
  document.getElementById('docLengkap').textContent = formatNumber(d.lengkap);
  document.getElementById('docBelumLengkap').textContent = formatNumber(d.belum_lengkap);
}

function renderEarlyWarning(ew) {
  if (!ew) return;
  const total = (ew.kritis || 0) + (ew.peringatan || 0);

  const banner = document.getElementById('ewBanner');
  if (banner) {
    const title = document.getElementById('ewBannerTitle');
    const sub = document.getElementById('ewBannerSub');
    const btn = document.getElementById('ewBannerBtn');
    if (ew.kritis > 0) {
      banner.hidden = false;
      banner.classList.add('ew-banner-critical');
      if (title) title.textContent = ew.kritis + ' reviu berstatus KRITIS, mendekati tenggat!';
      if (sub) sub.textContent = 'Sisa H-2 atau kurang dan dokumennya belum tuntas. Segera tindak lanjuti.';
      if (btn) btn.classList.add('btn-danger-x');
    } else if (ew.peringatan > 0) {
      banner.hidden = false;
      banner.classList.remove('ew-banner-critical');
      if (title) title.textContent = ew.peringatan + ' reviu mendekati tenggat (H-3 s.d H-7).';
      if (sub) sub.textContent = 'Pantau progresnya supaya tidak terlambat.';
      if (btn) btn.classList.remove('btn-danger-x');
    } else {
      banner.hidden = true;
    }
  }

  const badge = document.getElementById('ewCountBadge');
  if (badge) {
    if (total > 0) { badge.hidden = false; badge.textContent = total + ' berisiko'; }
    else badge.hidden = true;
  }

  const listEl = document.getElementById('ewListWidget');
  if (!listEl) return;
  if (!ew.items || !ew.items.length) {
    listEl.innerHTML = '<div class="small-muted" style="text-align:center; padding:14px 0;"><i class="bi bi-emoji-smile"></i> Tidak ada reviu mendekati tenggat.</div>';
    return;
  }
  listEl.innerHTML = ew.items.map(function (it) {
    const isKritis = it.level === 'kritis';
    const countdown = it.sisa_hari === 0 ? 'Hari ini' : ('H-' + it.sisa_hari);
    const sub = escapeHtml(it.nama_jenis) + (it.dokumen_ditunggu ? ' &middot; Menunggu ' + escapeHtml(it.dokumen_ditunggu) : '');
    return '<div class="ew-mini ' + (isKritis ? 'ew-mini-critical' : 'ew-mini-warning') + '">' +
      '<div class="ew-mini-dot"></div>' +
      '<div class="ew-mini-body">' +
        '<div class="ew-mini-title">' + escapeHtml(it.nama_opd) + '</div>' +
        '<div class="ew-mini-sub">' + sub + '</div>' +
      '</div>' +
      '<a href="reviu_detail.php?id=' + it.id + '" class="ew-mini-badge">' + countdown + '</a>' +
    '</div>';
  }).join('');
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str == null ? '' : str;
  return div.innerHTML;
}

function loadDashboard() {
  const qs = buildQuery();
  return fetch('api/dashboard_data.php?' + qs)
    .then(function (res) { return res.json(); })
    .then(function (json) {
      renderStats(json.stats);
      renderChartOpd(json.chart_opd);
      renderChartStatus(json.chart_status);
      renderChartJenis(json.chart_jenis);
      renderTable(json.table);
      renderJadwal(json.jadwal);
      renderEarlyWarning(json.early_warning);
      renderDokumen(json.dokumen_minggu);
    })
    .catch(function (err) {
      console.error('Gagal memuat data dashboard:', err);
    });
}
window.reloadPageData = loadDashboard;

document.addEventListener('DOMContentLoaded', function () {
  loadDashboard();
  ['filterTahun', 'filterJenisReviu', 'filterStatus', 'filterOpd', 'filterTimReviu'].forEach(function (id) {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', loadDashboard);
  });
});
