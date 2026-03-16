/* ============================================================
   SCROLL-TO-TOP  —  show after 2 sections, smooth scroll up
   ============================================================ */
(function () {
  const btn = document.querySelector('.scroll-top');
  if (!btn) return;

  function getThreshold() {
    const sections = document.querySelectorAll('section');
    if (sections.length >= 2) {
      return sections[1].getBoundingClientRect().bottom + window.scrollY;
    }
    return window.innerHeight * 2;
  }

  let threshold = getThreshold();

  window.addEventListener('scroll', function () {
    if (window.scrollY >= threshold) {
      btn.classList.add('-visible');
    } else {
      btn.classList.remove('-visible');
    }
  }, { passive: true });

  window.addEventListener('resize', function () {
    threshold = getThreshold();
  }, { passive: true });

  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();
