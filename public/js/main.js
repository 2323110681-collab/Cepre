// ---------- Mobile menu ----------
const menuToggle = document.getElementById('menuToggle');
const mainNav = document.getElementById('mainNav');
if (menuToggle && mainNav) {
  menuToggle.addEventListener('click', () => mainNav.classList.toggle('open'));
}

// ---------- Teacher filters ----------
const filterBar = document.getElementById('filterBar');
if (filterBar) {
  const buttons = filterBar.querySelectorAll('.filter-btn');
  const cards = document.querySelectorAll('.teacher-card');
  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      buttons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const f = btn.dataset.filter;
      cards.forEach(card => {
        card.classList.toggle('hidden', f !== 'todos' && card.dataset.cat !== f);
      });
    });
  });
}

// ---------- FAQ accordion ----------
document.querySelectorAll('.faq-item').forEach(item => {
  const q = item.querySelector('.faq-q');
  const a = item.querySelector('.faq-a');
  q.addEventListener('click', () => {
    const isOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(o => {
      o.classList.remove('open');
      o.querySelector('.faq-a').style.maxHeight = null;
    });
    if (!isOpen) {
      item.classList.add('open');
      a.style.maxHeight = a.scrollHeight + 'px';
    }
  });
});
// Open default item
const firstOpen = document.querySelector('.faq-item.open .faq-a');
if (firstOpen) firstOpen.style.maxHeight = firstOpen.scrollHeight + 'px';
