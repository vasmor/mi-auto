/* ============================================================
   works.js — carousel for work card images
   ============================================================ */
(function () {
  var carousels = document.querySelectorAll('.works__carousel');
  if (!carousels.length) return;

  for (var i = 0; i < carousels.length; i++) {
    initCarousel(carousels[i]);
  }

  function initCarousel(el) {
    var track  = el.querySelector('.works__carousel-track');
    var slides = el.querySelectorAll('.works__carousel-slide');
    var dots   = el.querySelectorAll('.works__carousel-dot');
    var prev   = el.querySelector('.works__carousel-btn.-prev');
    var next   = el.querySelector('.works__carousel-btn.-next');
    var total  = slides.length;
    var current = 0;

    if (total <= 1) {
      if (prev) prev.style.display = 'none';
      if (next) next.style.display = 'none';
      return;
    }

    function goTo(index) {
      if (index < 0) index = total - 1;
      if (index >= total) index = 0;
      current = index;
      track.style.transform = 'translateX(-' + (current * 100) + '%)';
      for (var d = 0; d < dots.length; d++) {
        dots[d].classList.toggle('-active', d === current);
      }
    }

    if (prev) {
      prev.addEventListener('click', function () { goTo(current - 1); });
    }
    if (next) {
      next.addEventListener('click', function () { goTo(current + 1); });
    }

    for (var d = 0; d < dots.length; d++) {
      (function (idx) {
        dots[idx].addEventListener('click', function () { goTo(idx); });
      })(d);
    }
  }
})();
