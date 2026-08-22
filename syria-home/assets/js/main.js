(function () {
  var nav = document.getElementById('mainNav');
  var toggle = document.getElementById('navToggle');
  var backdrop = document.getElementById('navBackdrop');
  if (!nav || !toggle) return;

  function openNav() {
    nav.classList.add('open');
    if (backdrop) backdrop.classList.add('show');
    document.body.classList.add('nav-open');
  }
  function closeNav() {
    nav.classList.remove('open');
    if (backdrop) backdrop.classList.remove('show');
    document.body.classList.remove('nav-open');
  }

  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    nav.classList.contains('open') ? closeNav() : openNav();
  });
  if (backdrop) backdrop.addEventListener('click', closeNav);
  nav.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', closeNav); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeNav(); });
  document.addEventListener('click', function (e) {
    if (nav.classList.contains('open') && !nav.contains(e.target) && !toggle.contains(e.target)) closeNav();
  });
})();
