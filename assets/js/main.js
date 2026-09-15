// ===== Live clock (WIB) =====
function updateLiveClock() {
  const el = document.getElementById('liveDatetime');
  if (!el) return;
  const hari = ['Minggu','Senin','Selasa','Rabu','Kamis',"Jum'at",'Sabtu'];
  const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  const now = new Date();
  const teks = hari[now.getDay()] + ', ' + now.getDate() + ' ' + bulan[now.getMonth()] + ' ' + now.getFullYear()
    + ' ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0') + ' WIB';
  el.textContent = teks;
}
setInterval(updateLiveClock, 1000);
document.addEventListener('DOMContentLoaded', updateLiveClock);

// ===== User popup (bottom sidebar) =====
document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('userRowToggle');
  const popup = document.getElementById('userPopup');
  if (toggle && popup) {
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      popup.classList.toggle('show');
    });
    document.addEventListener('click', function (e) {
      if (!popup.contains(e.target) && !toggle.contains(e.target)) {
        popup.classList.remove('show');
      }
    });
  }

  // Mobile sidebar toggle
  const mobileBtn = document.getElementById('btnMobileSidebar');
  const sidebar = document.getElementById('appSidebar');
  if (mobileBtn && sidebar) {
    mobileBtn.addEventListener('click', function () {
      sidebar.classList.toggle('mobile-show');
    });
  }

  // Generic refresh button: re-fetch dashboard data if available, otherwise reload page
  const refreshBtn = document.getElementById('btnRefreshData');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      refreshBtn.classList.add('loading');
      if (typeof window.reloadPageData === 'function') {
        Promise.resolve(window.reloadPageData()).finally(function () {
          setTimeout(function () { refreshBtn.classList.remove('loading'); }, 300);
        });
      } else {
        window.location.reload();
      }
    });
  }
});

// Small helper other pages can use
function formatNumber(n) {
  return new Intl.NumberFormat('id-ID').format(n);
}

// ===== Konfirmasi hapus/aksi via SweetAlert2 =====
// Pakai atribut data-confirm pada <form> (submit) atau <a> (klik) alih-alih
// window.confirm() bawaan browser, supaya tampilan konfirmasi konsisten.
//
//   <form data-confirm="Yakin hapus data ini?" ...>
//   <a href="..." data-confirm="Yakin ingin logout?" data-confirm-icon="question">
//
// Atribut opsional: data-confirm-title, data-confirm-icon (warning/question/error),
// data-confirm-button (teks tombol konfirmasi).
document.addEventListener('submit', function (e) {
  const form = e.target;
  if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
  if (form.dataset.confirmed === '1') return; // sudah dikonfirmasi, lanjutkan submit asli
  e.preventDefault();

  Swal.fire({
    title: form.dataset.confirmTitle || 'Konfirmasi',
    text: form.dataset.confirm,
    icon: form.dataset.confirmIcon || 'warning',
    showCancelButton: true,
    confirmButtonText: form.dataset.confirmButton || 'Ya, lanjutkan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#9ca3af',
    reverseButtons: true,
    focusCancel: true,
  }).then(function (result) {
    if (result.isConfirmed) {
      form.dataset.confirmed = '1';
      form.submit();
    }
  });
});

document.addEventListener('click', function (e) {
  const link = e.target.closest ? e.target.closest('a[data-confirm]') : null;
  if (!link) return;
  e.preventDefault();

  Swal.fire({
    title: link.dataset.confirmTitle || 'Konfirmasi',
    text: link.dataset.confirm,
    icon: link.dataset.confirmIcon || 'question',
    showCancelButton: true,
    confirmButtonText: link.dataset.confirmButton || 'Ya',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#2f6fed',
    cancelButtonColor: '#9ca3af',
    reverseButtons: true,
  }).then(function (result) {
    if (result.isConfirmed) {
      window.location.href = link.href;
    }
  });
});
