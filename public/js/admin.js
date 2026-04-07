// admin.js
document.addEventListener('DOMContentLoaded', () => {
  // Sidebar toggle
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) sidebar.classList.remove('open');
    });
  }

  // Clock
  const el = document.getElementById('tbTime');
  if (el) {
    const tick = () => {
      const now = new Date();
      el.textContent = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    };
    tick();
    setInterval(tick, 1000);
  }

  // CSRF token on all fetch requests
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  if (csrfToken) {
    const origFetch = window.fetch;
    window.fetch = (url, opts = {}) => {
      opts.headers = opts.headers || {};
      opts.headers['X-CSRF-TOKEN'] = csrfToken;
      opts.headers['X-Requested-With'] = 'XMLHttpRequest';
      return origFetch(url, opts);
    };
  }
});

function showToast(msg, type = 'default', duration = 3000) {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast-item ${type}`;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => {
    t.style.opacity = '0';
    t.style.transform = 'translateX(40px)';
    t.style.transition = '0.3s ease';
    setTimeout(() => t.remove(), 300);
  }, duration);
}
