<?php
/**
 * Kumpulan fungsi bantu (formatting, badge, dsb) yang dipakai di banyak halaman
 */

function e($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function format_tanggal_indo($date)
{
    if (empty($date) || $date === '0000-00-00') return '-';
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($date);
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function status_badge_class($status)
{
    switch ($status) {
        case 'Selesai':      return 'badge-selesai';
        case 'Proses':       return 'badge-proses';
        case 'Tertunda':     return 'badge-tertunda';
        case 'Belum Mulai':  return 'badge-belummulai';
        default:             return 'badge-secondary';
    }
}

function status_dot_color($status)
{
    switch ($status) {
        case 'Selesai':      return '#16a34a';
        case 'Proses':       return '#f59e0b';
        case 'Tertunda':     return '#ef4444';
        case 'Belum Mulai':  return '#9ca3af';
        default:             return '#9ca3af';
    }
}

function dokumen_badge_class($status)
{
    return $status === 'Lengkap' ? 'badge-lengkap' : 'badge-belumlengkap';
}

function progres_bar_color($progres)
{
    if ($progres >= 100) return '#16a34a';
    if ($progres >= 50) return '#2f6fed';
    if ($progres >= 1) return '#f59e0b';
    return '#9ca3af';
}

function role_label($slug)
{
    $map = [
        'administrator' => 'Administrator',
        'auditor'       => 'Auditor',
        'pimpinan'      => 'Pimpinan',
    ];
    return isset($map[$slug]) ? $map[$slug] : ucfirst($slug);
}

function avatar_url($avatar)
{
    if (!empty($avatar) && file_exists(__DIR__ . '/../uploads/avatars/' . $avatar)) {
        return base_url('uploads/avatars/' . $avatar);
    }
    return null;
}

function initials($name)
{
    // Sengaja tidak memakai fungsi mb_* supaya tetap jalan meski ekstensi
    // mbstring tidak aktif di server (banyak hosting PHP dasar mematikannya).
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') {
            $initials .= strtoupper(substr($p, 0, 1));
        }
    }
    return $initials !== '' ? $initials : 'U';
}

function redirect($path)
{
    header('Location: ' . base_url($path));
    exit;
}

/**
 * Daftar jenis dokumen reviu beserta urutan wajib unggah dan bobot progres.
 * Urutan: SPT -> Pemeriksaan -> KKR -> LHP (masing-masing menaikkan progres 25%).
 */
function dokumen_jenis_list()
{
    return [
        'SPT'         => ['urutan' => 1, 'label' => 'SPT (Surat Perintah Tugas)',       'singkat' => 'SPT',        'progres' => 25],
        'Pemeriksaan' => ['urutan' => 2, 'label' => 'Dokumen Pemeriksaan',               'singkat' => 'Pemeriksaan', 'progres' => 50],
        'KKR'         => ['urutan' => 3, 'label' => 'KKR (Kertas Kerja Reviu)',          'singkat' => 'KKR',        'progres' => 75],
        'LHP'         => ['urutan' => 4, 'label' => 'LHP (Laporan Hasil Pemeriksaan)',   'singkat' => 'LHP',        'progres' => 100],
    ];
}

/**
 * Kembalikan array key jenis dokumen yang sudah punya minimal 1 file pada reviu tsb.
 */
function dokumen_jenis_terunggah($pdo, $reviu_id)
{
    $stmt = $pdo->prepare("SELECT DISTINCT jenis FROM dokumen WHERE reviu_id = ?");
    $stmt->execute([(int) $reviu_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Jenis dokumen berikutnya yang boleh diunggah (mengikuti urutan wajib).
 * Return null jika seluruh dokumen sudah lengkap.
 */
function dokumen_jenis_berikutnya($terunggah)
{
    foreach (dokumen_jenis_list() as $key => $cfg) {
        if (!in_array($key, $terunggah, true)) {
            return $key;
        }
    }
    return null;
}

/**
 * Cek apakah sebuah jenis dokumen sudah boleh diunggah:
 * semua jenis dengan urutan lebih kecil harus sudah terunggah.
 */
function dokumen_jenis_terbuka($key, $terunggah)
{
    $list = dokumen_jenis_list();
    if (!isset($list[$key])) return false;
    foreach ($list as $k => $cfg) {
        if ($cfg['urutan'] >= $list[$key]['urutan']) break;
        if (!in_array($k, $terunggah, true)) return false;
    }
    return true;
}

/**
 * Hitung ulang progres, status reviu, dan status dokumen berdasarkan
 * dokumen yang sudah diunggah, lalu simpan ke tabel reviu.
 */
function recalculate_reviu($pdo, $reviu_id)
{
    $terunggah = dokumen_jenis_terunggah($pdo, $reviu_id);

    $progres = 0;
    foreach (dokumen_jenis_list() as $key => $cfg) {
        if (in_array($key, $terunggah, true)) {
            $progres = $cfg['progres'];
        } else {
            break; // berhenti pada urutan pertama yang belum diunggah
        }
    }

    if ($progres >= 100)      $status = 'Selesai';
    elseif ($progres > 0)     $status = 'Proses';
    else                      $status = 'Belum Mulai';

    // Status dokumen menjadi "Lengkap" setelah Dokumen Pemeriksaan diunggah.
    $dok_status = in_array('Pemeriksaan', $terunggah, true) ? 'Lengkap' : 'Belum Lengkap';

    $stmt = $pdo->prepare("UPDATE reviu SET progres = ?, status = ?, dokumen_status = ? WHERE id = ?");
    $stmt->execute([$progres, $status, $dok_status, (int) $reviu_id]);
}
