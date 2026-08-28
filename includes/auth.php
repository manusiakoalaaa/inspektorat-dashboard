<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

define('APP_NAME', 'Dashboard Monitoring Progres Reviu OPD');
define('APP_INSTANSI', 'Inspektorat Daerah Kabupaten Labuhanbatu Selatan');

/**
 * Cek apakah user sudah login
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Wajibkan login, kalau belum login lempar ke halaman login
 */
function require_login()
{
    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Ambil slug role user yang sedang login
 */
function current_role()
{
    return isset($_SESSION['role_slug']) ? $_SESSION['role_slug'] : null;
}

/**
 * Wajibkan role tertentu untuk mengakses halaman ini.
 * Jika $allowed_roles kosong, hanya wajib login (semua role boleh).
 */
function require_role($allowed_roles = [])
{
    require_login();
    if (!empty($allowed_roles) && !in_array(current_role(), $allowed_roles, true)) {
        header('Location: ' . base_url('index.php?error=akses_ditolak'));
        exit;
    }
}

/**
 * Helper untuk membangun path relatif dari root aplikasi.
 * Dipakai supaya redirect tetap benar walau dipanggil dari folder /process atau /api
 */
function base_url($path = '')
{
    return ROOT_URL . $path;
}

/**
 * Data user yang sedang login (array asosiatif)
 */
function current_user_data()
{
    global $pdo;
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    if (!is_logged_in()) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT u.*, r.nama_role, r.slug AS role_slug
                            FROM users u JOIN roles r ON r.id = u.role_id
                            WHERE u.id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $cached = $stmt->fetch();
    return $cached;
}

/**
 * Simple CSRF token helper
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf()
{
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Sesi tidak valid (CSRF check gagal). Silakan muat ulang halaman dan coba lagi.');
    }
}

function flash_set($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
