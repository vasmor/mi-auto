/**
 * header.js
 * Handles:
 *  - Mobile burger → drawer open/close
 *  - Drawer overlay click closes panel
 *  - Desktop "Услуги и ремонт" dropdown (placeholder)
 *  - Sticky header on scroll (adds .-sticky class to .header)
 */

(function () {
  'use strict';

  function init() {
    initBurger();
    initDropdown();
    initSticky();
  }

  /* ── Mobile burger / drawer ──────────────────────────────── */
  function initBurger() {
    const burger  = document.querySelector('.header__burger');
    const drawer  = document.querySelector('.header__drawer');
    if (!burger || !drawer) return;

    const overlay = drawer.querySelector('.header__drawer-overlay');

    function openDrawer() {
      drawer.classList.add('-open');
      document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
      drawer.classList.remove('-open');
      document.body.style.overflow = '';
    }

    const closeBtn = drawer.querySelector('.header__drawer-close');

    burger.addEventListener('click', openDrawer);

    if (overlay) {
      overlay.addEventListener('click', closeDrawer);
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', closeDrawer);
    }

    // Close on Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && drawer.classList.contains('-open')) {
        closeDrawer();
      }
    });
  }

  /* ── Mobile submenu toggle (chevron button click) ───────────── */
  function initDropdown() {
    var drawer = document.querySelector('.header__drawer');
    if (!drawer) return;

    // Handle chevron toggle buttons inside the drawer nav.
    drawer.addEventListener('click', function (e) {
      var btn = e.target.closest('.header__nav-toggle');
      if (!btn) return;

      e.preventDefault();
      var li = btn.closest('li.menu-item');
      if (!li) return;

      var isOpen = li.classList.contains('-open');

      // Close all other open items at the same level.
      var siblings = li.parentElement.querySelectorAll(':scope > li.menu-item.-open');
      siblings.forEach(function (s) {
        if (s !== li) {
          s.classList.remove('-open');
          var sibBtn = s.querySelector(':scope > .header__nav-toggle');
          if (sibBtn) sibBtn.setAttribute('aria-expanded', 'false');
        }
      });

      li.classList.toggle('-open', !isOpen);
      btn.setAttribute('aria-expanded', String(!isOpen));
    });
  }

  /* ── Sticky header on scroll ─────────────────────────────── */
  function initSticky() {
    const header = document.querySelector('.header');
    if (!header) return;

    const topBar = document.querySelector('.top-bar');

    function onScroll() {
      const topBarH = (topBar && !topBar.classList.contains('-hidden'))
        ? topBar.getBoundingClientRect().height
        : 0;

      if (window.scrollY > topBarH + 20) {
        header.classList.add('-sticky');
      } else {
        header.classList.remove('-sticky');
      }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
