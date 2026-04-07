// app.js — client-side utilities
document.addEventListener('DOMContentLoaded', () => {
  // User menu toggle
  const btn = document.getElementById('userMenuBtn');
  const dd  = document.getElementById('userDropdown');
  if (btn && dd) {
    btn.addEventListener('click', e => { e.stopPropagation(); dd.classList.toggle('open'); });
    document.addEventListener('click', () => dd.classList.remove('open'));
  }

  // Theme toggle
  const themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    const saved = localStorage.getItem('yta_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
    themeBtn.addEventListener('click', () => {
      const cur = document.documentElement.getAttribute('data-theme');
      const next = cur === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('yta_theme', next);
    });
  }
});

function showToast(msg, type = 'default', duration = 3000) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.className = `toast show ${type}`;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), duration);
}

function copyText(id, msg = 'Copiado!') {
  const el = document.getElementById(id);
  if (!el) return;
  const text = el.value !== undefined ? el.value : el.textContent;
  navigator.clipboard.writeText(text)
    .then(() => showToast(msg, 'success'))
    .catch(() => {
      const ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      showToast(msg, 'success');
    });
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}
