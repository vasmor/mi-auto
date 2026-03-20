/* reviews.js — Reviews carousel
   Desktop (>767px): prev/next buttons, shown only if cards > 4
   Mobile  (≤767px): dots + touch swipe, no nav buttons
*/
(function () {
  var section = document.querySelector('.reviews');
  if (!section) return;

  var track   = section.querySelector('.reviews__track');
  var cards   = track ? Array.from(track.children) : [];
  var dots    = Array.from(section.querySelectorAll('.reviews__dot'));
  var prevBtn = section.querySelector('.reviews__nav-btn.-prev');
  var nextBtn = section.querySelector('.reviews__nav-btn.-next');
  var nav     = section.querySelector('.reviews__nav');
  var count   = cards.length;
  var current = 0;

  if (!track || count === 0) return;

  function getGap() {
    return parseFloat(getComputedStyle(track).gap) || 0;
  }

  function maxIdx() {
    return window.innerWidth <= 767
      ? count - 1
      : Math.max(0, count - 4);
  }

  function go(idx) {
    if (idx < 0) idx = 0;
    var mx = maxIdx();
    if (idx > mx) idx = mx;
    current = idx;

    if (window.innerWidth <= 767) {
      track.style.transform = 'translateX(-' + (current * 100) + '%)';
    } else {
      var gap = getGap();
      var cw  = cards[0].offsetWidth;
      track.style.transform = 'translateX(-' + (current * (cw + gap)) + 'px)';
    }

    dots.forEach(function (d, i) {
      d.classList.toggle('-active', i === current);
    });

    if (prevBtn) prevBtn.disabled = (current === 0);
    if (nextBtn) nextBtn.disabled = (current >= maxIdx());
  }

  function updateNav() {
    if (!nav) return;
    var show = window.innerWidth > 767 && count > 4;
    nav.style.visibility = show ? '' : 'hidden';
  }

  go(0);
  updateNav();

  if (prevBtn) prevBtn.addEventListener('click', function () { go(current - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { go(current + 1); });

  dots.forEach(function (d, i) {
    d.addEventListener('click', function () { go(i); });
  });

  var touchStartX = 0;
  track.addEventListener('touchstart', function (e) {
    touchStartX = e.changedTouches[0].screenX;
  }, { passive: true });
  track.addEventListener('touchend', function (e) {
    var diff = touchStartX - e.changedTouches[0].screenX;
    if (Math.abs(diff) > 50) go(current + (diff > 0 ? 1 : -1));
  }, { passive: true });

  window.addEventListener('resize', function () {
    updateNav();
    go(current);
  });
}());
