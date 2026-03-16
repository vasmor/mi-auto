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

  /* ── Desktop dropdown ("Услуги и ремонт") → toggles hero service-menu card */
  function initDropdown() {
    var navItem = document.querySelector('.header__nav-item.-dropdown');
    var serviceMenu = document.querySelector('.hero__service-menu');
    if (!navItem || !serviceMenu) return;

    var hideTimeout;

    function show() {
      clearTimeout(hideTimeout);
      navItem.classList.add('-open');
      serviceMenu.classList.add('-visible');
    }

    function hide() {
      hideTimeout = setTimeout(function () {
        navItem.classList.remove('-open');
        serviceMenu.classList.remove('-visible');
      }, 200);
    }

    // Show on hover over nav item
    navItem.addEventListener('mouseenter', show);
    navItem.addEventListener('mouseleave', hide);

    // Keep visible when hovering over the service-menu card itself
    serviceMenu.addEventListener('mouseenter', show);
    serviceMenu.addEventListener('mouseleave', hide);

    // Toggle on click (for touch devices)
    navItem.addEventListener('click', function (e) {
      e.stopPropagation();
      if (serviceMenu.classList.contains('-visible')) {
        serviceMenu.classList.remove('-visible');
        navItem.classList.remove('-open');
      } else {
        show();
      }
    });

    document.addEventListener('click', function () {
      serviceMenu.classList.remove('-visible');
      navItem.classList.remove('-open');
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
