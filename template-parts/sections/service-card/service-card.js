/* ============================================================
   service-card.js
   FAQ accordion, examples carousel, reviews carousel
   ============================================================ */
(function () {

  /* ── FAQ accordion ─────────────────────────────────────── */
  var faqItems = document.querySelectorAll('.faq__item');

  for (var i = 0; i < faqItems.length; i++) {
    (function (item) {
      var btn = item.querySelector('.faq__question');
      var answer = item.querySelector('.faq__answer');
      if (!btn || !answer) return;

      btn.addEventListener('click', function () {
        var isOpen = item.classList.contains('-open');

        if (isOpen) {
          item.classList.remove('-open');
          btn.setAttribute('aria-expanded', 'false');
          answer.style.maxHeight = '0';
        } else {
          item.classList.add('-open');
          btn.setAttribute('aria-expanded', 'true');
          answer.style.maxHeight = answer.scrollHeight + 'px';
        }
      });
    })(faqItems[i]);
  }

  /* ── Examples carousel ─────────────────────────────────── */
  var exSection = document.querySelector('.sc-examples');
  if (exSection) {
    var exTrack  = exSection.querySelector('.sc-examples__track');
    var exCards   = exTrack ? exTrack.children : [];
    var exPrev   = exSection.querySelector('.sc-examples__nav-btn.-prev');
    var exNext   = exSection.querySelector('.sc-examples__nav-btn.-next');
    var exDots   = exSection.querySelectorAll('.sc-examples__dot');
    var exCurrent = 0;

    function exGo(idx) {
      if (idx < 0) idx = 0;
      var isMobile = window.innerWidth <= 767;
      var maxIdx = isMobile ? exCards.length - 1 : 0;
      if (idx > maxIdx) idx = maxIdx;
      exCurrent = idx;

      if (isMobile) {
        exTrack.style.transform = 'translateX(-' + (exCurrent * 100) + '%)';
      } else {
        exTrack.style.transform = 'translateX(0)';
      }

      for (var d = 0; d < exDots.length; d++) {
        exDots[d].classList.toggle('-active', d === exCurrent);
      }
    }

    if (exPrev) exPrev.addEventListener('click', function () { exGo(exCurrent - 1); });
    if (exNext) exNext.addEventListener('click', function () { exGo(exCurrent + 1); });

    for (var d = 0; d < exDots.length; d++) {
      (function (idx) {
        exDots[idx].addEventListener('click', function () { exGo(idx); });
      })(d);
    }
  }

  /* ── Reviews carousel ──────────────────────────────────── */
  var revSection = document.querySelector('.reviews');
  if (revSection) {
    var revTrack = revSection.querySelector('.reviews__track');
    var revCards  = revTrack ? revTrack.children : [];
    var revDots  = revSection.querySelectorAll('.reviews__dot');
    var revCurrent = 0;

    function revGo(idx) {
      if (idx < 0) idx = 0;
      var isMobile = window.innerWidth <= 767;
      var maxIdx = isMobile ? revCards.length - 1 : 0;
      if (idx > maxIdx) idx = maxIdx;
      revCurrent = idx;

      if (isMobile) {
        revTrack.style.transform = 'translateX(-' + (revCurrent * 100) + '%)';
      } else {
        revTrack.style.transform = 'translateX(0)';
      }

      for (var d = 0; d < revDots.length; d++) {
        revDots[d].classList.toggle('-active', d === revCurrent);
      }
    }

    for (var d = 0; d < revDots.length; d++) {
      (function (idx) {
        revDots[idx].addEventListener('click', function () { revGo(idx); });
      })(d);
    }

    /* Touch swipe for mobile */
    var touchStartX = 0;
    var touchEndX = 0;

    if (revTrack) {
      revTrack.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
      }, { passive: true });

      revTrack.addEventListener('touchend', function (e) {
        touchEndX = e.changedTouches[0].screenX;
        var diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50) {
          if (diff > 0) revGo(revCurrent + 1);
          else revGo(revCurrent - 1);
        }
      }, { passive: true });
    }

    /* Touch swipe for examples on mobile */
    if (exTrack) {
      var exTouchStartX = 0;

      exTrack.addEventListener('touchstart', function (e) {
        exTouchStartX = e.changedTouches[0].screenX;
      }, { passive: true });

      exTrack.addEventListener('touchend', function (e) {
        var exTouchEndX = e.changedTouches[0].screenX;
        var diff = exTouchStartX - exTouchEndX;
        if (Math.abs(diff) > 50) {
          if (diff > 0) exGo(exCurrent + 1);
          else exGo(exCurrent - 1);
        }
      }, { passive: true });
    }
  }

})();
