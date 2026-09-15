<?php
define('ROOT_URL', '');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT u.*, r.slug AS role_slug, r.nama_role
                                FROM users u JOIN roles r ON r.id = u.role_id
                                WHERE u.username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Username atau password salah.';
        } elseif ($user['status'] !== 'aktif') {
            $error = 'Akun Anda tidak aktif. Hubungi Administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['role_slug'] = $user['role_slug'];
            $_SESSION['nama']      = $user['nama_lengkap'];
            redirect('index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - <?= e(APP_INSTANSI) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">
    <div class="login-emblem">ID</div>
    <div class="login-title">DASHBOARD MONITORING PROGRES REVIU OPD</div>
    <div class="login-sub"><?= e(APP_INSTANSI) ?></div>

    <form method="POST" autocomplete="off">
      <?= csrf_field() ?>
      <label class="login-label">Username</label>
      <input type="text" name="username" class="login-input" placeholder="Masukkan username" required autofocus>

      <label class="login-label">Password</label>
      <input type="password" name="password" class="login-input" placeholder="Masukkan password" required>

      <button type="submit" class="btn-login">Masuk <i class="bi bi-box-arrow-in-right"></i></button>
    </form>

    <div class="small-muted text-center mt-3" style="text-align:center; margin-top:16px;">
      &copy; <?= date('Y') ?> Inspektorat Daerah Kabupaten Labuhanbatu Selatan
    </div>
  </div>
</div>
<?php if ($error): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  Swal.fire({
    icon: 'error',
    title: 'Gagal Masuk',
    text: <?= json_encode($error, JSON_UNESCAPED_UNICODE) ?>,
    confirmButtonText: 'Coba Lagi',
    confirmButtonColor: '#2f6fed',
  });
});
</script>
<?php endif; ?>
<?php if (isset($_GET['logout'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  Swal.fire({
    icon: 'success',
    title: 'Berhasil Logout',
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
  });
});
</script>
<?php endif; ?>
</body>
</html>
