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
