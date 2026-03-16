(function () {
  'use strict';

  function init() {
    var btn = document.querySelector('.services__more');
    if (!btn) return;

    var grid = document.querySelector('.services__grid');

    btn.addEventListener('click', function () {
      grid.classList.add('-expanded');
      btn.style.display = 'none';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
