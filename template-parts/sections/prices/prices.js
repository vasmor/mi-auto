/* ============================================================
   prices.js — model & service tab filtering for price tables
   ============================================================ */
(function () {
  var modelTabs   = document.querySelectorAll('.prices__model-tab');
  var serviceTabs = document.querySelectorAll('.prices__service-tab');
  var tableWraps  = document.querySelectorAll('.prices__table-wrap');
  var emptyMsg    = document.querySelector('.prices__empty');

  if (!modelTabs.length || !serviceTabs.length) return;

  var activeModel   = 'outlander';
  var activeService = 'to';

  function updateTables() {
    var found = false;

    for (var i = 0; i < tableWraps.length; i++) {
      var wrap = tableWraps[i];
      var matchModel   = wrap.getAttribute('data-model') === activeModel;
      var matchService = wrap.getAttribute('data-service') === activeService;

      if (matchModel && matchService) {
        wrap.classList.remove('-hidden');
        found = true;
      } else {
        wrap.classList.add('-hidden');
      }
    }

    if (emptyMsg) {
      emptyMsg.style.display = found ? 'none' : 'block';
    }
  }

  /* ── Model tabs ──────────────────────────────── */
  for (var m = 0; m < modelTabs.length; m++) {
    modelTabs[m].addEventListener('click', function () {
      for (var j = 0; j < modelTabs.length; j++) {
        modelTabs[j].classList.remove('-active');
      }
      this.classList.add('-active');
      activeModel = this.getAttribute('data-model');
      updateTables();
    });
  }

  /* ── Service tabs ────────────────────────────── */
  for (var s = 0; s < serviceTabs.length; s++) {
    serviceTabs[s].addEventListener('click', function () {
      for (var j = 0; j < serviceTabs.length; j++) {
        serviceTabs[j].classList.remove('-active');
      }
      this.classList.add('-active');
      activeService = this.getAttribute('data-service');
      updateTables();
    });
  }
})();
