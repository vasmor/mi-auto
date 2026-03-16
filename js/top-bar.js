/**
 * top-bar.js
 * Handles the promo announcement bar:
 *  - Close button hides the bar (session storage so it stays closed on refresh)
 *  - If previously closed this session, keeps it hidden on load
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'topBarClosed';

  function init() {
    const bar = document.querySelector('.top-bar');
    if (!bar) return;

    // Keep hidden if user already closed it this session
    if (sessionStorage.getItem(STORAGE_KEY) === '1') {
      bar.classList.add('-hidden');
      return;
    }

    const closeBtn = bar.querySelector('.top-bar__close');
    if (!closeBtn) return;

    closeBtn.addEventListener('click', function () {
      bar.classList.add('-hidden');
      sessionStorage.setItem(STORAGE_KEY, '1');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
