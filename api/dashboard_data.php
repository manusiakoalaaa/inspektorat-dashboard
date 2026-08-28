<?php
define('ROOT_URL', '../');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json');

$tahun     = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int) $_GET['tahun'] : null;
$jenis_id  = isset($_GET['jenis_reviu']) && $_GET['jenis_reviu'] !== '' ? (int) $_GET['jenis_reviu'] : null;
$status    = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$opd_id    = isset($_GET['opd']) && $_GET['opd'] !== '' ? (int) $_GET['opd'] : null;
$tim_id    = isset($_GET['tim_reviu']) && $_GET['tim_reviu'] !== '' ? (int) $_GET['tim_reviu'] : null;

$where = [];
$params = [];
if ($tahun)    { $where[] = 'r.tahun = ?';          $params[] = $tahun; }
if ($jenis_id) { $where[] = 'r.jenis_reviu_id = ?';  $params[] = $jenis_id; }
if ($status)   { $where[] = 'r.status = ?';          $params[] = $status; }
if ($opd_id)   { $where[] = 'r.opd_id = ?';          $params[] = $opd_id; }
if ($tim_id)   { $where[] = 'r.tim_reviu_id = ?';    $params[] = $tim_id; }
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ===== Statistik utama =====
$sql = "SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) AS selesai,
          SUM(CASE WHEN status = 'Proses' THEN 1 ELSE 0 END) AS proses,
          SUM(CASE WHEN status = 'Tertunda' THEN 1 ELSE 0 END) AS tertunda,
          SUM(CASE WHEN status = 'Belum Mulai' THEN 1 ELSE 0 END) AS belum_mulai,
          COALESCE(AVG(progres),0) AS rata_progres
        FROM reviu r $where_sql";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stats = $stmt->fetch();
$total = (int) $stats['total'];

$pct = function ($n) use ($total) {
    return $total > 0 ? round(($n / $total) * 100) : 0;
};

// ===== Chart: progres rata-rata per OPD =====
$sql = "SELECT o.nama_opd, COALESCE(AVG(r.progres),0) AS avg_progres
        FROM opd o
        LEFT JOIN reviu r ON r.opd_id = o.id " .
        ($where ? str_replace('WHERE', 'AND', $where_sql) : '') . "
        GROUP BY o.id, o.nama_opd
        ORDER BY o.nama_opd ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$chart_opd = ['labels' => [], 'data' => []];
foreach ($rows as $r) {
    $chart_opd['labels'][] = $r['nama_opd'];
    $chart_opd['data'][] = round((float) $r['avg_progres']);
}

// ===== Chart: status reviu (donut) =====
$chart_status = [
    'labels' => ['Selesai', 'Proses', 'Tertunda', 'Belum Mulai'],
    'data'   => [(int) $stats['selesai'], (int) $stats['proses'], (int) $stats['tertunda'], (int) $stats['belum_mulai']],
    'colors' => ['#16a34a', '#f59e0b', '#ef4444', '#9ca3af'],
];

// ===== Chart: progres berdasarkan jenis reviu =====
$sql = "SELECT j.nama_jenis, COALESCE(AVG(r.progres),0) AS avg_progres
        FROM jenis_reviu j
        LEFT JOIN reviu r ON r.jenis_reviu_id = j.id " .
        ($where ? str_replace('WHERE', 'AND', $where_sql) : '') . "
        GROUP BY j.id, j.nama_jenis
        ORDER BY j.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$chart_jenis = ['labels' => [], 'data' => []];
foreach ($rows as $r) {
    $chart_jenis['labels'][] = $r['nama_jenis'];
    $chart_jenis['data'][] = round((float) $r['avg_progres']);
}

// ===== Tabel daftar progres reviu =====
$sql = "SELECT r.id, o.nama_opd, j.nama_jenis, r.tahun, t.nama_tim, r.tgl_mulai, r.tgl_target_selesai,
               r.dokumen_status, r.status, r.progres, r.keterangan
        FROM reviu r
        JOIN opd o ON o.id = r.opd_id
        JOIN jenis_reviu j ON j.id = r.jenis_reviu_id
        JOIN tim_reviu t ON t.id = r.tim_reviu_id
        $where_sql
        ORDER BY r.id DESC
        LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$table = $stmt->fetchAll();
$no = 1;
foreach ($table as &$row) {
    $row['no'] = $no++;
    $row['tgl_mulai_fmt'] = format_tanggal_indo($row['tgl_mulai']);
    $row['tgl_target_fmt'] = format_tanggal_indo($row['tgl_target_selesai']);
}
unset($row);

// ===== Jadwal kegiatan terdekat =====
$jadwal_where = $tahun ? 'WHERE YEAR(jk.tanggal) = ?' : '';
$jadwal_params = $tahun ? [$tahun] : [];
$sql = "SELECT jk.judul, jk.tanggal, jk.warna, o.nama_opd
        FROM jadwal_kegiatan jk
        LEFT JOIN opd o ON o.id = jk.opd_id
        $jadwal_where
        ORDER BY jk.tanggal ASC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute($jadwal_params);
$jadwal = $stmt->fetchAll();
foreach ($jadwal as &$j) {
    $j['tanggal_fmt'] = format_tanggal_indo($j['tanggal']);
}
unset($j);

// ===== Dokumen masuk minggu ini =====
$sql = "SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN status = 'Lengkap' THEN 1 ELSE 0 END) AS lengkap,
          SUM(CASE WHEN status = 'Belum Lengkap' THEN 1 ELSE 0 END) AS belum_lengkap
        FROM dokumen
        WHERE YEARWEEK(tanggal_upload, 1) = YEARWEEK(CURDATE(), 1)";
$stmt = $pdo->query($sql);
$doc = $stmt->fetch();

echo json_encode([
    'stats' => [
        'total'        => $total,
        'selesai'      => (int) $stats['selesai'],
        'proses'       => (int) $stats['proses'],
        'tertunda'     => (int) $stats['tertunda'],
        'belum_mulai'  => (int) $stats['belum_mulai'],
        'rata_progres' => round((float) $stats['rata_progres']),
        'pct_selesai'  => $pct((int) $stats['selesai']),
        'pct_proses'   => $pct((int) $stats['proses']),
        'pct_tertunda' => $pct((int) $stats['tertunda']),
    ],
    'chart_opd'    => $chart_opd,
    'chart_status' => $chart_status,
    'chart_jenis'  => $chart_jenis,
    'table'        => $table,
    'jadwal'       => $jadwal,
    'dokumen_minggu' => [
        'total'         => (int) ($doc['total'] ?? 0),
        'lengkap'       => (int) ($doc['lengkap'] ?? 0),
        'belum_lengkap' => (int) ($doc['belum_lengkap'] ?? 0),
    ],
], JSON_UNESCAPED_UNICODE);
