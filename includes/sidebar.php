<?php
$__user = current_user_data();
$__role = current_role();
$__current_page = basename($_SERVER['PHP_SELF']);

$menu_items = [
  ['label' => 'Beranda',         'icon' => 'bi-house-door-fill',            'link' => 'index.php',           'roles' => ['administrator', 'auditor', 'pimpinan']],
  ['label' => 'Rekapitulasi',    'icon' => 'bi-bar-chart-line-fill',        'link' => 'rekapitulasi.php',    'roles' => ['administrator', 'auditor', 'pimpinan']],
  ['label' => 'Daftar Reviu',    'icon' => 'bi-file-earmark-text-fill',     'link' => 'daftar_reviu.php',    'roles' => ['administrator', 'auditor']],
  ['label' => 'Progres OPD',     'icon' => 'bi-bank2',                      'link' => 'progres_opd.php',     'roles' => ['administrator', 'auditor']],
  ['label' => 'Reviu Tertunda',  'icon' => 'bi-clock-history',              'link' => 'reviu_tertunda.php',  'roles' => ['administrator', 'auditor']],
  ['label' => 'Dokumen',         'icon' => 'bi-folder-fill',                'link' => 'dokumen.php',         'roles' => ['administrator', 'auditor']],
  ['label' => 'Laporan',         'icon' => 'bi-file-earmark-bar-graph-fill', 'link' => 'laporan.php',        'roles' => ['administrator', 'auditor']],
  ['label' => 'Manajemen User',  'icon' => 'bi-people-fill',                'link' => 'user_management.php', 'roles' => ['administrator']],
  ['label' => 'Pengaturan',      'icon' => 'bi-gear-fill',                  'link' => 'pengaturan.php',      'roles' => ['administrator', 'auditor']],
];

$__avatar = avatar_url($__user['avatar']);
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <img src="<?= e(base_url('assets/images/logo-kabupaten.png')) ?>" alt="Logo Kabupaten Labuhanbatu Selatan"
            class="logo-emblem" onerror="this.style.display='none'">
        <div class="brand-text">
            <div class="t1">INSPEKTORAT DAERAH</div>
            <div class="t1">KABUPATEN</div>
            <div class="t2">LABUHANBATU SELATAN</div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <?php foreach ($menu_items as $item):
      if (!in_array($__role, $item['roles'], true)) continue;
      $is_active = $__current_page === $item['link'];
    ?>
        <a href="<?= e(base_url($item['link'])) ?>" class="menu-item <?= $is_active ? 'active' : '' ?>">
            <i class="bi <?= e($item['icon']) ?>"></i>
            <span><?= e($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-user">
        <div class="user-popup" id="userPopup">
            <a href="<?= e(base_url('profile.php')) ?>"><i class="bi bi-person-gear"></i> Edit Profil</a>
            <hr>
            <a href="<?= e(base_url('logout.php')) ?>" class="text-danger"
                onclick="return confirm('Yakin ingin logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
        <div class="user-row" id="userRowToggle">
            <?php if ($__avatar): ?>
            <img src="<?= e($__avatar) ?>" class="avatar-sm" alt="avatar">
            <?php else: ?>
            <div class="avatar-sm"><?= e(initials($__user['nama_lengkap'])) ?></div>
            <?php endif; ?>
            <div class="user-info">
                <div class="u-name"><?= e($__user['nama_lengkap']) ?></div>
                <div class="u-role"><?= e($__user['jabatan'] ? $__user['jabatan'] : role_label($__role)) ?></div>
            </div>
            <i class="bi bi-chevron-up caret"></i>
        </div>
    </div>
</aside>
<button class="btn-refresh d-lg-none" id="btnMobileSidebar" style="position:fixed; top:14px; left:14px; z-index:1100;">
    <i class="bi bi-list"></i>
</button>