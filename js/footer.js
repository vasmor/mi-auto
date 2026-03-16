(function () {
  'use strict';

  function init() {
    var columns = document.querySelectorAll('.footer__column[data-accordion]');
    if (!columns.length) return;

    columns.forEach(function (col) {
      var title = col.querySelector('.footer__column-title');
      if (!title) return;

      title.addEventListener('click', function () {
        col.classList.toggle('-open');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
