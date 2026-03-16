/* ============================================================
   blog.js — category filter for blog cards
   ============================================================ */
(function () {
  var filters = document.querySelectorAll('.blog__filter');
  var cards   = document.querySelectorAll('.blog__card[data-category]');

  if (!filters.length) return;

  filters.forEach(function (btn) {
    btn.addEventListener('click', function () {
      /* Update active button */
      filters.forEach(function (b) { b.classList.remove('-active'); });
      btn.classList.add('-active');

      var value = btn.getAttribute('data-filter');

      /* Show / hide cards */
      cards.forEach(function (card) {
        if (value === 'all' || card.getAttribute('data-category') === value) {
          card.classList.remove('-hidden');
        } else {
          card.classList.add('-hidden');
        }
      });
    });
  });
})();
