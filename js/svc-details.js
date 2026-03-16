(function () {
  'use strict';

  var mq = window.matchMedia('(max-width: 767px)');

  function init() {
    var tabs = document.querySelectorAll('.svc-details__tab');
    var panels = document.querySelectorAll('.svc-details__panel');
    var content = document.querySelector('.svc-details__content');
    if (!tabs.length || !content) return;

    /* ── Desktop: classic tab switching ─────────────────────── */
    function activateDesktop(tab) {
      var target = tab.getAttribute('data-tab');

      tabs.forEach(function (t) {
        t.classList.remove('-active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('-active');
      tab.setAttribute('aria-selected', 'true');

      panels.forEach(function (p) {
        if (p.getAttribute('data-panel') === target) {
          p.classList.add('-active');
        } else {
          p.classList.remove('-active');
        }
      });
    }

    /* ── Mobile: accordion — panel opens right after the tab ── */
    function activateMobile(tab) {
      var target = tab.getAttribute('data-tab');
      var panel = null;
      panels.forEach(function (p) {
        if (p.getAttribute('data-panel') === target) {
          panel = p;
        }
      });

      // Toggle off if already open
      if (tab.classList.contains('-active')) {
        tab.classList.remove('-active');
        tab.setAttribute('aria-selected', 'false');
        if (panel) panel.classList.remove('-active');
        return;
      }

      // Close all
      tabs.forEach(function (t) {
        t.classList.remove('-active');
        t.setAttribute('aria-selected', 'false');
      });
      panels.forEach(function (p) {
        p.classList.remove('-active');
      });

      // Open clicked
      tab.classList.add('-active');
      tab.setAttribute('aria-selected', 'true');
      if (panel) {
        panel.classList.add('-active');
        // Move panel right after the clicked tab
        tab.after(panel);
      }
    }

    /* ── Click handler ─────────────────────────────────────── */
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        if (mq.matches) {
          activateMobile(tab);
        } else {
          activateDesktop(tab);
        }
      });
    });

    /* ── Resize: restore desktop layout ────────────────────── */
    function resetLayout() {
      if (!mq.matches) {
        // Move all panels back to .svc-details__content
        panels.forEach(function (p) {
          content.appendChild(p);
        });
        // Ensure at least first tab is active
        var hasActive = false;
        tabs.forEach(function (t) {
          if (t.classList.contains('-active')) hasActive = true;
        });
        if (!hasActive && tabs.length) {
          tabs[0].classList.add('-active');
          tabs[0].setAttribute('aria-selected', 'true');
        }
        // Activate matching panel
        var activeTab = document.querySelector('.svc-details__tab.-active');
        if (activeTab) {
          var target = activeTab.getAttribute('data-tab');
          panels.forEach(function (p) {
            if (p.getAttribute('data-panel') === target) {
              p.classList.add('-active');
            } else {
              p.classList.remove('-active');
            }
          });
        }
      }
    }

    mq.addEventListener('change', resetLayout);

    // On mobile initial load: close all (accordion starts collapsed)
    if (mq.matches) {
      tabs.forEach(function (t) {
        t.classList.remove('-active');
        t.setAttribute('aria-selected', 'false');
      });
      panels.forEach(function (p) {
        p.classList.remove('-active');
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
