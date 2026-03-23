/* ============================================================
   model-tabs.js — tab switching + popup management
   ============================================================ */
(function () {
  /* ── Tab switching ──────────────────────────────── */
  var tabs   = document.querySelectorAll('.model-tabs__tab');
  var panels = document.querySelectorAll('.model-tabs__panel');

  if (!tabs.length) return;

  for (var i = 0; i < tabs.length; i++) {
    tabs[i].addEventListener('click', function () {
      var target = this.getAttribute('data-tab');

      for (var j = 0; j < tabs.length; j++) {
        tabs[j].classList.remove('-active');
        tabs[j].setAttribute('aria-selected', 'false');
      }
      this.classList.add('-active');
      this.setAttribute('aria-selected', 'true');

      for (var k = 0; k < panels.length; k++) {
        if (panels[k].getAttribute('data-panel') === target) {
          panels[k].classList.add('-active');
        } else {
          panels[k].classList.remove('-active');
        }
      }
    });
  }

  /* ── Popup management ───────────────────────────── */
  var popups = document.querySelectorAll('.miauto-popup');

  function openPopup(id) {
    var popup = document.getElementById(id);
    if (!popup) return;
    popup.classList.add('-visible');
    popup.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closePopup(popup) {
    popup.classList.remove('-visible');
    popup.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function closeAllPopups() {
    for (var i = 0; i < popups.length; i++) {
      closePopup(popups[i]);
    }
  }

  // Bind popup triggers
  var triggers = document.querySelectorAll('[data-popup]');
  for (var t = 0; t < triggers.length; t++) {
    triggers[t].addEventListener('click', function (e) {
      e.preventDefault();
      openPopup(this.getAttribute('data-popup'));
    });
  }

  // Bind close buttons and overlays
  for (var p = 0; p < popups.length; p++) {
    var closeBtn = popups[p].querySelector('.miauto-popup__close');
    var overlay  = popups[p].querySelector('.miauto-popup__overlay');

    if (closeBtn) {
      closeBtn.addEventListener('click', (function (popup) {
        return function () { closePopup(popup); };
      })(popups[p]));
    }

    if (overlay) {
      overlay.addEventListener('click', (function (popup) {
        return function () { closePopup(popup); };
      })(popups[p]));
    }
  }

  // ESC key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      closeAllPopups();
    }
  });
})();
