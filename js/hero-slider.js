/**
 * hero-slider.js
 * Handles the hero section slider:
 *  - Click on pagination dots to switch slides
 *  - Auto-advance every 5 seconds
 *  - Pause auto-advance on hover
 */

(function () {
  'use strict';

  var INTERVAL = 5000;

  function init() {
    var wrap = document.querySelector('.hero__bg-wrap');
    if (!wrap) return;

    var slides = wrap.querySelectorAll('.hero__slide');
    var dots   = wrap.querySelectorAll('.hero__pagination-dot');
    if (slides.length === 0 || dots.length === 0) return;

    var current = 0;
    var timer   = null;

    function goTo(index) {
      if (index === current) return;

      slides[current].classList.remove('-active');
      dots[current].classList.remove('-active');
      dots[current].setAttribute('aria-selected', 'false');

      current = index;

      slides[current].classList.add('-active');
      dots[current].classList.add('-active');
      dots[current].setAttribute('aria-selected', 'true');
    }

    function next() {
      goTo((current + 1) % slides.length);
    }

    function startAuto() {
      stopAuto();
      timer = setInterval(next, INTERVAL);
    }

    function stopAuto() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    }

    // Dot clicks
    dots.forEach(function (dot, i) {
      dot.addEventListener('click', function () {
        goTo(i);
        startAuto();
      });
    });

    // Pause on hover over the slider area
    wrap.addEventListener('mouseenter', stopAuto);
    wrap.addEventListener('mouseleave', startAuto);

    startAuto();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
