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
