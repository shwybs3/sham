document.addEventListener('click', function (e) {
  var nav = document.getElementById('mainNav');
  if (!nav) return;
  if (nav.classList.contains('open') && !nav.contains(e.target) && !e.target.closest('.hamburger')) {
    nav.classList.remove('open');
  }
});
